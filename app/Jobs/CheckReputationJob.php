<?php

namespace App\Jobs;

use App\Contracts\ReputationProviderInterface;
use App\Models\Investigation;
use App\Models\ReputationResult;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckReputationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(public Investigation $investigation, public string $domain, public ?string $primaryIp = null) {}

    public function handle(ReputationProviderInterface $reputationProvider, EvidenceManagerService $evidenceManager): void
    {
        $this->investigation->addTimeline('REPUTATION_CHECK_STARTED', 'Memeriksa reputasi domain pada threat intelligence.', 'INFO');
        $reputations = $reputationProvider->checkReputation($this->domain, 'domain');

        if ($this->primaryIp) {
            $ipRep = $reputationProvider->checkReputation($this->primaryIp, 'ip');
            $reputations = array_merge($reputations, $ipRep);
        }

        foreach ($reputations as $rep) {
            ReputationResult::create([
                'investigation_id' => $this->investigation->id,
                'provider_name' => $rep['provider_name'],
                'status' => $rep['status'],
                'threat_type' => $rep['threat_type'],
                'score' => $rep['score'],
                'checked_at' => $rep['checked_at'],
                'details' => $rep['details'],
            ]);
        }

        $evidenceManager->record($this->investigation, 'Reputation', 'Public Threat Feeds', $reputations, $reputations, 'Hasil pengecekan reputasi publik');
        $this->investigation->addTimeline('REPUTATION_CHECK_COMPLETED', 'Pengecekan reputasi selesai.', 'SUCCESS');
    }
}
