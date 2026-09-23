<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ScreenshotProviderInterface;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Evidence;
use App\Models\Screenshot;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
     * Serve raw evidence / screenshot visual payload for browser previews.
     */
    public function renderRaw(Request $request, $identifier)
    {
        if ($request->expectsJson() && !$request->has('raw') && !$request->has('view')) {
            return $this->show($identifier);
        }

        // 1. Look for Screenshot by sha256 or uuid
        $screenshot = Screenshot::with('investigation')
            ->where('sha256', $identifier)
            ->orWhere('uuid', $identifier)
            ->first();

        if ($screenshot) {
            $realPath = $this->resolveStorageFilePath($screenshot->file_path);

            // If file does not exist on disk, attempt on-demand capture if investigation exists
            if (!$realPath && $screenshot->investigation) {
                try {
                    $provider = app(ScreenshotProviderInterface::class);
                    $captured = $provider->capture(
                        $screenshot->investigation->target_url,
                        $screenshot->investigation->investigation_code
                    );

                    $screenshot->update([
                        'file_path' => $captured['file_path'],
                        'sha256' => $captured['sha256'],
                        'file_size' => $captured['file_size'],
                        'width' => $captured['width'],
                        'height' => $captured['height'],
                    ]);

                    $realPath = $this->resolveStorageFilePath($captured['file_path']);
                } catch (\Throwable $e) {
                    Log::warning("On-demand screenshot capture failed: {$e->getMessage()}");
                }
            }

            if ($realPath && file_exists($realPath)) {
                $mime = $this->detectMimeType($realPath);
                return response()->file($realPath, [
                    'Content-Type' => $mime,
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        }

        // 2. Look for Evidence by sha256, uuid, or evidence_code
        $evidence = Evidence::where('sha256', $identifier)
            ->orWhere('uuid', $identifier)
            ->orWhere('evidence_code', $identifier)
            ->first();

        if ($evidence) {
            if (in_array(strtoupper($evidence->type), ['SCREENSHOT', 'IMAGE', 'VISUAL'])) {
                $parsed = is_array($evidence->parsed_data) ? $evidence->parsed_data : json_decode($evidence->raw_data, true);
                if (!empty($parsed['file_path'])) {
                    $realPath = $this->resolveStorageFilePath($parsed['file_path']);
                    if ($realPath && file_exists($realPath)) {
                        $mime = $this->detectMimeType($realPath);
                        return response()->file($realPath, [
                            'Content-Type' => $mime,
                            'Cache-Control' => 'public, max-age=86400',
                        ]);
                    }
                }
            }

            return response($evidence->raw_data, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        // 3. Fallback placeholder SVG
        $safeId = htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8');
        $fallbackSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1280 800" width="1280" height="800">
  <defs>
    <linearGradient id="headerGrad" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#1E293B" />
      <stop offset="100%" stop-color="#0F172A" />
    </linearGradient>
  </defs>
  <rect width="1280" height="800" fill="#020617" />
  <rect x="0" y="0" width="1280" height="42" fill="url(#headerGrad)" />
  <circle cx="24" cy="21" r="6" fill="#EF4444" />
  <circle cx="44" cy="21" r="6" fill="#F59E0B" />
  <circle cx="64" cy="21" r="6" fill="#10B981" />
  <text x="640" y="27" fill="#94A3B8" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, sans-serif" font-size="12" font-weight="600" text-anchor="middle">
    WebDetect Digital Evidence Visualizer
  </text>
  <g transform="translate(340, 260)">
    <rect width="600" height="280" rx="16" fill="#0F172A" stroke="#334155" stroke-width="1.5" />
    <circle cx="300" cy="80" r="32" fill="#1E293B" />
    <path d="M290 80 L310 80 M300 70 L300 90" stroke="#60A5FA" stroke-width="2.5" stroke-linecap="round"/>
    <text x="300" y="145" fill="#F8FAFC" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, sans-serif" font-size="18" font-weight="bold" text-anchor="middle">
      Dokumentasi Visual Forensik Digital
    </text>
    <text x="300" y="180" fill="#94A3B8" font-family="monospace" font-size="12" text-anchor="middle">
      Identifier / SHA-256: {$safeId}
    </text>
    <text x="300" y="225" fill="#3B82F6" font-family="sans-serif" font-size="12" font-weight="600" text-anchor="middle">
      &bull; WebDetect Isolated Evidence Engine &bull;
    </text>
  </g>
</svg>
SVG;
        return response($fallbackSvg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    /**
     * Resolves storage path across local disk and multiple directory configurations.
     */
    protected function resolveStorageFilePath(string $filePath): ?string
    {
        $clean = ltrim(str_replace('private/', '', $filePath), '/');
        $basename = basename($filePath);

        // 1. Check Laravel Storage local disk
        $disk = Storage::disk('local');
        $diskCandidates = [
            $filePath,
            $clean,
            'private/' . $clean,
            'screenshots/' . $basename,
            'private/screenshots/' . $basename,
        ];

        foreach ($diskCandidates as $candidate) {
            if ($disk->exists($candidate)) {
                return $disk->path($candidate);
            }
        }

        // 2. Check direct filesystem locations
        $fsCandidates = [
            storage_path('app/' . $filePath),
            storage_path('app/private/' . $filePath),
            storage_path('app/' . $clean),
            storage_path('app/private/' . $clean),
            storage_path('app/private/screenshots/' . $basename),
            storage_path('app/screenshots/' . $basename),
            storage_path('app/private/private/screenshots/' . $basename),
        ];

        foreach ($fsCandidates as $path) {
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Resolves correct MIME type from file extension.
     */
    protected function detectMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => mime_content_type($path) ?: 'application/octet-stream',
        };
    }
}
