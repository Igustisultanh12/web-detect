<?php

namespace App\Jobs;

use App\Contracts\ScreenshotProviderInterface;
use App\Models\Investigation;
use App\Models\Screenshot;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CaptureScreenshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 45;

    public function __construct(public Investigation $investigation) {}

    public function handle(ScreenshotProviderInterface $screenshotProvider, EvidenceManagerService $evidenceManager): void
    {
        $this->investigation->addTimeline('SCREENSHOT_CAPTURE_STARTED', 'Mengambil tangkapan layar website.', 'INFO');
        $shot = $screenshotProvider->capture($this->investigation->target_url, $this->investigation->investigation_code);

        Screenshot::create([
            'investigation_id' => $this->investigation->id,
            'file_path' => $shot['file_path'],
            'original_url' => $this->investigation->target_url,
            'sha256' => $shot['sha256'],
            'width' => $shot['width'],
            'height' => $shot['height'],
            'file_size' => $shot['file_size'],
            'captured_at' => $shot['captured_at'],
        ]);

        $evidenceManager->record($this->investigation, 'Screenshot', 'Browser Worker', [
            'file_path' => $shot['file_path'],
            'sha256' => $shot['sha256'],
            'captured_at' => $shot['captured_at'],
        ], $shot, 'Tangkapan layar visual website target');

        $this->investigation->addTimeline('SCREENSHOT_CAPTURE_COMPLETED', 'Screenshot berhasil disimpan sebagai barang bukti.', 'SUCCESS');
    }
}
