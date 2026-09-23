<?php

namespace App\Http\Controllers\Api\Takedown;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DefensiveAction;
use App\Models\IncidentChecklist;
use App\Services\Takedown\DefensiveActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DefensiveActionController extends Controller
{
    public function __construct(protected DefensiveActionService $defensiveService) {}

    /**
     * List internal defensive rules and actions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DefensiveAction::with(['recommender.rank', 'reviewer.rank', 'approver.rank', 'investigation', 'takedownCase'])
            ->withCount(['checklists']);

        if ($request->filled('rule_type')) {
            $query->where('rule_type', $request->rule_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('action_code', 'like', "%{$s}%")
                  ->orWhere('title', 'like', "%{$s}%")
                  ->orWhere('target_value', 'like', "%{$s}%");
            });
        }

        $actions = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $actions->items(),
            'meta' => [
                'current_page' => $actions->currentPage(),
                'last_page' => $actions->lastPage(),
                'total' => $actions->total(),
            ],
        ]);
    }

    /**
     * Store new defensive rule recommendation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'investigation_id' => ['nullable', 'exists:investigations,id'],
            'takedown_case_id' => ['nullable', 'exists:takedown_cases,id'],
            'rule_type' => ['required', 'string', 'in:INTERNAL_BLOCKLIST,IOC_LIST,FIREWALL_RULE,WAF_RULE,DNS_SINKHOLE,EMAIL_FILTER,PROXY_BLOCK,SIEM_RULE,INCIDENT_CHECKLIST'],
            'target_type' => ['required', 'string', 'in:DOMAIN,IP,URL,SUBNET,HASH'],
            'target_value' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scope' => ['nullable', 'string', 'in:INTERNAL_NETWORK,CORPORATE_FIREWALL,LOCAL_DNS_RESOLVER,INTERNAL_MAIL_GATEWAY'],
        ]);

        // Automatically generate rule syntax payload
        $rulePayload = $this->defensiveService->generateRulePayload(
            $validated['rule_type'],
            $validated['target_type'],
            $validated['target_value'],
            $validated['description'] ?? null
        );

        $action = DefensiveAction::create(array_merge($validated, [
            'scope' => $validated['scope'] ?? 'INTERNAL_NETWORK',
            'rule_payload' => $rulePayload,
            'status' => 'PENDING_REVIEW',
            'recommended_by' => $request->user()->id,
        ]));

        // Attach default 8-step incident response checklist
        $this->defensiveService->attachDefaultChecklist($action);

        AuditLog::log('DEFENSIVE_ACTION_CREATED', 'DefensiveAction', (string) $action->id, [
            'action_code' => $action->action_code,
            'rule_type' => $action->rule_type,
            'target' => $action->target_value,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Rekomendasi aksi defensif {$action->action_code} berhasil dibuat untuk peninjauan.",
            'action' => $action->load(['recommender', 'checklists']),
        ], 201);
    }

    /**
     * Show single defensive action with checklist.
     */
    public function show($id): JsonResponse
    {
        $action = DefensiveAction::with([
            'recommender.rank',
            'reviewer.rank',
            'approver.rank',
            'investigation',
            'takedownCase',
            'checklists.completer.rank',
        ])->where('id', $id)->orWhere('uuid', $id)->orWhere('action_code', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'action' => $action,
        ]);
    }

    /**
     * Admin review action -> move to PENDING_APPROVAL.
     */
    public function review(Request $request, $id): JsonResponse
    {
        $action = DefensiveAction::where('id', $id)->orWhere('uuid', $id)->firstOrFail();
        $this->defensiveService->reviewAction($action, $request->user());

        return response()->json([
            'success' => true,
            'message' => "Rekomendasi {$action->action_code} telah ditinjau dan diteruskan untuk persetujuan Super Admin.",
            'action' => $action->fresh(),
        ]);
    }

    /**
     * Super Admin approve action -> move to APPROVED.
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Super Administrator yang berwenang menyetujui penerapan aksi defensif.',
            ], 403);
        }

        $action = DefensiveAction::where('id', $id)->orWhere('uuid', $id)->firstOrFail();
        $this->defensiveService->approveAction($action, $user);

        return response()->json([
            'success' => true,
            'message' => "Aksi defensif {$action->action_code} telah disetujui untuk diterapkan di infrastruktur internal.",
            'action' => $action->fresh(),
        ]);
    }

    /**
     * Reject action with reason.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:5']]);

        $action = DefensiveAction::where('id', $id)->orWhere('uuid', $id)->firstOrFail();
        $action->update([
            'status' => 'REJECTED',
            'rejection_reason' => $request->reason,
        ]);

        AuditLog::log('DEFENSIVE_ACTION_REJECTED', 'DefensiveAction', (string) $action->id, [
            'action_code' => $action->action_code,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Aksi defensif {$action->action_code} ditolak.",
            'action' => $action->fresh(),
        ]);
    }

    /**
     * Mark deployed internally by network administrator.
     */
    public function markDeployed(Request $request, $id): JsonResponse
    {
        $action = DefensiveAction::where('id', $id)->orWhere('uuid', $id)->firstOrFail();

        if ($action->status !== 'APPROVED') {
            return response()->json([
                'success' => false,
                'message' => 'Aturan harus disetujui (APPROVED) terlebih dahulu sebelum dapat ditandai telah diterapkan.',
            ], 422);
        }

        $this->defensiveService->markDeployed($action, $request->user());

        return response()->json([
            'success' => true,
            'message' => "Aturan {$action->action_code} berhasil ditandai telah aktif di infrastruktur internal.",
            'action' => $action->fresh(),
        ]);
    }

    /**
     * Toggle checklist item status.
     */
    public function toggleChecklistItem(Request $request, $id, $checklistId): JsonResponse
    {
        $item = IncidentChecklist::where('id', $checklistId)
            ->where('defensive_action_id', $id)
            ->firstOrFail();

        $newState = !$item->is_completed;
        $item->update([
            'is_completed' => $newState,
            'completed_by' => $newState ? $request->user()->id : null,
            'completed_at' => $newState ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'checklist_item' => $item->load('completer.rank'),
        ]);
    }

    /**
     * Export raw rule payload as downloadable configuration file.
     */
    public function exportRule($id)
    {
        $action = DefensiveAction::where('id', $id)->orWhere('uuid', $id)->firstOrFail();

        $ext = match ($action->rule_type) {
            'SIEM_RULE' => 'yml',
            'IOC_LIST' => 'json',
            'WAF_RULE', 'DNS_SINKHOLE', 'FIREWALL_RULE' => 'conf',
            default => 'txt',
        };

        $filename = "{$action->action_code}-{$action->rule_type}.{$ext}";

        return response($action->rule_payload, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
