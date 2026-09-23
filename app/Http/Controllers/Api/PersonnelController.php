<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePersonnelRequest;
use App\Models\AuditLog;
use App\Models\Rank;
use App\Models\Role;
use App\Models\Unit;
use App\Models\Position;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\Personnel\PersonnelService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PersonnelController extends Controller
{
    public function __construct(protected PersonnelService $personnelService) {}

    /**
     * List personnel with comprehensive search, filters, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['roles', 'rank', 'unit', 'position'])
            ->withCount('investigations');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('nrp', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('whatsapp_number', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('rank_id')) {
            $query->where('rank_id', $request->rank_id);
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }

        $users = $query->orderBy('name')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Store new personnel.
     */
    public function store(StorePersonnelRequest $request): JsonResponse
    {
        $password = $request->password ?: Str::random(16) . 'A1!';

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => $request->name,
            'nrp' => $request->nrp,
            'rank_id' => $request->rank_id,
            'position_id' => $request->position_id,
            'unit_id' => $request->unit_id,
            'phone' => $request->phone,
            'whatsapp_number' => $request->whatsapp_number,
            'email' => $request->email,
            'password' => Hash::make($password),
            'status' => 'ACTIVE',
            'active_from' => $request->active_from ?: now(),
            'activated_by_admin_at' => now(),
            'activated_by_admin_id' => auth()->id(),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($request->role);

        AuditLog::log('USER_CREATED', 'User', (string) $user->id, [
            'name' => $user->name,
            'nrp' => $user->nrp,
            'email' => $user->email,
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data personel berhasil ditambahkan.',
            'user' => $user->load(['roles', 'rank', 'unit', 'position']),
        ], 201);
    }

    /**
     * Show personnel details with tabs.
     */
    public function show($uuid): JsonResponse
    {
        $user = User::with([
            'roles.permissions',
            'rank',
            'unit',
            'position',
            'profile',
            'documents.versions',
            'userSessions',
            'investigations' => fn($q) => $q->latest()->take(10),
            'auditLogs' => fn($q) => $q->latest('created_at')->take(20),
        ])->where('uuid', $uuid)
          ->orWhere('id', $uuid)
          ->firstOrFail();

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    /**
     * Update personnel data.
     */
    public function update(Request $request, $uuid): JsonResponse
    {
        $user = User::where('uuid', $uuid)->orWhere('id', $uuid)->firstOrFail();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nrp' => ['nullable', 'string', 'max:64', 'unique:users,nrp,' . $user->id],
            'rank_id' => ['nullable', 'exists:ranks,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp_number' => ['nullable', 'string', 'max:32'],
            'role' => ['nullable', 'string', 'in:super_admin,admin,investigator,viewer'],
        ]);

        $user->update($request->only([
            'name', 'nrp', 'rank_id', 'position_id', 'unit_id', 'phone', 'whatsapp_number',
        ]));

        if ($request->filled('role')) {
            $user->roles()->detach();
            $user->assignRole($request->role);
        }

        AuditLog::log('USER_UPDATED', 'User', (string) $user->id, [
            'name' => $user->name,
            'nrp' => $user->nrp,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data personel berhasil diperbarui.',
            'user' => $user->fresh(['roles', 'rank', 'unit', 'position']),
        ]);
    }

    /**
     * Activate personnel account.
     */
    public function activate($uuid): JsonResponse
    {
        $user = User::where('uuid', $uuid)->orWhere('id', $uuid)->firstOrFail();
        $this->personnelService->activateUser($user);

        return response()->json([
            'success' => true,
            'message' => "Akun personel {$user->name} berhasil diaktifkan.",
        ]);
    }

    /**
     * Deactivate personnel account with mandatory reason.
     */
    public function deactivate(Request $request, $uuid): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:5'],
        ]);

        $user = User::where('uuid', $uuid)->orWhere('id', $uuid)->firstOrFail();
        $this->personnelService->deactivateUser($user, $request->reason);

        return response()->json([
            'success' => true,
            'message' => "Akun personel {$user->name} dinonaktifkan.",
        ]);
    }

    /**
     * Suspend personnel account.
     */
    public function suspend(Request $request, $uuid): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:5'],
        ]);

        $user = User::where('uuid', $uuid)->orWhere('id', $uuid)->firstOrFail();
        $this->personnelService->suspendUser($user, $request->reason);

        return response()->json([
            'success' => true,
            'message' => "Akun personel {$user->name} ditangguhkan.",
        ]);
    }

    /**
     * Upload KTP / KTA securely.
     */
    public function uploadDocument(Request $request, $uuid): JsonResponse
    {
        $request->validate([
            'document' => ['required', 'file', 'max:5120'], // 5MB max
            'document_type' => ['required', 'string', 'in:KTP,KTA'],
        ]);

        $user = User::where('uuid', $uuid)->orWhere('id', $uuid)->firstOrFail();
        $doc = $this->personnelService->uploadDocument($user, $request->file('document'), $request->document_type);

        return response()->json([
            'success' => true,
            'message' => "Dokumen {$request->document_type} berhasil diunggah secara aman.",
            'document' => $doc,
        ]);
    }

    /**
     * View KTP / KTA preview with confidential watermark.
     */
    public function viewDocument(Request $request, $documentUuid)
    {
        $authUser = $request->user();

        // Strict authorization
        if (!$authUser->hasPermission('user.document.view')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak (403 Forbidden). Anda tidak memiliki izin untuk melihat dokumen identitas sensitif.',
            ], 403);
        }

        $doc = UserDocument::with('user')->where('uuid', $documentUuid)->orWhere('id', $documentUuid)->firstOrFail();

        AuditLog::log('DOCUMENT_VIEWED', 'UserDocument', (string) $doc->id, [
            'document_type' => $doc->document_type,
            'target_user_id' => $doc->user_id,
            'target_user_name' => $doc->user->name,
        ]);

        $filePath = Storage::disk('local')->path($doc->file_path);

        if (!file_exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'Berkas tidak ditemukan pada private storage.'], 404);
        }

        // Return with security headers prohibiting caching
        return response()->file($filePath, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Download original KTP / KTA with strict audit log.
     */
    public function downloadDocument(Request $request, $documentUuid)
    {
        $authUser = $request->user();

        if (!$authUser->hasPermission('user.document.download')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak (403 Forbidden). Anda tidak memiliki izin untuk mengunduh dokumen identitas.',
            ], 403);
        }

        $doc = UserDocument::with('user')->where('uuid', $documentUuid)->orWhere('id', $documentUuid)->firstOrFail();

        AuditLog::log('DOCUMENT_DOWNLOADED', 'UserDocument', (string) $doc->id, [
            'document_type' => $doc->document_type,
            'target_user_id' => $doc->user_id,
            'target_user_name' => $doc->user->name,
            'sha256' => $doc->sha256,
        ]);

        $filePath = Storage::disk('local')->path($doc->file_path);

        return response()->download($filePath, $doc->original_filename, [
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
