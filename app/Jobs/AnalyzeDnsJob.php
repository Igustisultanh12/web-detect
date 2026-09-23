<?php

namespace App\Jobs;

use App\Contracts\DnsProviderInterface;
use App\Models\DnsRecord;
use App\Models\Investigation;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeDnsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(public Investigation $investigation, public string $domain) {}

    public function handle(DnsProviderInterface $provider, EvidenceManagerService $evidenceManager): void
    {
        $this->investigation->addTimeline('DNS_ANALYSIS_STARTED', 'Menjalankan kueri DNS publik.', 'INFO');
        $records = $provider->getRecords($this->domain);

        foreach ($records as $r) {
            DnsRecord::create([
                'investigation_id' => $this->investigation->id,
                'record_type' => $r['record_type'],
                'host' => $r['host'],
                'target' => $r['target'],
                'ttl' => $r['ttl'],
                'priority' => $r['priority'],
                'raw_entry' => $r['raw_entry'],
            ]);
        }

        $evidenceManager->record($this->investigation, 'DNS Result', 'DNS Resolver', $records, $records, 'Daftar catatan DNS publik');
        $this->investigation->addTimeline('DNS_ANALYSIS_COMPLETED', 'Ditemukan ' . count($records) . ' catatan DNS publik.', 'SUCCESS');
    }
}
