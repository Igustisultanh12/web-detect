<?php

namespace App\Services\Evidence;

use App\Models\Evidence;
use App\Models\EvidenceVersion;
use App\Models\Investigation;
use Exception;

class EvidenceManagerService
{
    /**
     * Store a new immutable evidence record with SHA-256 hash.
     */
    public function record(
        Investigation $investigation,
        string $type,
        string $source,
        string|array $rawData,
        ?array $parsedData = null,
        ?string $notes = null
    ): Evidence {
        $rawString = is_array($rawData) ? json_encode($rawData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $rawData;
        $hash = hash('sha256', $rawString);

        return Evidence::create([
            'investigation_id' => $investigation->id,
            'type' => $type,
            'source' => $source,
            'raw_data' => $rawString,
            'parsed_data' => $parsedData,
            'sha256' => $hash,
            'collected_at' => now(),
            'created_by' => auth()->id() ?? $investigation->user_id,
            'notes' => $notes,
        ]);
    }

    /**
     * Add annotation or note as a new immutable version without mutating original raw evidence.
     */
    public function addNote(Evidence $evidence, string $note): EvidenceVersion
    {
        $currentMaxVersion = $evidence->versions()->max('version') ?? 0;

        return EvidenceVersion::create([
            'evidence_id' => $evidence->id,
            'version' => $currentMaxVersion + 1,
            'notes' => $note,
            'updated_by' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    /**
     * Verify SHA-256 integrity of an existing evidence item.
     */
    public function verifyIntegrity(Evidence $evidence): bool
    {
        $computedHash = hash('sha256', $evidence->raw_data);
        return hash_equals($evidence->sha256, $computedHash);
    }
}
