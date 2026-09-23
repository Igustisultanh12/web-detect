<?php

namespace App\Jobs;

use App\Models\Investigation;
use App\Models\Technology;
use App\Services\Analysis\TechnologyDetectionService;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectTechnologyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(public Investigation $investigation, public array $headers, public array $cookies, public string $html) {}

    public function handle(TechnologyDetectionService $techDetector, EvidenceManagerService $evidenceManager): void
    {
        $this->investigation->addTimeline('TECH_DETECTION_STARTED', 'Mendeteksi teknologi web.', 'INFO');
        $techs = $techDetector->detect($this->headers, $this->cookies, $this->html);

        foreach ($techs as $t) {
            Technology::create([
                'investigation_id' => $this->investigation->id,
                'category' => $t['category'],
                'name' => $t['name'],
                'version' => $t['version'],
                'confidence' => $t['confidence'],
                'matched_pattern' => $t['matched_pattern'],
                'icon' => $t['icon'],
            ]);
        }

        $evidenceManager->record($this->investigation, 'Technology', 'Passive Fingerprinting', $techs, $techs, 'Daftar teknologi terdeteksi');
        $this->investigation->addTimeline('TECH_DETECTION_COMPLETED', 'Terdeteksi ' . count($techs) . ' teknologi pada target.', 'SUCCESS');
    }
}
