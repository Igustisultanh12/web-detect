<?php

namespace App\Services\Security;

use App\Exceptions\SsrfBlockedException;

class SsrfProtectionService
{
    /**
     * List of disallowed private/reserved CIDRs (IPv4 and IPv6).
     */
    protected static array $blockedCidrs = [
        // IPv4 Loopback
        '127.0.0.0/8',
        // IPv4 Private
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        // IPv4 Link-Local / Cloud Metadata (AWS, GCP, Azure, DigitalOcean)
        '169.254.0.0/16',
        // IPv4 Broadcast / Current network
        '0.0.0.0/8',
        '224.0.0.0/4', // Multicast
        '240.0.0.0/4', // Reserved
        '255.255.255.255/32',
        // CGNAT
        '100.64.0.0/10',
    ];

    protected static array $blockedHosts = [
        'localhost',
        'localhost.localdomain',
        'ip6-localhost',
        'ip6-loopback',
        'metadata.google.internal',
        'instance-data',
    ];

    /**
     * Validate an arbitrary URL against SSRF vulnerabilities.
     * Throws an exception or returns false if unsafe.
     *
     * @param string $url
     * @return array Normalized URL and verified resolved IP addresses
     * @throws SsrfBlockedException
     */
    public function validateUrl(string $url): array
    {
        $normalizedUrl = trim($url);

        if (preg_match('#^([a-z0-9+.-]+)://#i', $normalizedUrl, $protoMatch)) {
            $scheme = strtolower($protoMatch[1]);
            if (!in_array($scheme, ['http', 'https'], true)) {
                throw new SsrfBlockedException("Protokol '{$scheme}' tidak diizinkan. Hanya HTTP dan HTTPS yang didukung.");
            }
        } else {
            $normalizedUrl = 'https://' . $normalizedUrl;
        }

        $parts = parse_url($normalizedUrl);
        if (!$parts || empty($parts['host'])) {
            throw new SsrfBlockedException("URL tidak valid atau format host tidak dapat diuraikan: {$url}");
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new SsrfBlockedException("Protokol '{$scheme}' tidak diizinkan. Hanya HTTP dan HTTPS yang didukung.");
        }

        $host = strtolower($parts['host']);
        $cleanHost = trim($host, '[]');

        // Check if user/password is embedded (e.g. http://foo:bar@127.0.0.1)
        if (!empty($parts['user']) || !empty($parts['pass'])) {
            throw new SsrfBlockedException("Autentikasi kredensial dalam URL tidak diizinkan.");
        }

        // Port restrictions
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        if (!in_array($port, [80, 443, 8080, 8443], true)) {
            throw new SsrfBlockedException("Port {$port} tidak diizinkan untuk analisis pasif.");
        }

        // Check explicit hostname blacklist
        if (in_array($cleanHost, self::$blockedHosts, true) || str_ends_with($cleanHost, '.localhost') || str_ends_with($cleanHost, '.internal') || str_ends_with($cleanHost, '.local')) {
            throw new SsrfBlockedException("Akses ke host internal atau loopback '{$host}' dilarang keras demi keamanan (SSRF Protection).");
        }

        // Check if host is raw IP or decimal/hex representation
        $resolvedIps = $this->resolveAndValidateIps($cleanHost);

        return [
            'normalized_url' => $normalizedUrl,
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port,
            'path' => $parts['path'] ?? '/',
            'query' => $parts['query'] ?? '',
            'resolved_ips' => $resolvedIps,
        ];
    }

    /**
     * Resolve host to IPs and verify every IP is public and non-malicious.
     */
    public function resolveAndValidateIps(string $host): array
    {
        $ips = [];

        // Check if $host is already an IP address
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $this->assertPublicIp($host);
            return [$host];
        }

        // Check decimal / hex / octal IP representations (e.g. 2130706433 or 017700000001)
        if (is_numeric($host) || preg_match('/^0x[0-9a-fA-F]+$/', $host)) {
            throw new SsrfBlockedException("Representasi IP non-standar (desimal/heksadesimal) '{$host}' ditolak demi mitigasi SSRF.");
        }

        // Resolve IPv4
        $ipv4Records = @dns_get_record($host, DNS_A);
        if ($ipv4Records) {
            foreach ($ipv4Records as $record) {
                if (!empty($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }
        }

        // Resolve IPv6
        $ipv6Records = @dns_get_record($host, DNS_AAAA);
        if ($ipv6Records) {
            foreach ($ipv6Records as $record) {
                if (!empty($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        // Fallback gethostbynamel
        if (empty($ips)) {
            $fallback = @gethostbynamel($host);
            if ($fallback) {
                $ips = array_merge($ips, $fallback);
            }
        }

        if (empty($ips)) {
            // If DNS lookup returned nothing locally, return empty array (target domain might not resolve)
            return [];
        }

        $ips = array_unique($ips);

        foreach ($ips as $ip) {
            $this->assertPublicIp($ip);
        }

        return $ips;
    }

    /**
     * Asserts that a single IP address is public and outside blocked ranges.
     */
    public function assertPublicIp(string $ip): void
    {
        // Check IPv6 loopback and unique local
        if ($ip === '::1' || $ip === '0:0:0:0:0:0:0:1') {
            throw new SsrfBlockedException("Akses ke IPv6 loopback [{$ip}] diblokir.");
        }

        if (str_starts_with(strtolower($ip), 'fc00:') || str_starts_with(strtolower($ip), 'fd00:') || str_starts_with(strtolower($ip), 'fe80:')) {
            throw new SsrfBlockedException("Akses ke IPv6 unique local / link-local [{$ip}] diblokir.");
        }

        // Check IPv4-mapped IPv6 (e.g. ::ffff:127.0.0.1)
        if (preg_match('/^::ffff:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/i', $ip, $matches)) {
            $this->assertPublicIp($matches[1]);
            return;
        }

        // Standard filter_var check with flags
        $isPublic = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if (!$isPublic) {
            throw new SsrfBlockedException("Alamat IP [{$ip}] berada pada rentang privat, lokal, atau khusus dan diblokir dari analisis.");
        }

        // Explicit CIDR check for IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            foreach (self::$blockedCidrs as $cidr) {
                if ($this->ipMatchesCidr($ip, $cidr)) {
                    throw new SsrfBlockedException("Alamat IP [{$ip}] cocok dengan subnet terlarang [{$cidr}].");
                }
            }
        }
    }

    protected function ipMatchesCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
