<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Evidence;
use App\Models\HostingRecord;
use App\Models\Investigation;
use App\Models\Report;
use App\Models\ReputationResult;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard aggregated metrics and chart statistics.
     */
    public function stats(): JsonResponse
    {
        $totalInvestigations = Investigation::count();
        $completed = Investigation::where('status', 'COMPLETED')->count();
        $running = Investigation::whereIn('status', ['QUEUED', 'ANALYZING'])->count();
        $failed = Investigation::where('status', 'FAILED')->count();

        // Reputation counts
        $suspicious = ReputationResult::where('status', 'SUSPICIOUS')->distinct('investigation_id')->count('investigation_id');
        $malicious = ReputationResult::where('status', 'MALICIOUS')->distinct('investigation_id')->count('investigation_id');
        $clean = ReputationResult::where('status', 'CLEAN')->distinct('investigation_id')->count('investigation_id');

        $totalEvidence = Evidence::count();
        $totalReports = Report::count();
        $totalPersonnel = User::count();
        $activePersonnel = User::where('status', 'ACTIVE')->count();

        // Category breakdown
        $categories = Investigation::select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        // Top hosting countries
        $topCountries = HostingRecord::select('country', DB::raw('count(*) as total'))
            ->whereNotNull('country')
            ->where('country', '!=', 'Unknown')
            ->groupBy('country')
            ->orderByDesc('total')
            ->take(6)
            ->get();

        // Top ASNs
        $topAsn = DB::table('asn_records')
            ->select('asn', 'asn_org', DB::raw('count(*) as total'))
            ->whereNotNull('asn')
            ->groupBy('asn', 'asn_org')
            ->orderByDesc('total')
            ->take(6)
            ->get();

        // Recent investigations
        $recentInvestigations = Investigation::with(['user.rank', 'domainRecord'])
            ->latest('id')
            ->take(6)
            ->get();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_investigations' => $totalInvestigations,
                'completed' => $completed,
                'running' => $running,
                'failed' => $failed,
                'suspicious' => $suspicious + $malicious,
                'clean' => $clean,
                'total_evidence' => $totalEvidence,
                'total_reports' => $totalReports,
                'total_personnel' => $totalPersonnel,
                'active_personnel' => $activePersonnel,
            ],
            'categories' => $categories,
            'top_countries' => $topCountries,
            'top_asn' => $topAsn,
            'recent_investigations' => $recentInvestigations,
        ]);
    }
}
