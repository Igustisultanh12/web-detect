<?php

namespace App\Jobs;

use App\Contracts\IpIntelligenceProviderInterface;
use App\Models\AsnRecord;
use App\Models\HostingRecord;
use App\Models\Investigation;
use App\Models\IpAddress;
use App\Services\Analysis\CdnDetectionService;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeIpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 45;

    public function __construct(public Investigation $investigation, public array $ips) {}

    public function handle(
        IpIntelligenceProviderInterface $provider,
        CdnDetectionService $cdnDetector,
        EvidenceManagerService $evidenceManager
    ): void {
        $this->investigation->addTimeline('IP_ANALYSIS_STARTED', 'Menganalisis alamat IP dan intelijen server.', 'INFO');
        $asnList = [];
        $rdnsList = [];

        foreach ($this->ips as $ip) {
            $info = $provider->getIpInfo($ip);

            if (!empty($info['asn'])) {
                $asnList[] = $info['asn'];
                AsnRecord::create([
                    'investigation_id' => $this->investigation->id,
                    'ip_address' => $ip,
                    'asn' => $info['asn'],
                    'asn_org' => $info['asn_org'],
                    'bgp_prefix' => $info['bgp_prefix'],
                    'registry' => $info['registry'],
                ]);
            }

            if (!empty($info['reverse_dns'])) {
                $rdnsList[] = $info['reverse_dns'];
            }

            HostingRecord::create([
                'investigation_id' => $this->investigation->id,
                'ip_address' => $ip,
                'isp' => $info['isp'],
                'organization' => $info['organization'],
                'hosting_type' => $info['hosting_type'],
                'country' => $info['country'],
                'country_code' => $info['country_code'],
                'region' => $info['region'],
                'city' => $info['city'],
                'latitude' => $info['latitude'],
                'longitude' => $info['longitude'],
                'timezone' => $info['timezone'],
                'is_datacenter' => $info['is_datacenter'],
            ]);
        }

        $dnsRecords = $this->investigation->dnsRecords()->get()->toArray();
        $cdnAnalysis = $cdnDetector->detect($dnsRecords, [], $asnList, $rdnsList);

        foreach ($this->ips as $ip) {
            IpAddress::create([
                'investigation_id' => $this->investigation->id,
                'ip_address' => $ip,
                'ip_version' => str_contains($ip, ':') ? 'v6' : 'v4',
                'is_cdn_or_proxy' => $cdnAnalysis['cdn_detected'],
                'cdn_provider' => $cdnAnalysis['provider'],
                'reverse_dns' => @gethostbyaddr($ip) ?: null,
            ]);
        }

        $evidenceManager->record($this->investigation, 'IP Information', 'IP Intelligence Feed', $this->ips, [
            'ips' => $this->ips,
            'cdn' => $cdnAnalysis,
        ], 'Identifikasi alamat IP server dan deteksi CDN');

        $this->investigation->addTimeline('IP_ANALYSIS_COMPLETED', 'Analisis IP selesai.', 'SUCCESS');
    }
}
