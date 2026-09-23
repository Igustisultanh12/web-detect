<?php

namespace App\Services\Analysis;

use App\Contracts\DnsProviderInterface;
use App\Contracts\DomainProviderInterface;
use App\Contracts\IpIntelligenceProviderInterface;
use App\Contracts\ReputationProviderInterface;
use App\Contracts\ScreenshotProviderInterface;
use App\Contracts\WhatsAppProviderInterface;
use App\Models\AsnRecord;
use App\Models\DnsRecord;
use App\Models\DomainRecord;
use App\Models\HostingRecord;
use App\Models\HttpResult;
use App\Models\Investigation;
use App\Models\IpAddress;
use App\Models\ReputationResult;
use App\Models\Screenshot;
use App\Models\SslCertificate;
use App\Models\Subdomain;
use App\Models\Technology;
use App\Services\Evidence\EvidenceManagerService;
use App\Services\Reports\ReportGeneratorService;
use App\Services\Security\SsrfProtectionService;
use Exception;
use Illuminate\Support\Facades\Log;

class InvestigationWorkflowService
{
    public function __construct(
        protected SsrfProtectionService $ssrfProtection,
        protected DomainProviderInterface $domainProvider,
        protected DnsProviderInterface $dnsProvider,
        protected IpIntelligenceProviderInterface $ipProvider,
        protected CdnDetectionService $cdnDetector,
        protected SslAnalysisService $sslService,
        protected HttpAnalysisService $httpService,
        protected TechnologyDetectionService $techDetector,
        protected SubdomainDiscoveryService $subdomainService,
        protected ReputationProviderInterface $reputationProvider,
        protected ScreenshotProviderInterface $screenshotProvider,
        protected EvidenceManagerService $evidenceManager,
        protected ReportGeneratorService $reportGenerator,
        protected WhatsAppProviderInterface $whatsAppProvider
    ) {}

    /**
     * Executes the complete passive investigation pipeline.
     */
    public function process(Investigation $investigation): void
    {
        $investigation->update([
            'status' => 'ANALYZING',
            'started_at' => now(),
        ]);

        $investigation->addTimeline('INVESTIGATION_STARTED', 'Proses investigasi pasif dimulai.', 'INFO');
        $hasErrors = false;

        try {
            // STEP 0: SSRF & URL Validation
            $validatedUrl = $this->ssrfProtection->validateUrl($investigation->target_url);
            $domain = $validatedUrl['host'];
            $investigation->update(['target_domain' => $domain]);

            // STEP 1: Domain & RDAP Analysis
            $this->runDomainAnalysis($investigation, $domain);

            // STEP 2: DNS Records Query
            $dnsRecords = $this->runDnsAnalysis($investigation, $domain);

            // STEP 3: Server IP Intelligence & CDN Detection
            $resolvedIps = $validatedUrl['resolved_ips'];
            $ipData = $this->runIpAnalysis($investigation, $domain, $dnsRecords, $resolvedIps);

            // STEP 4: SSL/TLS Certificate Analysis
            $this->runSslAnalysis($investigation, $domain);

            // STEP 5: HTTP Metadata & Security Headers
            $httpData = $this->runHttpAnalysis($investigation, $investigation->target_url);

            // STEP 6: Technology Fingerprinting
            $this->runTechnologyDetection($investigation, $httpData);

            // STEP 7: Passive Subdomain Discovery
            $this->runSubdomainDiscovery($investigation, $domain);

            // STEP 8: Public Reputation Check
            $this->runReputationCheck($investigation, $domain, $ipData['primary_ip'] ?? null);

            // STEP 9: Isolated Visual Screenshot Capture
            $this->runScreenshotCapture($investigation);

            // STEP 10: Automatic Report Generation (PDF)
            $this->runReportGeneration($investigation);

            // STEP 11: Notification Dispatch
            $this->runNotificationDispatch($investigation);

            $finalStatus = $hasErrors ? 'PARTIAL_RESULT' : 'COMPLETED';
            $investigation->update([
                'status' => $finalStatus,
                'completed_at' => now(),
            ]);

            $investigation->addTimeline(
                'INVESTIGATION_COMPLETED',
                "Investigasi selesai dengan status {$finalStatus}. Seluruh bukti dan laporan telah disimpan.",
                'SUCCESS'
            );

        } catch (Exception $e) {
            Log::error("Investigation {$investigation->investigation_code} failed: " . $e->getMessage());

            $investigation->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $investigation->addTimeline(
                'INVESTIGATION_FAILED',
                'Investigasi gagal: ' . $e->getMessage(),
                'ERROR'
            );
        }
    }

