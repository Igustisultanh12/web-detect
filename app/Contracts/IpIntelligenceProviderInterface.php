<?php

namespace App\Contracts;

interface IpIntelligenceProviderInterface
{
    /**
     * Get ASN, ISP, organization, country, region, city, coordinates for IP address.
     */
    public function getIpInfo(string $ip): array;
}
