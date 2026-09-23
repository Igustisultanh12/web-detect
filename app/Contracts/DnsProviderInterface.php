<?php

namespace App\Contracts;

interface DnsProviderInterface
{
    /**
     * Get DNS records for domain (A, AAAA, CNAME, MX, TXT, NS, SOA, CAA).
     */
    public function getRecords(string $domain): array;
}
