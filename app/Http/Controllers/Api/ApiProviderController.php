<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiProvider;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiProviderController extends Controller
{
    public function index(): JsonResponse
    {
        $providers = ApiProvider::all();
        return response()->json([
            'success' => true,
            'providers' => $providers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'string', 'in:dns,whois,ip_intel,reputation,screenshot,whatsapp'],
            'api_endpoint' => ['nullable', 'string', 'url'],
            'api_key' => ['nullable', 'string'],
            'secret_key' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        $provider = ApiProvider::create($data);

        AuditLog::log('API_PROVIDER_CREATED', 'ApiProvider', (string) $provider->id, [
            'name' => $provider->name,
            'service_type' => $provider->service_type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Provider API berhasil ditambahkan.',
            'provider' => $provider,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $provider = ApiProvider::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'api_endpoint' => ['nullable', 'string', 'url'],
            'api_key' => ['nullable', 'string'],
            'secret_key' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        $provider->update($data);

        AuditLog::log('API_PROVIDER_UPDATED', 'ApiProvider', (string) $provider->id);

        return response()->json([
            'success' => true,
            'message' => 'Provider API berhasil diperbarui.',
            'provider' => $provider,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $provider = ApiProvider::findOrFail($id);
        $provider->delete();

        AuditLog::log('API_PROVIDER_DELETED', 'ApiProvider', (string) $id);

        return response()->json([
            'success' => true,
            'message' => 'Provider API berhasil dihapus.',
        ]);
    }
}
