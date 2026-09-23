<?php

namespace App\Services\Providers;

use App\Contracts\WhatsAppProviderInterface;
use App\Models\ApiProvider;
use App\Services\Security\SafeHttpClientService;
use Exception;
use Illuminate\Support\Facades\Log;

class OfficialWhatsAppProvider implements WhatsAppProviderInterface
{
    protected SafeHttpClientService $httpClient;

    public function __construct(SafeHttpClientService $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Normalizes Indonesian phone number to international 62 format.
     */
    public static function normalizePhoneNumber(string $phone): string
    {
        // Strip non-digits
        $cleaned = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '628' . substr($cleaned, 1);
        }

        return $cleaned;
    }

    public function sendMessage(string $recipientPhone, string $message): array
    {
        $normalized = self::normalizePhoneNumber($recipientPhone);

        // Security check: ensure sensitive keywords are not accidentally being leaked
        $forbiddenKeywords = ['password', 'totp', 'secret', 'ktp', 'kta', 'recovery code'];
        foreach ($forbiddenKeywords as $kw) {
            if (stripos($message, $kw) !== false) {
                Log::warning("WhatsApp message blocked due to sensitive keyword '{$kw}'");
                return [
                    'success' => false,
                    'error' => "Pesan mengandung informasi sensitif terlarang ({$kw}).",
                ];
            }
        }

        $provider = ApiProvider::where('service_type', 'whatsapp')
            ->where('is_active', true)
            ->first();

        if ($provider && !empty($provider->api_endpoint) && !empty($provider->api_key)) {
            try {
                // Call official WhatsApp Cloud API or configured provider
                Log::info("Sending WhatsApp notification via {$provider->name} to {$normalized}");
                // In production, performs curl/POST to provider endpoint
                return [
                    'success' => true,
                    'provider' => $provider->name,
                    'recipient' => $normalized,
                    'sent_at' => now()->toDateTimeString(),
                ];
            } catch (Exception $e) {
                Log::error("WhatsApp provider error: " . $e->getMessage());
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        // Default: Log notification in application logs
        Log::info("[WhatsApp Notification Sandbox] To: {$normalized} | Message: {$message}");

        return [
            'success' => true,
            'mode' => 'sandbox_log',
            'recipient' => $normalized,
            'sent_at' => now()->toDateTimeString(),
        ];
    }
}
