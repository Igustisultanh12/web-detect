<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserSession;
use App\Services\Security\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(protected TwoFactorAuthService $twoFactorService) {}

    /**
     * Authenticate user, verify status, track login attempt, and return Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'two_factor_code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $email = $request->email;
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        $user = User::with(['roles.permissions', 'rank', 'unit', 'position'])->where('email', $email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            LoginAttempt::create([
                'email' => $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'status' => 'FAILED',
                'failure_reason' => 'Kombinasi email atau password salah',
            ]);

            SecurityEvent::fire('LOGIN_FAILED', "Percobaan login gagal untuk email: {$email}", 'WARNING');

            return response()->json([
                'success' => false,
                'message' => 'Kombinasi email dan password tidak cocok.',
            ], 401);
        }

        // Check Account Status
        if ($user->status === 'INACTIVE') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda berstatus NONAKTIF. Hubungi Administrator untuk pengaktifan kembali.',
            ], 403);
        }

        if ($user->status === 'SUSPENDED') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda DITANGGUHKAN (SUSPENDED): ' . ($user->deactivation_reason ?: 'Pelanggaran keamanan.'),
            ], 403);
        }

        // 2FA Verification
        if ($user->two_factor_enabled) {
            $code = $request->two_factor_code;
            $recoveryCode = $request->recovery_code;

            if (empty($code) && empty($recoveryCode)) {
                return response()->json([
                    'success' => true,
                    'requires_two_factor' => true,
                    'message' => 'Masukkan 6 digit kode Google Authenticator atau kode pemulihan.',
                ]);
            }

            $security = $user->security;
            $verified = false;

            if (!empty($code) && $security?->totp_secret) {
                $verified = $this->twoFactorService->verifyCode($security->totp_secret, $code);
            } elseif (!empty($recoveryCode)) {
                $verified = $this->twoFactorService->verifyRecoveryCode($user, $recoveryCode);
            }

            if (!$verified) {
                return response()->json([
                    'success' => false,
                    'requires_two_factor' => true,
                    'message' => 'Kode Two-Factor Authentication atau kode pemulihan tidak valid.',
                ], 422);
            }
        }

        // Record successful login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);

        LoginAttempt::create([
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status' => 'SUCCESS',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'LOGIN_SUCCESS',
            'target_type' => 'User',
            'target_id' => (string) $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'result' => 'SUCCESS',
            'created_at' => now(),
        ]);

        // Record User Session
        UserSession::create([
            'user_id' => $user->id,
            'session_id' => (string) Str::uuid(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'is_current' => true,
            'last_active_at' => now(),
        ]);

        // Revoke old tokens if single session policy or create new token
        $token = $user->createToken('webguard_api_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Autentikasi berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'nrp' => $user->nrp,
                'rank' => $user->rank?->name,
                'rank_code' => $user->rank?->code,
                'position' => $user->position?->name,
                'unit' => $user->unit?->name,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->roles->flatMap->permissions->pluck('name')->unique()->values(),
                'status' => $user->status,
                'two_factor_enabled' => $user->two_factor_enabled,
                'masked_whatsapp' => $user->masked_whatsapp,
            ],
        ]);
    }

    /**
     * Current authenticated user info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles.permissions', 'rank', 'unit', 'position', 'profile']);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'nrp' => $user->nrp,
                'phone' => $user->phone,
                'whatsapp_number' => $user->whatsapp_number,
                'masked_whatsapp' => $user->masked_whatsapp,
                'rank' => $user->rank?->name,
                'rank_code' => $user->rank?->code,
                'position' => $user->position?->name,
                'unit' => $user->unit?->name,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->roles->flatMap->permissions->pluck('name')->unique()->values(),
                'status' => $user->status,
                'two_factor_enabled' => $user->two_factor_enabled,
                'active_from' => $user->active_from?->format('d M Y'),
                'activated_by_admin_at' => $user->activated_by_admin_at?->format('d M Y'),
                'last_login_at' => $user->last_login_at?->format('d M Y H:i'),
                'last_login_ip' => $user->last_login_ip,
                'profile' => $user->profile,
            ],
        ]);
    }

    /**
     * Start 2FA setup: generate secret & QR code.
     */
    public function setupTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();
        $secret = $this->twoFactorService->generateSecretKey();
        $qrCodeUrl = $this->twoFactorService->getQrCodeUrl($user, $secret);

        return response()->json([
            'success' => true,
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    /**
     * Confirm 2FA setup with initial 6-digit code.
     */
    public function enableTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'secret' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $result = $this->twoFactorService->enableTwoFactor($user, $request->secret, $request->code);

        return response()->json([
            'success' => true,
            'message' => 'Two-Factor Authentication (Google Authenticator) berhasil diaktifkan.',
            'recovery_codes' => $result['recovery_codes'],
        ]);
    }

    /**
     * Revoke Sanctum Token (Logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'LOGOUT',
                'target_type' => 'User',
                'target_id' => (string) $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'result' => 'SUCCESS',
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesi berhasil diakhiri.',
        ]);
    }
}
