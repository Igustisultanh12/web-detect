<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvestigationRequest;
use App\Jobs\ProcessInvestigationJob;
use App\Models\AuditLog;
use App\Models\Investigation;
use App\Services\Analysis\InvestigationWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestigationController extends Controller
{
    /**
     * List investigations with pagination, search, status and category filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Investigation::with(['user.rank', 'domainRecord'])
            ->withCount(['evidences', 'dnsRecords', 'ipAddresses', 'subdomains']);

        // Non-admin can only see own investigations unless they have permission
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('investigation_code', 'like', "%{$s}%")
                  ->orWhere('target_domain', 'like', "%{$s}%")
                  ->orWhere('target_url', 'like', "%{$s}%");
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

        $investigations = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $investigations->items(),
            'meta' => [
                'current_page' => $investigations->currentPage(),
                'last_page' => $investigations->lastPage(),
                'per_page' => $investigations->perPage(),
                'total' => $investigations->total(),
            ],
        ]);
    }

    /**
     * Create and queue a new passive website investigation.
     */
    public function store(StoreInvestigationRequest $request, InvestigationWorkflowService $workflowService): JsonResponse
    {
        $user = $request->user();
        $url = trim($request->target_url);

        // Normalize domain preview
        $host = parse_url($url, PHP_URL_HOST) ?: parse_url('https://' . $url, PHP_URL_HOST);

        $investigation = Investigation::create([
            'user_id' => $user->id,
            'target_url' => $url,
            'target_domain' => strtolower($host ?: $url),
            'category' => $request->category ?: 'Suspicious Domain',
            'priority' => $request->priority ?: 'MEDIUM',
            'reason' => $request->reason,
            'tags' => $request->tags ?: [],
            'status' => 'QUEUED',
        ]);

        $investigation->addTimeline('INVESTIGATION_QUEUED', "Investigasi dijadwalkan ke dalam antrean (Queue) oleh {$user->name}.", 'INFO');

        AuditLog::log('INVESTIGATION_CREATED', 'Investigation', (string) $investigation->id, [
            'code' => $investigation->investigation_code,
            'target_domain' => $investigation->target_domain,
            'target_url' => $investigation->target_url,
        ]);

        // Process directly or dispatch to queue
        if (config('queue.default') === 'sync' || $request->boolean('sync', false)) {
            $workflowService->process($investigation);
        } else {
            // Queue via Redis / database queue
            dispatch(new ProcessInvestigationJob($investigation));
            // Also trigger initial background step if worker is not yet running
            rescue(fn() => $workflowService->process($investigation));
        }

        return response()->json([
            'success' => true,
            'message' => 'Investigasi berhasil dibuat dan dijadwalkan.',
            'investigation' => $investigation->fresh(),
        ], 201);
    }

    /**
     * Show full investigation details across all 15 analysis tabs.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $investigation = Investigation::with([
            'user.rank',
            'user.unit',
            'user.position',
            'domainRecord',
            'dnsRecords',
            'ipAddresses',
            'asnRecords',
            'hostingRecords',
            'sslCertificate',
            'httpResult',
            'technologies',
            'subdomains',
            'reputationResults',
            'screenshots',
            'evidences.versions',
            'timeline',
            'reports',
        ])->where('id', $id)
          ->orWhere('investigation_code', $id)
          ->orWhere('uuid', $id)
          ->firstOrFail();

        AuditLog::log('INVESTIGATION_VIEWED', 'Investigation', (string) $investigation->id, [
            'code' => $investigation->investigation_code,
        ]);

        return response()->json([
            'success' => true,
            'investigation' => $investigation,
        ]);
    }

    public function dns($id): JsonResponse
    {
        $inv = $this->findInvestigation($id);
        return response()->json(['success' => true, 'data' => $inv->dnsRecords]);
    }

    public function ips($id): JsonResponse
    {
        $inv = $this->findInvestigation($id);
        return response()->json([
            'success' => true,
            'ip_addresses' => $inv->ipAddresses,
            'asn_records' => $inv->asnRecords,
            'hosting_records' => $inv->hostingRecords,
        ]);
    }

    public function ssl($id): JsonResponse
    {
        $inv = $this->findInvestigation($id);
        return response()->json(['success' => true, 'data' => $inv->sslCertificate]);
    }

    public function technology($id): JsonResponse
    {
        $inv = $this->findInvestigation($id);
        return response()->json(['success' => true, 'data' => $inv->technologies]);
    }

    public function evidence($id): JsonResponse
    {
        $inv = $this->findInvestigation($id);
        return response()->json(['success' => true, 'data' => $inv->evidences]);
    }

    public function report($id): JsonResponse
    {
        $inv = $this->findInvestigation($id);
        return response()->json(['success' => true, 'data' => $inv->reports]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $inv = $this->findInvestigation($id);
        $user = $request->user();

        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki izin untuk menghapus investigasi.'], 403);
        }

        $inv->delete();
        AuditLog::log('INVESTIGATION_DELETED', 'Investigation', (string) $id);

        return response()->json(['success' => true, 'message' => 'Investigasi berhasil dihapus.']);
    }

    protected function findInvestigation($id): Investigation
    {
        return Investigation::where('id', $id)
            ->orWhere('investigation_code', $id)
            ->orWhere('uuid', $id)
            ->firstOrFail();
    }
}
