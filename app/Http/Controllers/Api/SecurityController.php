<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    /**
     * Security posture and metrics overview.
     */
    public function dashboard(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'ACTIVE')->count(),
            'inactive_users' => User::where('status', 'INACTIVE')->count(),
            'suspended_users' => User::where('status', 'SUSPENDED')->count(),
            'two_factor_enabled' => User::where('two_factor_enabled', true)->count(),
            'two_factor_disabled' => User::where('two_factor_enabled', false)->count(),
            'failed_logins_24h' => LoginAttempt::where('status', 'FAILED')->where('created_at', '>=', now()->subDay())->count(),
            'total_security_events' => SecurityEvent::count(),
            'critical_security_events' => SecurityEvent::where('severity', 'CRITICAL')->count(),
            'recent_events' => SecurityEvent::latest('created_at')->take(8)->get(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Login attempts history.
     */
    public function loginActivity(Request $request): JsonResponse
    {
        $query = LoginAttempt::orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', "%{$request->email}%");
        }

        $attempts = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $attempts->items(),
            'meta' => [
                'current_page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
                'total' => $attempts->total(),
            ],
        ]);
    }

    /**
     * Security events log.
     */
    public function securityEvents(Request $request): JsonResponse
    {
        $events = SecurityEvent::with('user.rank')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    /**
     * Comprehensive system audit trail.
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::with('user.rank')->orderByDesc('id');

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('action', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%")
                  ->orWhere('target_type', 'like', "%{$s}%");
            });
        }

        $logs = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
