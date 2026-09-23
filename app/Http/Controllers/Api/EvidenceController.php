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

    /**
     * Serve raw evidence / screenshot visual payload for browser iframe previews.
     */
    public function renderRaw(Request $request, $identifier)
    {
        if ($request->expectsJson() && !$request->has('raw') && !$request->has('view')) {
            return $this->show($identifier);
        }

        // 1. Look for Screenshot by sha256 or uuid
        $screenshot = \App\Models\Screenshot::where('sha256', $identifier)
            ->orWhere('uuid', $identifier)
            ->first();

        if ($screenshot) {
            $storagePath = storage_path('app/' . $screenshot->file_path);
            if (file_exists($storagePath)) {
                $mime = str_ends_with($screenshot->file_path, '.svg') ? 'image/svg+xml' : 'image/png';
                return response()->file($storagePath, ['Content-Type' => $mime]);
            }
        }

        // 2. Look for Evidence by sha256, uuid, or evidence_code
        $evidence = Evidence::where('sha256', $identifier)
            ->orWhere('uuid', $identifier)
            ->orWhere('evidence_code', $identifier)
            ->first();

        if ($evidence) {
            if ($evidence->type === 'Screenshot') {
                $parsed = is_array($evidence->parsed_data) ? $evidence->parsed_data : json_decode($evidence->raw_data, true);
                if (!empty($parsed['file_path'])) {
                    $storagePath = storage_path('app/' . $parsed['file_path']);
                    if (file_exists($storagePath)) {
                        $mime = str_ends_with($parsed['file_path'], '.svg') ? 'image/svg+xml' : 'image/png';
                        return response()->file($storagePath, ['Content-Type' => $mime]);
                    }
                }
            }

            return response($evidence->raw_data, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        // 3. Fallback placeholder SVG
        $safeId = htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8');
        $fallbackSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1280 800" width="1280" height="800">
  <rect width="1280" height="800" fill="#0F172A" />
  <rect x="0" y="0" width="1280" height="42" fill="#1E293B" />
  <circle cx="24" cy="21" r="6" fill="#EF4444" />
  <circle cx="44" cy="21" r="6" fill="#F59E0B" />
  <circle cx="64" cy="21" r="6" fill="#10B981" />
  <text x="640" y="390" fill="#94A3B8" font-family="sans-serif" font-size="22" font-weight="bold" text-anchor="middle">
    Bukti Visual Forensik Digital
  </text>
  <text x="640" y="430" fill="#64748B" font-family="monospace" font-size="14" text-anchor="middle">
    Hash SHA-256: {$safeId}
  </text>
  <text x="640" y="470" fill="#3B82F6" font-family="sans-serif" font-size="13" font-weight="600" text-anchor="middle">
    WebGuard Isolated Browser Capture
  </text>
</svg>
SVG;
        return response($fallbackSvg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
