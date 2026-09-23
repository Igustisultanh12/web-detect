<?php

namespace App\Jobs;

use App\Models\Investigation;
use App\Services\Reports\ReportGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(public Investigation $investigation, public string $format = 'PDF') {}

    public function handle(ReportGeneratorService $reportGenerator): void
    {
        $this->investigation->addTimeline('REPORT_GENERATION_STARTED', 'Menyusun laporan investigasi format ' . $this->format, 'INFO');
        $report = $reportGenerator->generate($this->investigation, $this->format);
        $this->investigation->addTimeline('REPORT_GENERATION_COMPLETED', "Laporan {$report->report_number} siap diunduh.", 'SUCCESS');
    }
}