    protected function runDomainAnalysis(Investigation $inv, string $domain): void
    {
        $inv->addTimeline('DOMAIN_ANALYSIS_STARTED', 'Memulai pengumpulan data domain dan RDAP/WHOIS.', 'INFO');
        $info = $this->domainProvider->getDomainInfo($domain);

        DomainRecord::create([
            'investigation_id' => $inv->id,
            'domain' => $info['domain'],
            'tld' => $info['tld'],
            'registrar' => $info['registrar'],
            'registered_at' => $info['registered_at'],
            'expires_at' => $info['expires_at'],
            'domain_status' => $info['domain_status'],
            'nameservers' => $info['nameservers'],
            'dnssec_status' => $info['dnssec_status'],
            'rdap_data' => $info['rdap_data'],
        ]);

        $this->evidenceManager->record($inv, 'Domain Information', 'RDAP Protocol', $info, $info, 'Metadata registrasi domain publik');
        $inv->addTimeline('DOMAIN_ANALYSIS_COMPLETED', 'Data domain publik berhasil dikumpulkan.', 'SUCCESS');
    }

    protected function runDnsAnalysis(Investigation $inv, string $domain): array
    {
        $inv->addTimeline('DNS_ANALYSIS_STARTED', 'Menjalankan kueri DNS publik untuk semua record (A, AAAA, MX, TXT, NS, SOA, CAA).', 'INFO');
        $records = $this->dnsProvider->getRecords($domain);

        foreach ($records as $r) {
            DnsRecord::create([
                'investigation_id' => $inv->id,
                'record_type' => $r['record_type'],
                'host' => $r['host'],
                'target' => $r['target'],
                'ttl' => $r['ttl'],
                'priority' => $r['priority'],
                'raw_entry' => $r['raw_entry'],
            ]);
        }

        $this->evidenceManager->record($inv, 'DNS Result', 'DNS Resolver', $records, $records, 'Daftar catatan DNS publik');
        $inv->addTimeline('DNS_ANALYSIS_COMPLETED', 'Ditemukan ' . count($records) . ' catatan DNS publik.', 'SUCCESS');

        return $records;
    }

    protected function runIpAnalysis(Investigation $inv, string $domain, array $dnsRecords, array $resolvedIps): array
    {
        $inv->addTimeline('IP_ANALYSIS_STARTED', 'Menganalisis alamat IP, ASN, ISP, dan estimasi geolokasi server.', 'INFO');

        // Extract all unique IPs from A and AAAA records
        $allIps = $resolvedIps;
        foreach ($dnsRecords as $rec) {
            if (in_array($rec['record_type'], ['A', 'AAAA'], true) && !empty($rec['target'])) {
                $allIps[] = $rec['target'];
            }
        }
        $allIps = array_unique(array_filter($allIps));

        $asnList = [];
        $rdnsList = [];
        $primaryIp = null;

        foreach ($allIps as $ip) {
            if (!$primaryIp) $primaryIp = $ip;
            $ipInfo = $this->ipProvider->getIpInfo($ip);

            // Record ASN
            if (!empty($ipInfo['asn'])) {
                $asnList[] = $ipInfo['asn'];
                AsnRecord::create([
                    'investigation_id' => $inv->id,
                    'ip_address' => $ip,
                    'asn' => $ipInfo['asn'],
                    'asn_org' => $ipInfo['asn_org'],
                    'bgp_prefix' => $ipInfo['bgp_prefix'],
                    'registry' => $ipInfo['registry'],
                ]);
            }

            if (!empty($ipInfo['reverse_dns'])) {
                $rdnsList[] = $ipInfo['reverse_dns'];
            }

            // Record Hosting / Geolocation
            HostingRecord::create([
                'investigation_id' => $inv->id,
                'ip_address' => $ip,
                'isp' => $ipInfo['isp'],
                'organization' => $ipInfo['organization'],
                'hosting_type' => $ipInfo['hosting_type'],
                'country' => $ipInfo['country'],
                'country_code' => $ipInfo['country_code'],
                'region' => $ipInfo['region'],
                'city' => $ipInfo['city'],
                'latitude' => $ipInfo['latitude'],
                'longitude' => $ipInfo['longitude'],
                'timezone' => $ipInfo['timezone'],
                'is_datacenter' => $ipInfo['is_datacenter'],
            ]);
        }

        // Detect CDN
        $cdnAnalysis = $this->cdnDetector->detect($dnsRecords, [], $asnList, $rdnsList);

        foreach ($allIps as $ip) {
            IpAddress::create([
                'investigation_id' => $inv->id,
                'ip_address' => $ip,
                'ip_version' => str_contains($ip, ':') ? 'v6' : 'v4',
                'is_cdn_or_proxy' => $cdnAnalysis['cdn_detected'],
                'cdn_provider' => $cdnAnalysis['provider'],
                'reverse_dns' => @gethostbyaddr($ip) ?: null,
            ]);
        }

        $this->evidenceManager->record($inv, 'IP Information', 'IP Intelligence Feed', $allIps, [
            'ips' => $allIps,
            'cdn' => $cdnAnalysis,
        ], 'Identifikasi alamat IP server dan deteksi CDN');

        $inv->addTimeline('IP_ANALYSIS_COMPLETED', 'Analisis IP selesai. CDN detected: ' . ($cdnAnalysis['cdn_detected'] ? 'YES (' . $cdnAnalysis['provider'] . ')' : 'NO'), 'SUCCESS');

        return [
            'primary_ip' => $primaryIp,
            'all_ips' => $allIps,
            'cdn' => $cdnAnalysis,
        ];
    }

