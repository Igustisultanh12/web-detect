<?php

namespace App\Jobs;

use App\Models\Investigation;
use App\Models\SslCertificate;
use App\Services\Analysis\SslAnalysisService;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeSslJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(public Investigation $investigation, public string $domain) {}

    public function handle(SslAnalysisService $sslService, EvidenceManagerService $evidenceManager): void
    {
        $this->investigation->addTimeline('SSL_ANALYSIS_STARTED', 'Memeriksa sertifikat SSL/TLS.', 'INFO');
        $ssl = $sslService->analyze($this->domain);

        SslCertificate::create([
            'investigation_id' => $this->investigation->id,
            'subject_cn' => $ssl['subject_cn'],
            'subject_org' => $ssl['subject_org'],
            'issuer_cn' => $ssl['issuer_cn'],
            'issuer_org' => $ssl['issuer_org'],
            'san_list' => $ssl['san_list'],
            'valid_from' => $ssl['valid_from'],
            'valid_until' => $ssl['valid_until'],
            'is_valid' => $ssl['is_valid'],
            'tls_version' => $ssl['tls_version'],
            'cipher' => $ssl['cipher'],
            'signature_algorithm' => $ssl['signature_algorithm'],
            'public_key_bits' => $ssl['public_key_bits'],
            'cert_chain' => $ssl['cert_chain'],
            'ct_status' => $ssl['ct_status'],
        ]);

        $evidenceManager->record($this->investigation, 'SSL Certificate', 'TLS Handshake', $ssl, $ssl, 'Sertifikat keamanan SSL/TLS');
        $this->investigation->addTimeline('SSL_ANALYSIS_COMPLETED', 'Analisis SSL selesai.', 'SUCCESS');
    }
}
