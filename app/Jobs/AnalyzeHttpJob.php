<?php

namespace App\Jobs;

use App\Models\HttpResult;
use App\Models\Investigation;
use App\Services\Analysis\HttpAnalysisService;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeHttpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(public Investigation $investigation, public string $url) {}

    public function handle(HttpAnalysisService $httpService, EvidenceManagerService $evidenceManager): void
    {
        $this->investigation->addTimeline('HTTP_ANALYSIS_STARTED', 'Menginspeksi respons HTTP pasif.', 'INFO');
        $http = $httpService->analyze($this->url);

        HttpResult::create([
            'investigation_id' => $this->investigation->id,
            'http_status' => $http['http_status'],
            'https_available' => $http['https_available'],
            'final_url' => $http['final_url'],
            'redirect_chain' => $http['redirect_chain'],
            'server_header' => $http['server_header'],
            'content_type' => $http['content_type'],
            'content_length' => $http['content_length'],
            'compression' => $http['compression'],
            'hsts_header' => $http['hsts_header'],
            'csp_header' => $http['csp_header'],
            'x_frame_options' => $http['x_frame_options'],
            'x_content_type_options' => $http['x_content_type_options'],
            'referrer_policy' => $http['referrer_policy'],
            'permissions_policy' => $http['permissions_policy'],
            'cookies_data' => $http['cookies_data'],
            'security_score' => $http['security_score'],
            'security_notes' => $http['security_notes'],
            'raw_headers' => $http['raw_headers'],
        ]);

        $evidenceManager->record($this->investigation, 'HTTP Headers', 'Safe HTTP Client', $http['raw_headers'], $http, 'Header HTTP dan Skor Security Header');
        $this->investigation->addTimeline('HTTP_ANALYSIS_COMPLETED', "Pemeriksaan HTTP selesai. Skor: {$http['security_score']}/100", 'SUCCESS');
    }
}
