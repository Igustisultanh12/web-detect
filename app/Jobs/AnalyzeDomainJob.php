<?php

namespace App\Jobs;

use App\Contracts\DomainProviderInterface;
use App\Models\DomainRecord;
use App\Models\Investigation;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(public Investigation $investigation, public string $domain) {}

    public function handle(DomainProviderInterface $provider, EvidenceManagerService $evidenceManager): void
    {
        $this->investigation->addTimeline('DOMAIN_ANALYSIS_STARTED', 'Memulai pengumpulan data domain dan RDAP/WHOIS.', 'INFO');
        $info = $provider->getDomainInfo($this->domain);

        DomainRecord::create([
            'investigation_id' => $this->investigation->id,
            'domain' => $info['domain'],
            'tld' => $info['tld'],
            'registrar' => $info['registrar'],
            'registered_at' => $info['registered_at'],
            'expires_at' => $info['expires_at'],
            'domain_status' => $info['domain_status'],
            'nameservers' => $info['nameservers'],
            'dnssec_status' => $info['dnssec_status'],
            'rdap_data' => $info['rdap_data'],
        ]);

        $evidenceManager->record($this->investigation, 'Domain Information', 'RDAP Protocol', $info, $info, 'Metadata registrasi domain publik');
        $this->investigation->addTimeline('DOMAIN_ANALYSIS_COMPLETED', 'Data domain publik berhasil dikumpulkan.', 'SUCCESS');
    }
}
