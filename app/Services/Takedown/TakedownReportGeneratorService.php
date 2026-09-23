<?php

namespace App\Services\Takedown;

use App\Models\Evidence;
use App\Models\TakedownCase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TakedownReportGeneratorService
{
    /**
     * Generate official Takedown Report PDF.
     */
    public function generatePdf(TakedownCase $case): array
    {
        $investigation = $case->investigation;

        // Load selected evidences or all case evidences
        $evidenceIds = $case->selected_evidence_ids;
        if (!empty($evidenceIds)) {
            $evidences = Evidence::whereIn('id', $evidenceIds)->get();
        } elseif ($investigation) {
            $evidences = $investigation->evidences;
        } else {
            $evidences = collect();
        }

        $pdf = Pdf::loadView('reports.takedown-report-pdf', [
            'case' => $case,
            'investigation' => $investigation,
            'evidences' => $evidences,
        ])->setPaper('a4', 'portrait');

        $content = $pdf->output();
        $sha256 = hash('sha256', $content);
        $filename = "{$case->case_number}-Takedown-Report.pdf";
        $storagePath = "takedown/reports/{$case->case_number}/{$filename}";

        Storage::disk('local')->put($storagePath, $content);

        return [
            'filename' => $filename,
            'path' => $storagePath,
            'sha256' => $sha256,
            'size' => strlen($content),
            'content' => $content,
        ];
    }
}
