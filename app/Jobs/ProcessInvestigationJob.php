<?php

namespace App\Jobs;

use App\Models\Investigation;
use App\Services\Analysis\InvestigationWorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInvestigationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180; // 3 minutes timeout

    public function __construct(public Investigation $investigation) {}

    public function handle(InvestigationWorkflowService $workflowService): void
    {
        $workflowService->process($this->investigation);
    }
}