    protected function runSslAnalysis(Investigation $inv, string $domain): void
    {
        $inv->addTimeline('SSL_ANALYSIS_STARTED', 'Memeriksa sertifikat SSL/TLS, masa berlaku, dan Certificate Transparency.', 'INFO');
        $ssl = $this->sslService->analyze($domain);

        SslCertificate::create([
            'investigation_id' => $inv->id,
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

        $this->evidenceManager->record($inv, 'SSL Certificate', 'TLS Handshake', $ssl, $ssl, 'Sertifikat keamanan SSL/TLS');
        $inv->addTimeline('SSL_ANALYSIS_COMPLETED', 'Analisis SSL/TLS selesai. Status sertifikat: ' . ($ssl['is_valid'] ? 'VALID' : 'INVALID/NONE'), 'SUCCESS');
    }

    protected function runHttpAnalysis(Investigation $inv, string $targetUrl): array
    {
        $inv->addTimeline('HTTP_ANALYSIS_STARTED', 'Menginspeksi respons HTTP pasif, headers, cookies, dan postur keamanan.', 'INFO');
        $http = $this->httpService->analyze($targetUrl);

        HttpResult::create([
            'investigation_id' => $inv->id,
            'http_status' => $http['http_status'],
            'https_available' => $http['https_available'],
            'final_url' => $http['final_url'],
            'redirect_chain' => $http['redirect_chain'],
            'server_header' => $http['server_header'],
            'content_type' => $http['content_type'],
            'content_length' => $http['content_length'],
            'compression' => $http['compression'],
            'hsts_header' => $http['hsts_header'],
            'csp_header' => $http['csp_header'],
            'x_frame_options' => $http['x_frame_options'],
            'x_content_type_options' => $http['x_content_type_options'],
            'referrer_policy' => $http['referrer_policy'],
            'permissions_policy' => $http['permissions_policy'],
            'cookies_data' => $http['cookies_data'],
            'security_score' => $http['security_score'],
            'security_notes' => $http['security_notes'],
            'raw_headers' => $http['raw_headers'],
        ]);

        $this->evidenceManager->record($inv, 'HTTP Headers', 'Safe HTTP Client', $http['raw_headers'], $http, 'Header HTTP dan Skor Security Header');
        $inv->addTimeline('HTTP_ANALYSIS_COMPLETED', "Pemeriksaan HTTP selesai. Skor Security Header: {$http['security_score']}/100", 'SUCCESS');

        return $http;
    }

    protected function runTechnologyDetection(Investigation $inv, array $httpData): void
    {
        $inv->addTimeline('TECH_DETECTION_STARTED', 'Mendeteksi teknologi web (server, backend, framework, CMS, analytics).', 'INFO');
        $techs = $this->techDetector->detect(
            $httpData['raw_headers'] ?? [],
            $httpData['cookies_data'] ?? [],
            $httpData['html_body'] ?? ''
        );

        foreach ($techs as $t) {
            Technology::create([
                'investigation_id' => $inv->id,
                'category' => $t['category'],
                'name' => $t['name'],
                'version' => $t['version'],
                'confidence' => $t['confidence'],
                'matched_pattern' => $t['matched_pattern'],
                'icon' => $t['icon'],
            ]);
        }

        $this->evidenceManager->record($inv, 'Technology', 'Passive Fingerprinting', $techs, $techs, 'Daftar teknologi terdeteksi');
        $inv->addTimeline('TECH_DETECTION_COMPLETED', 'Terdeteksi ' . count($techs) . ' teknologi pada target.', 'SUCCESS');
    }

    protected function runSubdomainDiscovery(Investigation $inv, string $domain): void
    {
        $inv->addTimeline('SUBDOMAIN_DISCOVERY_STARTED', 'Mencari subdomain pasif melalui Certificate Transparency logs.', 'INFO');
        $subdomains = $this->subdomainService->discover($domain);

        foreach ($subdomains as $s) {
            Subdomain::create([
                'investigation_id' => $inv->id,
                'subdomain' => $s['subdomain'],
                'source' => $s['source'],
                'ip_address' => $s['ip_address'],
                'is_active' => $s['is_active'],
            ]);
        }

        $this->evidenceManager->record($inv, 'Subdomain', 'Certificate Transparency', $subdomains, $subdomains, 'Daftar subdomain pasif');
        $inv->addTimeline('SUBDOMAIN_DISCOVERY_COMPLETED', 'Ditemukan ' . count($subdomains) . ' subdomain pasif.', 'SUCCESS');
    }

    protected function runReputationCheck(Investigation $inv, string $domain, ?string $primaryIp): void
    {
        $inv->addTimeline('REPUTATION_CHECK_STARTED', 'Memeriksa reputasi domain pada threat intelligence feeds publik.', 'INFO');
        $reputations = $this->reputationProvider->checkReputation($domain, 'domain');

        if ($primaryIp) {
            $ipRep = $this->reputationProvider->checkReputation($primaryIp, 'ip');
            $reputations = array_merge($reputations, $ipRep);
        }

        foreach ($reputations as $rep) {
            ReputationResult::create([
                'investigation_id' => $inv->id,
                'provider_name' => $rep['provider_name'],
                'status' => $rep['status'],
                'threat_type' => $rep['threat_type'],
                'score' => $rep['score'],
                'checked_at' => $rep['checked_at'],
                'details' => $rep['details'],
            ]);
        }

        $this->evidenceManager->record($inv, 'Reputation', 'Public Threat Feeds', $reputations, $reputations, 'Hasil pengecekan reputasi publik');
        $inv->addTimeline('REPUTATION_CHECK_COMPLETED', 'Pengecekan reputasi selesai.', 'SUCCESS');
    }

    protected function runScreenshotCapture(Investigation $inv): void
    {
        $inv->addTimeline('SCREENSHOT_CAPTURE_STARTED', 'Mengambil tangkapan layar website melalui isolated capture worker.', 'INFO');
        $shot = $this->screenshotProvider->capture($inv->target_url, $inv->investigation_code);

        $screenshotModel = Screenshot::create([
            'investigation_id' => $inv->id,
            'file_path' => $shot['file_path'],
            'original_url' => $inv->target_url,
            'sha256' => $shot['sha256'],
            'width' => $shot['width'],
            'height' => $shot['height'],
            'file_size' => $shot['file_size'],
            'captured_at' => $shot['captured_at'],
        ]);

        $this->evidenceManager->record($inv, 'Screenshot', 'Browser Worker', [
            'file_path' => $shot['file_path'],
            'sha256' => $shot['sha256'],
            'captured_at' => $shot['captured_at'],
        ], $shot, 'Tangkapan layar visual website target');

        $inv->addTimeline('SCREENSHOT_CAPTURE_COMPLETED', 'Screenshot berhasil disimpan sebagai barang bukti digital.', 'SUCCESS');
    }

    protected function runReportGeneration(Investigation $inv): void
    {
        $inv->addTimeline('REPORT_GENERATION_STARTED', 'Menyusun laporan investigasi komprehensif dalam format PDF.', 'INFO');
        $report = $this->reportGenerator->generate($inv, 'PDF');
        $inv->addTimeline('REPORT_GENERATION_COMPLETED', "Laporan resmi diterbitkan: {$report->report_number}", 'SUCCESS');
    }

    protected function runNotificationDispatch(Investigation $inv): void
    {
        $user = $inv->user;
        if ($user && $user->whatsapp_number) {
            $msg = "Pemberitahuan WebGuard: Investigasi untuk domain {$inv->target_domain} ({$inv->investigation_code}) telah selesai dianalisis. Laporan dan barang bukti digital dapat diakses pada platform.";
            $this->whatsAppProvider->sendMessage($user->whatsapp_number, $msg);
        }
    }
}
