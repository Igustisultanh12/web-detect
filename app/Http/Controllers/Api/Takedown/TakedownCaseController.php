<?php

namespace App\Http\Controllers\Api\Takedown;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TakedownCase;
use App\Services\Takedown\EvidencePackageManagerService;
use App\Services\Takedown\TakedownCaseService;
use App\Services\Takedown\TakedownReportGeneratorService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TakedownCaseController extends Controller
{
    public function __construct(
        protected TakedownCaseService $caseService,
        protected TakedownReportGeneratorService $reportGenerator,
        protected EvidencePackageManagerService $packageManager
    ) {}

    /**
     * List takedown cases with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TakedownCase::with(['investigation', 'provider', 'assignedOfficer.rank', 'creator.rank'])
            ->withCount(['followUps', 'defensiveActions']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('case_number', 'like', "%{$s}%")
                  ->orWhere('target_domain', 'like', "%{$s}%")
                  ->orWhere('external_reference_number', 'like', "%{$s}%")
                  ->orWhere('provider_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->boolean('overdue_only')) {
            $query->where('next_follow_up_at', '<', now())
                  ->whereNotIn('status', ['ACTION_TAKEN', 'CLOSED', 'RESOLVED', 'REJECTED']);
        }

        $cases = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $cases->items(),
            'meta' => [
                'current_page' => $cases->currentPage(),
                'last_page' => $cases->lastPage(),
                'total' => $cases->total(),
            ],
        ]);
    }

    /**
     * Create a new takedown case.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'investigation_id' => ['nullable', 'exists:investigations,id'],
            'target_domain' => ['required', 'string', 'max:255'],
            'target_url' => ['required', 'string', 'max:2048'],
            'target_ip' => ['nullable', 'string', 'max:64'],
            'category' => ['required', 'string'],
            'allegation_summary' => ['required', 'string', 'min:10'],
            'legal_or_policy_basis' => ['nullable', 'string'],
            'evidence_summary' => ['nullable', 'string'],
            'selected_evidence_ids' => ['nullable', 'array'],
            'provider_id' => ['nullable', 'exists:takedown_providers,id'],
            'priority' => ['nullable', 'string', 'in:LOW,MEDIUM,HIGH,CRITICAL'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $case = $this->caseService->createCase($validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => "Kasus takedown {$case->case_number} berhasil dibuat.",
            'case' => $case->load(['investigation', 'provider', 'assignedOfficer']),
        ], 201);
    }

    /**
     * Show case details.
     */
    public function show($id): JsonResponse
    {
        $case = TakedownCase::with([
            'investigation.evidences',
            'investigation.domainRecord',
            'investigation.dnsRecords',
            'investigation.hostingRecord',
            'investigation.sslCertificate',
            'provider',
            'assignedOfficer.rank',
            'creator.rank',
            'followUps.officer.rank',
            'defensiveActions',
            'incidentChecklists.completer',
        ])->where('id', $id)
          ->orWhere('uuid', $id)
          ->orWhere('case_number', $id)
          ->firstOrFail();

        return response()->json([
            'success' => true,
            'case' => $case,
            'is_overdue' => $case->isFollowUpOverdue(),
        ]);
    }

    /**
     * Update case metadata.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $case = TakedownCase::where('id', $id)->orWhere('uuid', $id)->firstOrFail();

        $validated = $request->validate([
            'category' => ['sometimes', 'string'],
            'allegation_summary' => ['sometimes', 'string', 'min:10'],
            'legal_or_policy_basis' => ['nullable', 'string'],
            'evidence_summary' => ['nullable', 'string'],
            'selected_evidence_ids' => ['nullable', 'array'],
            'priority' => ['nullable', 'string', 'in:LOW,MEDIUM,HIGH,CRITICAL'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'external_reference_number' => ['nullable', 'string'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $case->update($validated);

        AuditLog::log('TAKEDOWN_CASE_UPDATED', 'TakedownCase', (string) $case->id, [
            'case_number' => $case->case_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rincian kasus berhasil diperbarui.',
            'case' => $case->fresh(['investigation', 'provider', 'assignedOfficer']),
        ]);
    }

    /**
     * Transition case status.
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $case = TakedownCase::where('id', $id)->orWhere('uuid', $id)->firstOrFail();

        $request->validate([
            'status' => ['required', 'string', 'in:DRAFT,READY_TO_SUBMIT,SUBMITTED,ACKNOWLEDGED,UNDER_REVIEW,ADDITIONAL_INFORMATION_REQUESTED,ACTION_TAKEN,REJECTED,ESCALATED,CLOSED,NO_RESPONSE'],
            'note' => ['nullable', 'string'],
        ]);

        $updatedCase = $this->caseService->updateStatus($case, $request->status, $request->user(), $request->note);

        return response()->json([
            'success' => true,
            'message' => "Status kasus diperbarui menjadi {$request->status}.",
            'case' => $updatedCase,
        ]);
    }

    /**
     * Add follow-up communication / ticket update.
     */
    public function addFollowUp(Request $request, $id): JsonResponse
    {
        $case = TakedownCase::where('id', $id)->orWhere('uuid', $id)->firstOrFail();

        $validated = $request->validate([
            'channel' => ['required', 'string', 'in:EMAIL,PORTAL,PHONE,WHATSAPP,API'],
            'direction' => ['required', 'string', 'in:OUTBOUND,INBOUND'],
            'ticket_number' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:5'],
            'provider_status' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240'], // 10MB
            'next_action' => ['nullable', 'string'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $followUp = $this->caseService->addFollowUp($case, $validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Catatan tindak lanjut berhasil disimpan.',
            'follow_up' => $followUp->load('officer.rank'),
            'case' => $case->fresh(),
        ], 201);
    }

    /**
     * Generate & stream Takedown Report PDF.
     */
    public function generateReport($id)
    {
        $case = TakedownCase::with(['investigation', 'provider', 'assignedOfficer.rank', 'creator.rank'])
            ->where('id', $id)->orWhere('uuid', $id)->firstOrFail();

        $result = $this->reportGenerator->generatePdf($case);

        AuditLog::log('TAKEDOWN_REPORT_DOWNLOADED', 'TakedownCase', (string) $case->id, [
            'case_number' => $case->case_number,
            'sha256' => $result['sha256'],
        ]);

        return response()->streamDownload(function () use ($result) {
            echo $result['content'];
        }, $result['filename'], [
            'Content-Type' => 'application/pdf',
            'X-Checksum-SHA256' => $result['sha256'],
        ]);
    }

    /**
     * Generate & download structured Evidence Package (ZIP + SHA-256 Manifest).
     */
    public function downloadEvidencePackage($id): BinaryFileResponse
    {
        $case = TakedownCase::with(['investigation.evidences', 'provider', 'assignedOfficer', 'creator'])
            ->where('id', $id)->orWhere('uuid', $id)->firstOrFail();

        $package = $this->packageManager->generatePackage($case);

        AuditLog::log('EVIDENCE_PACKAGE_DOWNLOADED', 'TakedownCase', (string) $case->id, [
            'case_number' => $case->case_number,
            'package_sha256' => $package['sha256'],
            'size' => $package['size'],
        ]);

        return response()->download($package['full_path'], $package['filename'], [
            'Content-Type' => 'application/zip',
            'X-Package-SHA256' => $package['sha256'],
        ]);
    }
}
