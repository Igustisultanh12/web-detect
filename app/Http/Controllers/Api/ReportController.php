<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Investigation;
use App\Models\Report;
use App\Services\Reports\ReportGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function __construct(protected ReportGeneratorService $reportGenerator) {}

    public function index(Request $request): JsonResponse
    {
        $reports = Report::with(['investigation', 'generator'])
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function generate(Request $request, $investigationId): JsonResponse
    {
        $request->validate([
            'format' => ['required', 'string', 'in:PDF,CSV,JSON,XLSX'],
        ]);

        $inv = Investigation::where('id', $investigationId)
            ->orWhere('investigation_code', $investigationId)
            ->orWhere('uuid', $investigationId)
            ->firstOrFail();

        $report = $this->reportGenerator->generate($inv, $request->format);

        AuditLog::log('REPORT_CREATED', 'Report', (string) $report->id, [
            'investigation_code' => $inv->investigation_code,
            'format' => $request->format,
            'report_number' => $report->report_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Laporan resmi ({$request->format}) berhasil diterbitkan.",
            'report' => $report,
        ]);
    }

    public function download(Request $request, $uuid)
    {
        $report = Report::where('uuid', $uuid)->orWhere('id', $uuid)->firstOrFail();

        AuditLog::log('REPORT_DOWNLOADED', 'Report', (string) $report->id, [
            'report_number' => $report->report_number,
            'sha256' => $report->sha256,
        ]);

        $path = Storage::disk('local')->path($report->file_path);
        if (!file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'Berkas laporan tidak ditemukan.'], 404);
        }

        $extension = strtolower($report->format);
        $downloadName = "{$report->report_number}.{$extension}";

        return response()->download($path, $downloadName);
    }
}
