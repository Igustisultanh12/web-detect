<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApiProviderController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvidenceController;
use App\Http\Controllers\Api\InvestigationController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\PersonnelController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public Routes
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/settings/public', [SettingController::class, 'publicSettings']);
    Route::get('/health', HealthController::class);

    // Protected Routes via Sanctum
    Route::middleware('auth:sanctum')->group(function () {

        // User & Session
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/2fa/setup', [AuthController::class, 'setupTwoFactor']);
        Route::post('/auth/2fa/enable', [AuthController::class, 'enableTwoFactor']);

        // Dashboard Metrics
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        // Investigations
        Route::get('/investigations', [InvestigationController::class, 'index']);
        Route::post('/investigations', [InvestigationController::class, 'store'])->middleware('throttle:10,1');
        Route::get('/investigations/{id}', [InvestigationController::class, 'show']);
        Route::delete('/investigations/{id}', [InvestigationController::class, 'destroy']);
        Route::get('/investigations/{id}/dns', [InvestigationController::class, 'dns']);
        Route::get('/investigations/{id}/ips', [InvestigationController::class, 'ips']);
        Route::get('/investigations/{id}/ssl', [InvestigationController::class, 'ssl']);
        Route::get('/investigations/{id}/technology', [InvestigationController::class, 'technology']);
        Route::get('/investigations/{id}/evidence', [InvestigationController::class, 'evidence']);
        Route::get('/investigations/{id}/report', [InvestigationController::class, 'report']);
        Route::post('/investigations/{id}/generate-report', [ReportController::class, 'generate']);

        // Personnel Management
        Route::get('/users', [PersonnelController::class, 'index']);
        Route::post('/users', [PersonnelController::class, 'store']);
        Route::get('/users/{uuid}', [PersonnelController::class, 'show']);
        Route::put('/users/{uuid}', [PersonnelController::class, 'update']);
        Route::post('/users/{uuid}/activate', [PersonnelController::class, 'activate']);
        Route::post('/users/{uuid}/deactivate', [PersonnelController::class, 'deactivate']);
        Route::post('/users/{uuid}/suspend', [PersonnelController::class, 'suspend']);
        Route::post('/users/{uuid}/documents', [PersonnelController::class, 'uploadDocument']);

        // Document View & Download (with Watermarking & Audit)
        Route::get('/documents/{uuid}/view', [PersonnelController::class, 'viewDocument']);
        Route::get('/documents/{uuid}/download', [PersonnelController::class, 'downloadDocument']);

        // Master Data Dropdowns
        Route::get('/master/personnel-options', [MasterDataController::class, 'getPersonnelOptions']);

        // Evidence Vault
        Route::get('/evidence', [EvidenceController::class, 'index']);
        Route::get('/evidence/{uuid}', [EvidenceController::class, 'show']);
        Route::post('/evidence/{uuid}/notes', [EvidenceController::class, 'addNote']);
        Route::get('/evidence/{uuid}/verify', [EvidenceController::class, 'verifyIntegrity']);

        // Reports
        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/{uuid}/download', [ReportController::class, 'download']);

        // Security & Audit Logs
        Route::get('/security/dashboard', [SecurityController::class, 'dashboard']);
        Route::get('/security/login-activity', [SecurityController::class, 'loginActivity']);
        Route::get('/security/events', [SecurityController::class, 'securityEvents']);
        Route::get('/security/audit-logs', [SecurityController::class, 'auditLogs']);

        // Settings & API Providers
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'update']);
        Route::apiResource('providers', ApiProviderController::class);

        // Takedown & Incident Response Management
        Route::prefix('takedown')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\Takedown\TakedownDashboardController::class, 'index']);

            // Cases
            Route::get('/cases', [\App\Http\Controllers\Api\Takedown\TakedownCaseController::class, 'index']);
            Route::post('/cases', [\App\Http\Controllers\Api\Takedown\TakedownCaseController::class, 'store']);
            Route::get('/cases/{id}', [\App\Http\Controllers\Api\Takedown\TakedownCaseController::class, 'show']);
            Route::put('/cases/{id}', [\App\Http\Controllers\Api\Takedown\TakedownCaseController::class, 'update']);
            Route::post('/cases/{id}/status', [\App\Http\Controllers\Api\Takedown\TakedownCaseController::class, 'updateStatus']);
            Route::post('/cases/{id}/follow-ups', [\App\Http\Controllers\Api\Takedown\TakedownCaseController::class, 'addFollowUp']);
            Route::get('/cases/{id}/report', [\App\Http\Controllers\Api\Takedown\TakedownCaseController::class, 'generateReport']);
            Route::get('/cases/{id}/download-package', [\App\Http\Controllers\Api\Takedown\TakedownCaseController::class, 'downloadEvidencePackage']);

            // Provider Directory
            Route::get('/providers', [\App\Http\Controllers\Api\Takedown\TakedownProviderController::class, 'index']);
            Route::post('/providers', [\App\Http\Controllers\Api\Takedown\TakedownProviderController::class, 'store']);
            Route::get('/providers/{id}', [\App\Http\Controllers\Api\Takedown\TakedownProviderController::class, 'show']);
            Route::put('/providers/{id}', [\App\Http\Controllers\Api\Takedown\TakedownProviderController::class, 'update']);
        });

        // Defensive Technical Actions (Internal Only)
        Route::prefix('defensive-actions')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Takedown\DefensiveActionController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\Takedown\DefensiveActionController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\Api\Takedown\DefensiveActionController::class, 'show']);
            Route::post('/{id}/review', [\App\Http\Controllers\Api\Takedown\DefensiveActionController::class, 'review']);
            Route::post('/{id}/approve', [\App\Http\Controllers\Api\Takedown\DefensiveActionController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\Takedown\DefensiveActionController::class, 'reject']);
            Route::post('/{id}/deploy', [\App\Http\Controllers\Api\Takedown\DefensiveActionController::class, 'markDeployed']);
            Route::post('/{id}/checklists/{checklistId}/toggle', [\App\Http\Controllers\Api\Takedown\DefensiveActionController::class, 'toggleChecklistItem']);
            Route::get('/{id}/export', [\App\Http\Controllers\Api\Takedown\DefensiveActionController::class, 'exportRule']);
        });
    });
});
