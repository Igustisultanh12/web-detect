<?php

namespace App\Services\Analysis;

use App\Services\Security\SafeHttpClientService;
use Exception;
use Illuminate\Support\Facades\Log;

class SubdomainDiscoveryService
{
    protected SafeHttpClientService $httpClient;

    public function __construct(SafeHttpClientService $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Passively discovers subdomains using public Certificate Transparency (crt.sh)
     * and DNS query records. No aggressive port scanning or brute-force.
     */
    public function discover(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^www\.#', '', $domain);
        $discovered = [];

        // 1. Query crt.sh Certificate Transparency logs JSON API
        try {
            $crtUrl = "https://crt.sh/?q=%25.{$domain}&output=json";
            $response = $this->httpClient->get($crtUrl, ['timeout' => 8]);

            if ($response['status'] === 200 && !empty($response['body'])) {
                $certs = json_decode($response['body'], true);
                if (is_array($certs)) {
                    foreach ($certs as $cert) {
                        $nameValue = $cert['name_value'] ?? '';
                        $lines = explode("\n", $nameValue);
                        foreach ($lines as $line) {
                            $sub = strtolower(trim($line));
                            if (str_starts_with($sub, '*.')) {
                                $sub = substr($sub, 2);
                            }
                            if (str_ends_with($sub, '.' . $domain) && $sub !== $domain) {
                                $discovered[$sub] = 'Certificate Transparency (crt.sh)';
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::info("crt.sh passive discovery error for {$domain}: " . $e->getMessage());
        }

        // 2. Common passive DNS hostnames check (non-aggressive, standard DNS lookup)
        $standardPrefixes = ['www', 'mail', 'api', 'app', 'cdn', 'dev', 'admin', 'portal', 'vpn', 'smtp'];
        foreach ($standardPrefixes as $prefix) {
            $candidate = "{$prefix}.{$domain}";
            if (!isset($discovered[$candidate])) {
                $hasDns = @dns_get_record($candidate, DNS_A);
                if (!empty($hasDns)) {
                    $discovered[$candidate] = 'Public DNS A-Record';
                }
            }
        }

        // 3. Resolve active status and IP for each discovered subdomain
        $results = [];
        $limit = 30; // Cap to 30 subdomains to prevent excessive work
        $count = 0;

        foreach ($discovered as $subdomain => $source) {
            if ($count >= $limit) {
                break;
            }

            $ip = null;
            $isActive = false;

            $dnsA = @dns_get_record($subdomain, DNS_A);
            if (!empty($dnsA) && !empty($dnsA[0]['ip'])) {
                $ip = $dnsA[0]['ip'];
                $isActive = true;
            }

            $results[] = [
                'subdomain' => $subdomain,
                'source' => $source,
                'ip_address' => $ip,
                'is_active' => $isActive,
            ];
            $count++;
        }

        return $results;
    }
}
