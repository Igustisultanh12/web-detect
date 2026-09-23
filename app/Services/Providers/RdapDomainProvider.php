<?php

namespace App\Services\Providers;

use App\Contracts\DomainProviderInterface;
use App\Services\Security\SafeHttpClientService;
use Exception;
use Illuminate\Support\Facades\Log;

class RdapDomainProvider implements DomainProviderInterface
{
    protected SafeHttpClientService $httpClient;

    public function __construct(SafeHttpClientService $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getDomainInfo(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^www\.#', '', $domain);
        $tld = pathinfo($domain, PATHINFO_EXTENSION);

        $result = [
            'domain' => $domain,
            'tld' => '.' . $tld,
            'registrar' => null,
            'registered_at' => null,
            'expires_at' => null,
            'domain_status' => [],
            'nameservers' => [],
            'dnssec_status' => 'Unknown',
            'rdap_data' => null,
        ];

        // Public RDAP bootstrap endpoint
        $rdapUrl = "https://rdap.org/domain/{$domain}";

        try {
            $response = $this->httpClient->get($rdapUrl, ['timeout' => 8]);
            if ($response['status'] >= 200 && $response['status'] < 300 && !empty($response['body'])) {
                $data = json_decode($response['body'], true);
                if (is_array($data)) {
                    $result['rdap_data'] = $data;

                    // Parse entities for registrar
                    if (!empty($data['entities'])) {
                        foreach ($data['entities'] as $entity) {
                            if (!empty($entity['roles']) && in_array('registrar', $entity['roles'], true)) {
                                $result['registrar'] = $entity['vcardArray'][1][1][3] ?? ($entity['handle'] ?? null);
                            }
                        }
                    }

                    // Parse events (registration, expiration)
                    if (!empty($data['events'])) {
                        foreach ($data['events'] as $event) {
                            $action = $event['eventAction'] ?? '';
                            $date = $event['eventDate'] ?? null;
                            if ($action === 'registration' && $date) {
                                $result['registered_at'] = date('Y-m-d H:i:s', strtotime($date));
                            } elseif ($action === 'expiration' && $date) {
                                $result['expires_at'] = date('Y-m-d H:i:s', strtotime($date));
                            }
                        }
                    }

                    // Parse status
                    if (!empty($data['status'])) {
                        $result['domain_status'] = (array) $data['status'];
                    }

                    // Parse nameservers
                    if (!empty($data['nameservers'])) {
                        foreach ($data['nameservers'] as $ns) {
                            if (!empty($ns['ldhName'])) {
                                $result['nameservers'][] = strtolower($ns['ldhName']);
                            }
                        }
                    }

                    // Parse DNSSEC
                    if (isset($data['secureDNS']['delegationSigned'])) {
                        $result['dnssec_status'] = $data['secureDNS']['delegationSigned'] ? 'SIGNED' : 'UNSIGNED';
                    }
                }
            }
        } catch (Exception $e) {
            Log::info("RDAP query failed for domain {$domain}: " . $e->getMessage());
        }

        // Fallback nameservers from native DNS if not discovered via RDAP
        if (empty($result['nameservers'])) {
            $nsRecords = @dns_get_record($domain, DNS_NS);
            if (!empty($nsRecords)) {
                foreach ($nsRecords as $ns) {
                    if (!empty($ns['target'])) {
                        $result['nameservers'][] = strtolower($ns['target']);
                    }
                }
            }
        }

        return $result;
    }
}
