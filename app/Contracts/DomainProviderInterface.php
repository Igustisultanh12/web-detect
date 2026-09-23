<?php

namespace App\Contracts;

interface DomainProviderInterface
{
    /**
     * Inspect domain info (registrar, creation date, expiry, nameservers, DNSSEC).
     */
    public function getDomainInfo(string $domain): array;
}
