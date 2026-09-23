<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserSecurity;
use Exception;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new TOTP secret for setup.
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * Generate otpauth QR code URL for authenticator apps.
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        $company = config('app.name', 'WebGuard');
        return $this->google2fa->getQRCodeUrl($company, $user->email, $secret);
    }

    /**
     * Verify a 6-digit TOTP code against secret.
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, $code, 1); // 1 window tolerance
    }

    /**
     * Generate 10 secure recovery codes in format ABCD-EFGH-IJKL.
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $part1 = strtoupper(Str::random(4));
            $part2 = strtoupper(Str::random(4));
            $part3 = strtoupper(Str::random(4));
            $codes[] = "{$part1}-{$part2}-{$part3}";
        }
        return $codes;
    }

    /**
     * Enable 2FA for a user after verifying initial code.
     */
    public function enableTwoFactor(User $user, string $secret, string $code): array
    {
        if (!$this->verifyCode($secret, $code)) {
            throw new Exception("Kode autentikasi 6 digit tidak valid atau telah kedaluwarsa.");
        }

        $recoveryCodes = $this->generateRecoveryCodes();
        $hashedCodes = array_map(fn($c) => password_hash($c, PASSWORD_DEFAULT), $recoveryCodes);

        $security = $user->security ?: new UserSecurity(['user_id' => $user->id]);
        $security->totp_secret = $secret;
        $security->recovery_codes = $hashedCodes;
        $security->save();

        $user->update(['two_factor_enabled' => true]);

        AuditLog::log('2FA_ENABLED', 'User', (string) $user->id);

        return [
            'success' => true,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Verify recovery code and mark it as consumed.
     */
    public function verifyRecoveryCode(User $user, string $recoveryCode): bool
    {
        $security = $user->security;
        if (!$security || empty($security->recovery_codes)) {
            return false;
        }

        $hashedCodes = (array) $security->recovery_codes;
        $trimmedCode = trim(strtoupper($recoveryCode));

        foreach ($hashedCodes as $index => $hash) {
            if (password_verify($trimmedCode, $hash)) {
                // Consume code
                unset($hashedCodes[$index]);
                $security->recovery_codes = array_values($hashedCodes);
                $security->save();

                AuditLog::log('RECOVERY_CODE_USED', 'User', (string) $user->id);
                return true;
            }
        }

        return false;
    }
}
