<?php

namespace App\Contracts;

interface ReputationProviderInterface
{
    /**
     * Check reputation of domain or IP against public threat intelligence feeds.
     * Returns: CLEAN, SUSPICIOUS, MALICIOUS, or UNKNOWN.
     */
    public function checkReputation(string $target, string $type = 'domain'): array;
}
