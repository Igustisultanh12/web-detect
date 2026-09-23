<?php

namespace App\Services\Providers;

use App\Contracts\IpIntelligenceProviderInterface;
use App\Services\Security\SafeHttpClientService;
use Exception;
use Illuminate\Support\Facades\Log;

class IpIntelligenceProvider implements IpIntelligenceProviderInterface
{
    protected SafeHttpClientService $httpClient;

    public function __construct(SafeHttpClientService $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getIpInfo(string $ip): array
    {
        $ip = trim($ip);
        $rdns = @gethostbyaddr($ip);
        if ($rdns === $ip) {
            $rdns = null;
        }

        $result = [
            'ip_address' => $ip,
            'ip_version' => str_contains($ip, ':') ? 'v6' : 'v4',
            'reverse_dns' => $rdns,
            'asn' => null,
            'asn_org' => null,
            'bgp_prefix' => null,
            'registry' => null,
            'isp' => null,
            'organization' => null,
            'hosting_type' => 'Data Center',
            'country' => 'Unknown',
            'country_code' => null,
            'region' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
            'is_datacenter' => true,
        ];

        // Query public IP intelligence (ip-api.com json API with fields)
        try {
            $url = "http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,region,regionName,city,lat,lon,timezone,isp,org,as,query";
            $response = $this->httpClient->get($url, ['timeout' => 5]);

            if ($response['status'] === 200 && !empty($response['body'])) {
                $data = json_decode($response['body'], true);
                if (!empty($data) && ($data['status'] ?? '') === 'success') {
                    $result['country'] = $data['country'] ?? 'Unknown';
                    $result['country_code'] = $data['countryCode'] ?? null;
                    $result['region'] = $data['regionName'] ?? ($data['region'] ?? null);
                    $result['city'] = $data['city'] ?? null;
                    $result['latitude'] = isset($data['lat']) ? (float) $data['lat'] : null;
                    $result['longitude'] = isset($data['lon']) ? (float) $data['lon'] : null;
                    $result['timezone'] = $data['timezone'] ?? null;
                    $result['isp'] = $data['isp'] ?? null;
                    $result['organization'] = $data['org'] ?? ($data['isp'] ?? null);

                    if (!empty($data['as'])) {
                        // Format: AS13335 Cloudflare, Inc.
                        $asParts = explode(' ', $data['as'], 2);
                        $result['asn'] = $asParts[0] ?? null;
                        $result['asn_org'] = $asParts[1] ?? ($result['organization'] ?? null);
                    }
                }
            }
        } catch (Exception $e) {
            Log::info("Public IP intelligence query failed for {$ip}: " . $e->getMessage());
        }

        return $result;
    }
}
