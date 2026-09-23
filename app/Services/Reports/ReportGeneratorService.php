<?php

namespace App\Services\Reports;

use App\Models\Investigation;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportGeneratorService
{
    /**
     * Generate structured PDF, CSV, or JSON investigation report.
     */
    public function generate(Investigation $investigation, string $format = 'PDF'): Report
    {
        $investigation->load([
            'user.rank',
            'user.unit',
            'domainRecord',
            'dnsRecords',
            'ipAddresses',
            'asnRecords',
            'hostingRecords',
            'sslCertificate',
            'httpResult',
            'technologies',
            'subdomains',
            'reputationResults',
            'screenshots',
            'evidences',
            'timeline',
        ]);

        $format = strtoupper($format);
        $code = $investigation->investigation_code;
        $uuid = (string) Str::uuid();
        $relativeDir = 'private/reports';
        $reportNumber = "{$code}-REP-" . date('Ymd');
        $title = "LAPORAN ANALISIS TEKNIS WEBSITE: {$investigation->target_domain}";

        if ($format === 'JSON') {
            $jsonData = json_encode($this->buildReportPayload($investigation), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $filename = "{$code}_{$uuid}.json";
            $filePath = "{$relativeDir}/{$filename}";
            Storage::disk('local')->put($filePath, $jsonData);
            $size = strlen($jsonData);
            $hash = hash('sha256', $jsonData);
        } elseif ($format === 'CSV') {
            $csvData = $this->buildCsvPayload($investigation);
            $filename = "{$code}_{$uuid}.csv";
            $filePath = "{$relativeDir}/{$filename}";
            Storage::disk('local')->put($filePath, $csvData);
            $size = strlen($csvData);
            $hash = hash('sha256', $csvData);
        } else {
            // PDF report
            $pdf = Pdf::loadView('reports.investigation-pdf', [
                'investigation' => $investigation,
                'reportNumber' => $reportNumber,
                'generatedAt' => now()->format('d M Y H:i:s') . ' WIB',
            ])->setPaper('a4', 'portrait');

            $pdfContent = $pdf->output();
            $filename = "{$code}_{$uuid}.pdf";
            $filePath = "{$relativeDir}/{$filename}";
            Storage::disk('local')->put($filePath, $pdfContent);
            $size = strlen($pdfContent);
            $hash = hash('sha256', $pdfContent);
        }

        return Report::create([
            'investigation_id' => $investigation->id,
            'report_number' => $reportNumber,
            'title' => $title,
            'format' => $format,
            'file_path' => $filePath,
            'file_size' => $size,
            'sha256' => $hash,
            'generated_by' => auth()->id() ?? $investigation->user_id,
            'generated_at' => now(),
        ]);
    }

    public function buildReportPayload(Investigation $inv): array
    {
        return [
            'report_header' => [
                'title' => 'LAPORAN ANALISIS TEKNIS WEBSITE (PASSIVE)',
                'investigation_code' => $inv->investigation_code,
                'target_url' => $inv->target_url,
                'target_domain' => $inv->target_domain,
                'category' => $inv->category,
                'priority' => $inv->priority,
                'investigator' => [
                    'name' => $inv->user->name ?? 'Investigator',
                    'nrp' => $inv->user->nrp ?? '-',
                    'rank' => $inv->user->rank->name ?? '-',
                    'unit' => $inv->user->unit->name ?? '-',
                ],
                'status' => $inv->status,
                'started_at' => $inv->started_at?->toIso8601String(),
                'completed_at' => $inv->completed_at?->toIso8601String(),
            ],
            'disclaimers' => [
                'nature' => 'Analisis dilakukan secara pasif menggunakan informasi publik yang sah.',
                'ip_attribution' => 'Lokasi IP adalah estimasi berdasarkan database IP geolocation dan tidak selalu menunjukkan lokasi fisik server sebenarnya.',
                'cdn' => 'Alamat IP yang ditemukan kemungkinan merupakan endpoint CDN/reverse proxy dan bukan alamat server origin.',
                'legal_notice' => 'Laporan ini tidak membuat kesimpulan hukum otomatis dan hanya memuat fakta teknis serta observasi digital.',
            ],
            'domain' => $inv->domainRecord,
            'dns_records' => $inv->dnsRecords,
            'ip_addresses' => $inv->ipAddresses,
            'asn_records' => $inv->asnRecords,
            'hosting_records' => $inv->hostingRecords,
            'ssl_certificate' => $inv->sslCertificate,
            'http_results' => $inv->httpResult,
            'technologies' => $inv->technologies,
            'subdomains' => $inv->subdomains,
            'reputation' => $inv->reputationResults,
            'timeline' => $inv->timeline,
            'evidences' => $inv->evidences->map(fn($e) => [
                'code' => $e->evidence_code,
                'type' => $e->type,
                'source' => $e->source,
                'sha256' => $e->sha256,
                'collected_at' => $e->collected_at?->toIso8601String(),
            ]),
        ];
    }

    protected function buildCsvPayload(Investigation $inv): string
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, ['SECTION', 'KEY', 'VALUE', 'CLASSIFICATION']);

        fputcsv($fp, ['METADATA', 'Investigation Code', $inv->investigation_code, 'FACT']);
        fputcsv($fp, ['METADATA', 'Target Domain', $inv->target_domain, 'FACT']);
        fputcsv($fp, ['METADATA', 'Category', $inv->category, 'INVESTIGATOR NOTE']);
        fputcsv($fp, ['METADATA', 'Investigator', $inv->user->name ?? '-', 'FACT']);

        if ($inv->domainRecord) {
            fputcsv($fp, ['DOMAIN', 'Registrar', $inv->domainRecord->registrar, 'EXTERNAL SOURCE']);
            fputcsv($fp, ['DOMAIN', 'Registered At', $inv->domainRecord->registered_at, 'EXTERNAL SOURCE']);
            fputcsv($fp, ['DOMAIN', 'Expires At', $inv->domainRecord->expires_at, 'EXTERNAL SOURCE']);
        }

        foreach ($inv->dnsRecords as $dns) {
            fputcsv($fp, ['DNS', $dns->record_type . ' ' . $dns->host, $dns->target, 'FACT']);
        }

        foreach ($inv->ipAddresses as $ip) {
            fputcsv($fp, ['IP', $ip->ip_address, $ip->cdn_provider ? "CDN: {$ip->cdn_provider}" : 'Direct IP', 'OBSERVATION']);
        }

        foreach ($inv->technologies as $tech) {
            fputcsv($fp, ['TECHNOLOGY', $tech->category . ': ' . $tech->name, $tech->version ?: 'Detected', 'OBSERVATION']);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return $csv;
    }
}
