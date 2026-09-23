<?php

namespace App\Http\Controllers\Api\Takedown;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TakedownProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TakedownProviderController extends Controller
{
    /**
     * List all abuse & regulatory providers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TakedownProvider::query()->withCount('takedownCases');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('abuse_email', 'like', "%{$s}%")
                  ->orWhere('website', 'like', "%{$s}%")
                  ->orWhere('type', 'like', "%{$s}%");
            });
        }

        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        $providers = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'providers' => $providers,
        ]);
    }

    /**
     * Store new provider in directory.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:takedown_providers,name'],
            'type' => ['required', 'string'],
            'website' => ['nullable', 'url', 'max:255'],
            'abuse_email' => ['nullable', 'email', 'max:255'],
            'abuse_url' => ['nullable', 'url', 'max:255'],
            'api_endpoint' => ['nullable', 'url', 'max:255'],
            'report_types' => ['nullable', 'array'],
            'requirements' => ['nullable', 'string'],
            'sla_hours' => ['nullable', 'integer', 'min:1'],
            'integration_status' => ['nullable', 'string', 'in:MANUAL,API_AVAILABLE,API_ACTIVE'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $provider = TakedownProvider::create($validated);

        AuditLog::log('PROVIDER_CREATED', 'TakedownProvider', (string) $provider->id, [
            'name' => $provider->name,
            'type' => $provider->type,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Provider {$provider->name} berhasil ditambahkan ke direktori.",
            'provider' => $provider,
        ], 201);
    }

    /**
     * Show single provider details.
     */
    public function show($id): JsonResponse
    {
        $provider = TakedownProvider::with(['takedownCases' => fn($q) => $q->latest()->take(10)])
            ->where('id', $id)->orWhere('uuid', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'provider' => $provider,
        ]);
    }

    /**
     * Update provider.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $provider = TakedownProvider::where('id', $id)->orWhere('uuid', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:takedown_providers,name,' . $provider->id],
            'type' => ['sometimes', 'string'],
            'website' => ['nullable', 'url'],
            'abuse_email' => ['nullable', 'email'],
            'abuse_url' => ['nullable', 'url'],
            'api_endpoint' => ['nullable', 'url'],
            'report_types' => ['nullable', 'array'],
            'requirements' => ['nullable', 'string'],
            'sla_hours' => ['nullable', 'integer', 'min:1'],
            'integration_status' => ['nullable', 'string', 'in:MANUAL,API_AVAILABLE,API_ACTIVE'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $provider->update($validated);

        AuditLog::log('PROVIDER_UPDATED', 'TakedownProvider', (string) $provider->id, [
            'name' => $provider->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data provider berhasil diperbarui.',
            'provider' => $provider,
        ]);
    }
}
