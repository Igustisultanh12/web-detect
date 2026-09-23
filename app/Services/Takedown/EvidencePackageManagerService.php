<?php

namespace App\Services\Takedown;

use App\Models\Evidence;
use App\Models\TakedownCase;
use Exception;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class EvidencePackageManagerService
{
    public function __construct(protected TakedownReportGeneratorService $reportGenerator) {}

    /**
     * Generate structured ZIP evidence package.
     */
    public function generatePackage(TakedownCase $case): array
    {
        $caseDirName = $case->case_number;
        $tempDir = storage_path("app/private/temp_packages/{$caseDirName}");
        $zipFilename = "{$case->case_number}-Evidence-Package.zip";
        $zipStoragePath = "takedown/packages/{$case->case_number}/{$zipFilename}";
        $fullZipPath = storage_path("app/private/{$zipStoragePath}");

        if (!file_exists(dirname($fullZipPath))) {
            mkdir(dirname($fullZipPath), 0755, true);
        }

        // 1. Generate Report PDF
        $pdfResult = $this->reportGenerator->generatePdf($case);

        // 2. Load Selected Evidences
        $evidenceIds = $case->selected_evidence_ids;
        if (!empty($evidenceIds)) {
            $evidences = Evidence::whereIn('id', $evidenceIds)->get();
        } elseif ($case->investigation) {
            $evidences = $case->investigation->evidences;
        } else {
            $evidences = collect();
        }

        // 3. Prepare ZIP Archive
        $zip = new ZipArchive();
        if ($zip->open($fullZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Gagal membuat arsip ZIP bukti digital pada sistem private storage.");
        }

        $manifestEntries = [];
        $sha256Sums = [];

        // Add Report PDF to ZIP root
        $zip->addFromString("{$caseDirName}/report.pdf", $pdfResult['content']);
        $manifestEntries[] = [
            'file_name' => 'report.pdf',
            'relative_path' => "{$caseDirName}/report.pdf",
            'file_size' => $pdfResult['size'],
            'sha256' => $pdfResult['sha256'],
            'collected_at' => now()->toISOString(),
            'source' => 'WebGuard Report Generator',
            'investigator' => $case->assignedOfficer?->name ?: $case->creator?->name,
            'evidence_id' => 'OFFICIAL_REPORT',
        ];
        $sha256Sums[] = "{$pdfResult['sha256']}  report.pdf";

        // Add Evidence items to evidence/ folder in ZIP
        $counter = 1;
        foreach ($evidences as $ev) {
            $ext = 'json';
            $content = $ev->raw_data;

            if ($ev->type === 'SCREENSHOT') {
                $ext = 'png';
                // If screenshot raw data is base64 or file path
                if (str_starts_with($content, 'data:image')) {
                    $base64Data = explode(',', $content)[1] ?? $content;
                    $binary = base64_decode($base64Data);
                } elseif (file_exists(storage_path("app/private/{$content}"))) {
                    $binary = file_get_contents(storage_path("app/private/{$content}"));
                } else {
                    $binary = (string) $content;
                }
            } else {
                $binary = (string) $content;
            }

            $typeSlug = strtolower(str_replace(['_', ' '], '-', $ev->type));
            $entryName = sprintf("evidence/%s-%03d.%s", $typeSlug, $counter, $ext);
            $zipPath = "{$caseDirName}/{$entryName}";

            $zip->addFromString($zipPath, $binary);

            $fileSha256 = hash('sha256', $binary);
            $fileSize = strlen($binary);

            $manifestEntries[] = [
                'file_name' => basename($entryName),
                'relative_path' => $zipPath,
                'file_size' => $fileSize,
                'sha256' => $fileSha256,
                'collected_at' => $ev->collected_at?->toISOString() ?: now()->toISOString(),
                'source' => $ev->source ?: 'Passive Recon Pipeline',
                'investigator' => $case->assignedOfficer?->name ?: $case->creator?->name,
                'evidence_id' => $ev->evidence_code ?: "EV-{$ev->id}",
            ];

            $sha256Sums[] = "{$fileSha256}  {$entryName}";
            $counter++;
        }

        // Add manifest.json
        $manifestJson = json_encode([
            'case_number' => $case->case_number,
            'target_domain' => $case->target_domain,
            'target_url' => $case->target_url,
            'generated_at' => now()->toISOString(),
            'platform' => 'WebGuard Investigasi & Incident Response Platform',
            'integrity_algorithm' => 'SHA-256',
            'files_count' => count($manifestEntries),
            'files' => $manifestEntries,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $zip->addFromString("{$caseDirName}/manifest.json", $manifestJson);
        $manifestHash = hash('sha256', $manifestJson);
        $sha256Sums[] = "{$manifestHash}  manifest.json";

        // Add SHA256SUMS.txt
        $sha256Text = implode("\n", $sha256Sums) . "\n";
        $zip->addFromString("{$caseDirName}/SHA256SUMS.txt", $sha256Text);

        $zip->close();

        $packageSize = filesize($fullZipPath);
        $packageSha256 = hash_file('sha256', $fullZipPath);

        return [
            'filename' => $zipFilename,
            'path' => $zipStoragePath,
            'full_path' => $fullZipPath,
            'size' => $packageSize,
            'sha256' => $packageSha256,
            'manifest' => json_decode($manifestJson, true),
        ];
    }
}
