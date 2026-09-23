<?php

namespace App\Services\Analysis;

class CdnDetectionService
{
    /**
     * Known CDN signatures based on CNAME, headers, ASN, reverse DNS, and nameservers.
     */
    protected static array $cdnSignatures = [
        'Cloudflare' => [
            'asn' => ['AS13335'],
            'headers' => ['cf-ray', 'cf-cache-status', 'server' => 'cloudflare'],
            'cname' => ['.cloudflare.net', '.cloudflare.com'],
            'ns' => ['.cloudflare.com'],
            'rdns' => ['.cloudflare.com'],
        ],
        'Akamai' => [
            'asn' => ['AS20940', 'AS16625', 'AS32787'],
            'headers' => ['x-akamai-transformed', 'akamai-origin-hop', 'server' => 'akamaighost'],
            'cname' => ['.edgekey.net', '.akamaiedge.net', '.akamai.net', '.akadns.net'],
            'ns' => ['.akamai.net', '.akamaitech.net'],
            'rdns' => ['.akamaitechnologies.com'],
        ],
        'Fastly' => [
            'asn' => ['AS54113'],
            'headers' => ['x-fastly-request-id', 'fastly-restarts'],
            'cname' => ['.fastly.net', '.fastlylb.net'],
            'ns' => [],
            'rdns' => ['.fastly.net'],
        ],
        'Amazon CloudFront' => [
            'asn' => ['AS16509'],
            'headers' => ['x-amz-cf-id', 'x-amz-cf-pop', 'server' => 'cloudfront'],
            'cname' => ['.cloudfront.net'],
            'ns' => [],
            'rdns' => ['.cloudfront.net'],
        ],
        'Google Cloud CDN' => [
            'asn' => ['AS15169', 'AS396982'],
            'headers' => ['server' => 'gws', 'server' => 'gse', 'via' => 'google'],
            'cname' => ['.googlehosted.com', '.googleusercontent.com'],
            'ns' => ['.googledomains.com'],
            'rdns' => ['.google.com', '.1e100.net'],
        ],
        'Microsoft Azure CDN' => [
            'asn' => ['AS8075'],
            'headers' => ['x-azure-ref', 'x-ms-ref'],
            'cname' => ['.azureedge.net', '.trafficmanager.net'],
            'ns' => [],
            'rdns' => ['.azure.com'],
        ],
        'Sucuri' => [
            'asn' => ['AS30148'],
            'headers' => ['x-sucuri-id', 'x-sucuri-cache', 'server' => 'sucuri'],
            'cname' => ['.sucuri.net'],
            'ns' => [],
            'rdns' => ['.sucuri.net'],
        ],
    ];

    /**
     * Inspect DNS records, HTTP headers, ASN, and reverse DNS to detect CDN.
     */
    public function detect(array $dnsRecords, array $httpHeaders, array $asnList = [], array $rdnsList = []): array
    {
        $detected = false;
        $provider = null;
        $evidence = [];

        // Check HTTP headers
        $serverHeader = strtolower($httpHeaders['server'] ?? '');
        $viaHeader = strtolower($httpHeaders['via'] ?? '');

        foreach (self::$cdnSignatures as $cdnName => $sig) {
            // Check specific header existence
            foreach ($sig['headers'] as $headerKey => $headerVal) {
                if (is_int($headerKey)) {
                    if (isset($httpHeaders[strtolower($headerVal)])) {
                        $detected = true;
                        $provider = $cdnName;
                        $evidence[] = "HTTP Header '{$headerVal}' terdeteksi";
                        break 2;
                    }
                } else {
                    if (str_contains($serverHeader, $headerVal) || str_contains($viaHeader, $headerVal)) {
                        $detected = true;
                        $provider = $cdnName;
                        $evidence[] = "Header Server/Via '{$headerVal}' terdeteksi";
                        break 2;
                    }
                }
            }

            // Check CNAME records
            foreach ($dnsRecords as $rec) {
                if (($rec['record_type'] ?? '') === 'CNAME') {
                    $target = strtolower($rec['target'] ?? '');
                    foreach ($sig['cname'] as $cnamePattern) {
                        if (str_contains($target, $cnamePattern)) {
                            $detected = true;
                            $provider = $cdnName;
                            $evidence[] = "CNAME record '{$target}' cocok dengan CDN {$cdnName}";
                            break 3;
                        }
                    }
                }
            }

            // Check ASN
            foreach ($asnList as $asn) {
                if (in_array(strtoupper($asn), $sig['asn'], true)) {
                    $detected = true;
                    $provider = $cdnName;
                    $evidence[] = "ASN {$asn} terdaftar milik {$cdnName}";
                    break 2;
                }
            }

            // Check rDNS
            foreach ($rdnsList as $rdns) {
                foreach ($sig['rdns'] as $pattern) {
                    if (str_contains(strtolower($rdns), $pattern)) {
                        $detected = true;
                        $provider = $cdnName;
                        $evidence[] = "Reverse DNS '{$rdns}' merujuk pada {$cdnName}";
                        break 3;
                    }
                }
            }
        }

        return [
            'cdn_detected' => $detected,
            'provider' => $provider,
            'evidence' => $evidence,
            'disclaimer' => $detected
                ? "IP yang ditemukan kemungkinan merupakan endpoint CDN/reverse proxy ({$provider}) dan bukan alamat server origin. Jangan mengasumsikan IP ini sebagai hosting fisik server target."
                : "Tidak terdeteksi layanan CDN populer pada domain target. Alamat IP kemungkinan mengarah langsung ke server hosting atau infrastruktur cloud penyedia.",
        ];
    }
}
