<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Evidence;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvidenceController extends Controller
{
    public function __construct(protected EvidenceManagerService $evidenceManager) {}

    public function index(Request $request): JsonResponse
    {
        $query = Evidence::with(['investigation', 'creator.rank'])->withCount('versions');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('evidence_code', 'like', "%{$s}%")
                  ->orWhere('sha256', 'like', "%{$s}%")
                  ->orWhere('type', 'like', "%{$s}%")
                  ->orWhere('source', 'like', "%{$s}%");
            });
        }

        $evidences = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $evidences->items(),
            'meta' => [
                'current_page' => $evidences->currentPage(),
                'last_page' => $evidences->lastPage(),
                'per_page' => $evidences->perPage(),
                'total' => $evidences->total(),
            ],
        ]);
    }

    public function show($uuid): JsonResponse
    {
        $evidence = Evidence::with(['investigation', 'creator.rank', 'versions.updater'])
            ->where('uuid', $uuid)
            ->orWhere('id', $uuid)
            ->orWhere('evidence_code', $uuid)
            ->firstOrFail();

        $isVerified = $this->evidenceManager->verifyIntegrity($evidence);

        return response()->json([
            'success' => true,
            'evidence' => $evidence,
            'integrity_verified' => $isVerified,
        ]);
    }

    public function addNote(Request $request, $uuid): JsonResponse
    {
        $request->validate(['notes' => ['required', 'string', 'min:3']]);
        $evidence = Evidence::where('uuid', $uuid)->orWhere('id', $uuid)->firstOrFail();

        $version = $this->evidenceManager->addNote($evidence, $request->notes);

        AuditLog::log('EVIDENCE_NOTE_ADDED', 'Evidence', (string) $evidence->id, [
            'version' => $version->version,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catatan bukti berhasil ditambahkan sebagai versi baru.',
            'version' => $version,
        ]);
    }

    public function verifyIntegrity($uuid): JsonResponse
    {
        $evidence = Evidence::where('uuid', $uuid)->orWhere('id', $uuid)->firstOrFail();
        $valid = $this->evidenceManager->verifyIntegrity($evidence);

        return response()->json([
            'success' => true,
            'evidence_code' => $evidence->evidence_code,
            'stored_hash' => $evidence->sha256,
            'computed_hash' => hash('sha256', $evidence->raw_data),
            'is_valid' => $valid,
            'message' => $valid ? 'Integritas barang bukti digital valid dan tidak termodifikasi.' : 'PERINGATAN: Integritas bukti tidak cocok!',
        ]);
    }
}
