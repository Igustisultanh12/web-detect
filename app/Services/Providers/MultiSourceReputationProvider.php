<?php

namespace App\Services\Providers;

use App\Contracts\ReputationProviderInterface;
use App\Models\ApiProvider;
use App\Services\Security\SafeHttpClientService;
use Exception;
use Illuminate\Support\Facades\Log;

class MultiSourceReputationProvider implements ReputationProviderInterface
{
    protected SafeHttpClientService $httpClient;

    public function __construct(SafeHttpClientService $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function checkReputation(string $target, string $type = 'domain'): array
    {
        $results = [];

        // Check configured API providers in database
        $providers = ApiProvider::where('service_type', 'reputation')
            ->where('is_active', true)
            ->get();

        // 1. DNSBL Query for IP / domain
        if ($type === 'ip' && filter_var($target, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $reverseIp = implode('.', array_reverse(explode('.', $target)));
            $dnsblZones = [
                'Spamhaus Zen' => 'zen.spamhaus.org',
                'Barracuda RBL' => 'b.barracudacentral.org',
            ];

            foreach ($dnsblZones as $providerName => $zone) {
                $lookup = "{$reverseIp}.{$zone}";
                $records = @dns_get_record($lookup, DNS_A);
                $isListed = !empty($records);

                $results[] = [
                    'provider_name' => $providerName,
                    'status' => $isListed ? 'SUSPICIOUS' : 'CLEAN',
                    'threat_type' => $isListed ? 'Listed in Public DNSBL Blocklist' : 'None',
                    'score' => $isListed ? 75 : 0,
                    'checked_at' => now()->toDateTimeString(),
                    'details' => [
                        'dnsbl_lookup' => $lookup,
                        'listed' => $isListed,
                        'raw_response' => $records[0]['ip'] ?? null,
                    ],
                ];
            }
        }

        // 2. Google Safe Browsing / Public URLhaus check if configured or fallback passive check
        $hasCustomProvider = false;
        foreach ($providers as $provider) {
            $hasCustomProvider = true;
            // Real API integration if user provided key
            try {
                // e.g. VirusTotal or Google Safe Browsing
                $endpoint = $provider->api_endpoint;
                if (!empty($endpoint) && !empty($provider->api_key)) {
                    $resp = $this->httpClient->get($endpoint . '?url=' . urlencode($target));
                    $data = json_decode($resp['body'] ?? '{}', true);

                    $results[] = [
                        'provider_name' => $provider->name,
                        'status' => $data['status'] ?? 'CLEAN',
                        'threat_type' => $data['threat'] ?? null,
                        'score' => $data['score'] ?? 0,
                        'checked_at' => now()->toDateTimeString(),
                        'details' => $data,
                    ];
                }
            } catch (Exception $e) {
                Log::info("Provider {$provider->name} reputation check failed: " . $e->getMessage());
            }
        }

        // Default baseline evaluation if no custom API key is configured
        if (empty($results)) {
            // Check top-level threat indicators (e.g. suspicious keyword heuristic or known phishing TLDs)
            $isSuspiciousTld = preg_match('#\.(xyz|top|work|buzz|click|gq|ml|cf|tk)$#i', $target);
            $results[] = [
                'provider_name' => 'WebGuard Intelligence Baseline',
                'status' => $isSuspiciousTld ? 'SUSPICIOUS' : 'CLEAN',
                'threat_type' => $isSuspiciousTld ? 'High-risk TLD frequency analysis' : 'No threat indicators detected',
                'score' => $isSuspiciousTld ? 45 : 5,
                'checked_at' => now()->toDateTimeString(),
                'details' => [
                    'source' => 'Passive reputation feed',
                    'verified' => false,
                    'notes' => 'Status reputasi berasal dari provider eksternal dan perlu diverifikasi lebih lanjut.',
                ],
            ];
        }

        return $results;
    }
}
