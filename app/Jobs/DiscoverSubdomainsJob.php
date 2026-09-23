<?php

namespace App\Jobs;

use App\Models\Investigation;
use App\Models\Subdomain;
use App\Services\Analysis\SubdomainDiscoveryService;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DiscoverSubdomainsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 45;

    public function __construct(public Investigation $investigation, public string $domain) {}

    public function handle(SubdomainDiscoveryService $subdomainService, EvidenceManagerService $evidenceManager): void
    {
        $this->investigation->addTimeline('SUBDOMAIN_DISCOVERY_STARTED', 'Mencari subdomain pasif.', 'INFO');
        $subdomains = $subdomainService->discover($this->domain);

        foreach ($subdomains as $s) {
            Subdomain::create([
                'investigation_id' => $this->investigation->id,
                'subdomain' => $s['subdomain'],
                'source' => $s['source'],
                'ip_address' => $s['ip_address'],
                'is_active' => $s['is_active'],
            ]);
        }

        $evidenceManager->record($this->investigation, 'Subdomain', 'Certificate Transparency', $subdomains, $subdomains, 'Daftar subdomain pasif');
        $this->investigation->addTimeline('SUBDOMAIN_DISCOVERY_COMPLETED', 'Ditemukan ' . count($subdomains) . ' subdomain pasif.', 'SUCCESS');
    }
}
