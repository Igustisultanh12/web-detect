<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'settings' => Setting::all()->pluck('value', 'key'),
        ]);
    }

    public function publicSettings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'app_name' => Setting::get('app_name', 'WebGuard Investigasi'),
            'app_tagline' => Setting::get('app_tagline', 'Platform Investigasi & Pelaporan Siber'),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        foreach ($data['settings'] as $key => $val) {
            Setting::set($key, $val);
        }

        AuditLog::log('SETTINGS_UPDATED', 'Setting', null, ['keys' => array_keys($data['settings'])]);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan sistem berhasil disimpan.',
        ]);
    }
}
