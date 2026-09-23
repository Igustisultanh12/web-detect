<?php

namespace App\Http\Controllers\Api\Takedown;

use App\Http\Controllers\Controller;
use App\Models\DefensiveAction;
use App\Models\Evidence;
use App\Models\Investigation;
use App\Models\TakedownCase;
use App\Models\TakedownProvider;
use Illuminate\Http\JsonResponse;

class TakedownDashboardController extends Controller
{
    /**
     * Provide comprehensive operational metrics for Takedown & Incident Response.
     */
    public function index(): JsonResponse
    {
        $totalCases = TakedownCase::count();
        $totalInvestigations = Investigation::count();
        $activeInvestigations = Investigation::whereIn('status', ['ANALYZING', 'QUEUED', 'PENDING'])->count();

        // Status counts
        $statusCounts = [
            'draft' => TakedownCase::where('status', 'DRAFT')->count(),
            'ready_to_submit' => TakedownCase::where('status', 'READY_TO_SUBMIT')->count(),
            'submitted' => TakedownCase::where('status', 'SUBMITTED')->count(),
            'acknowledged' => TakedownCase::where('status', 'ACKNOWLEDGED')->count(),
            'under_review' => TakedownCase::where('status', 'UNDER_REVIEW')->count(),
            'additional_info' => TakedownCase::where('status', 'ADDITIONAL_INFORMATION_REQUESTED')->count(),
            'action_taken' => TakedownCase::where('status', 'ACTION_TAKEN')->count(),
            'rejected' => TakedownCase::where('status', 'REJECTED')->count(),
            'escalated' => TakedownCase::where('status', 'ESCALATED')->count(),
            'closed' => TakedownCase::where('status', 'CLOSED')->count(),
            'no_response' => TakedownCase::where('status', 'NO_RESPONSE')->count(),
        ];

        // Overdue follow-ups
        $overdueCases = TakedownCase::with(['provider', 'assignedOfficer.rank'])
            ->where('next_follow_up_at', '<', now())
            ->whereNotIn('status', ['ACTION_TAKEN', 'CLOSED', 'RESOLVED', 'REJECTED'])
            ->orderBy('next_follow_up_at')
            ->take(8)
            ->get();

        // Provider case distribution
        $providerStats = TakedownProvider::withCount('takedownCases')
            ->orderByDesc('takedown_cases_count')
            ->take(6)
            ->get()
            ->map(fn($p) => [
                'name' => $p->name,
                'type' => $p->type,
                'sla_hours' => $p->sla_hours,
                'cases_count' => $p->takedown_cases_count,
            ]);

        // Defensive actions summary
        $defensiveSummary = [
            'total' => DefensiveAction::count(),
            'pending_approval' => DefensiveAction::where('status', 'PENDING_APPROVAL')->count(),
            'approved' => DefensiveAction::where('status', 'APPROVED')->count(),
            'deployed' => DefensiveAction::where('status', 'DEPLOYED_INTERNALLY')->count(),
        ];

        // Recent cases
        $recentCases = TakedownCase::with(['assignedOfficer.rank', 'provider'])
            ->latest()
            ->take(6)
            ->get();

        return response()->json([
            'success' => true,
            'metrics' => [
                'total_investigations' => $totalInvestigations,
                'active_investigations' => $activeInvestigations,
                'total_cases' => $totalCases,
                'status_distribution' => $statusCounts,
                'overdue_count' => $overdueCases->count(),
                'overdue_cases' => $overdueCases,
                'provider_stats' => $providerStats,
                'evidence_count' => Evidence::count(),
                'defensive_summary' => $defensiveSummary,
                'recent_cases' => $recentCases,
            ],
        ]);
    }
}
