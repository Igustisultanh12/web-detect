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
    });
});
