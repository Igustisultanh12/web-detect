<?php

namespace App\Services\Providers;

use App\Contracts\DnsProviderInterface;

class SystemDnsProvider implements DnsProviderInterface
{
    public function getRecords(string $domain): array
    {
        $records = [];
        $domain = idn_to_ascii(trim($domain));

        $types = [
            'A' => DNS_A,
            'AAAA' => DNS_AAAA,
            'CNAME' => DNS_CNAME,
            'MX' => DNS_MX,
            'TXT' => DNS_TXT,
            'NS' => DNS_NS,
            'SOA' => DNS_SOA,
            'CAA' => DNS_CAA,
        ];

        foreach ($types as $typeName => $dnsConst) {
            $rawRecords = @dns_get_record($domain, $dnsConst);
            if (!empty($rawRecords)) {
                foreach ($rawRecords as $item) {
                    $target = match ($typeName) {
                        'A' => $item['ip'] ?? '',
                        'AAAA' => $item['ipv6'] ?? '',
                        'CNAME' => $item['target'] ?? '',
                        'MX' => $item['target'] ?? '',
                        'TXT' => $item['txt'] ?? ($item['entries'][0] ?? ''),
                        'NS' => $item['target'] ?? '',
                        'SOA' => ($item['mname'] ?? '') . ' (Admin: ' . ($item['rname'] ?? '') . ')',
                        'CAA' => ($item['tag'] ?? '') . ' "' . ($item['value'] ?? '') . '"',
                        default => json_encode($item),
                    };

                    if (!empty($target)) {
                        $records[] = [
                            'record_type' => $typeName,
                            'host' => $item['host'] ?? $domain,
                            'target' => (string) $target,
                            'ttl' => $item['ttl'] ?? null,
                            'priority' => $item['pri'] ?? null,
                            'raw_entry' => json_encode($item),
                        ];
                    }
                }
            }
        }

        return $records;
    }
}
