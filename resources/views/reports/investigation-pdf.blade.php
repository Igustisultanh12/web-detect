<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Analisis Website - {{ $investigation->investigation_code }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
            @bottom-right {
                content: "Halaman " counter(page) " dari " counter(pages);
                font-size: 8pt;
                color: #64748B;
            }
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1E293B;
            font-size: 9pt;
            line-height: 1.45;
            background: #FFFFFF;
        }
        .header {
            border-bottom: 2px solid #2563EB;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header table {
            width: 100%;
        }
        .logo-title {
            font-size: 16pt;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.5px;
        }
        .logo-title span {
            color: #2563EB;
        }
        .sub-title {
            font-size: 8pt;
            font-weight: bold;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-badge {
            background: #EFF6FF;
            color: #2563EB;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 8pt;
            display: inline-block;
            border: 1px solid #BFDBFE;
        }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0F172A;
            background: #F1F5F9;
            padding: 5px 8px;
            margin-top: 14px;
            margin-bottom: 8px;
            border-left: 3px solid #2563EB;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #E2E8F0;
            padding: 5px 7px;
            text-align: left;
            font-size: 8.5pt;
        }
        table.data-table th {
            background: #F8FAFC;
            color: #475569;
            font-weight: 700;
        }
        .tag-fact { background: #ECFDF5; color: #065F46; padding: 2px 5px; border-radius: 3px; font-size: 7pt; font-weight: bold; }
        .tag-obs { background: #EFF6FF; color: #1E40AF; padding: 2px 5px; border-radius: 3px; font-size: 7pt; font-weight: bold; }
        .tag-ext { background: #FEF3C7; color: #92400E; padding: 2px 5px; border-radius: 3px; font-size: 7pt; font-weight: bold; }
        .tag-note { background: #F3E8FF; color: #6B21A8; padding: 2px 5px; border-radius: 3px; font-size: 7pt; font-weight: bold; }
        .disclaimer-box {
            background: #F8FAFC;
            border: 1px dashed #CBD5E1;
            padding: 8px 12px;
            font-size: 7.5pt;
            color: #64748B;
            margin-top: 16px;
            border-radius: 4px;
        }
        .footer {
            margin-top: 20px;
            border-top: 1px solid #E2E8F0;
            padding-top: 8px;
            font-size: 7.5pt;
            color: #94A3B8;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="logo-title">WEBGUARD<span>.</span></div>
                    <div class="sub-title">Platform Investigasi & Pelaporan Teknis Siber</div>
                </td>
                <td style="text-align: right;">
                    <div class="doc-badge">{{ $investigation->investigation_code }}</div>
                    <div style="font-size: 8pt; color: #64748B; margin-top: 4px;">{{ $generatedAt }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div style="font-size: 13pt; font-weight: bold; margin-bottom: 4px; text-align: center;">
        LAPORAN ANALISIS TEKNIS WEBSITE
    </div>
    <div style="font-size: 9pt; color: #64748B; text-align: center; margin-bottom: 16px;">
        Dokumen Pengumpulan Bukti & Informasi Pasif Terverifikasi
    </div>

    <!-- 1. IDENTITAS INVESTIGASI -->
    <div class="section-title">1. Identitas Investigasi & Target</div>
    <table class="data-table">
        <tr>
            <th style="width: 25%;">Nomor Investigasi</th>
            <td style="width: 25%; font-weight: bold;">{{ $investigation->investigation_code }}</td>
            <th style="width: 25%;">Klasifikasi Bukti</th>
            <td style="width: 25%;"><span class="tag-fact">FACT</span></td>
        </tr>
        <tr>
            <th>URL Target</th>
            <td colspan="3" style="font-family: monospace;">{{ $investigation->target_url }}</td>
        </tr>
        <tr>
            <th>Domain Target</th>
            <td><strong>{{ $investigation->target_domain }}</strong></td>
            <th>Kategori Investigasi</th>
            <td><span class="tag-note">{{ $investigation->category }}</span> ({{ $investigation->priority }})</td>
        </tr>
        <tr>
            <th>Investigator</th>
            <td>{{ $investigation->user->name ?? 'System' }} (NRP: {{ $investigation->user->nrp ?? '-' }})</td>
            <th>Satuan / Pangkat</th>
            <td>{{ $investigation->user->unit->name ?? '-' }} / {{ $investigation->user->rank->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Waktu Mulai</th>
            <td>{{ $investigation->started_at ? $investigation->started_at->format('d/m/Y H:i:s') : '-' }}</td>
            <th>Waktu Selesai</th>
            <td>{{ $investigation->completed_at ? $investigation->completed_at->format('d/m/Y H:i:s') : '-' }}</td>
        </tr>
    </table>

    <!-- 2. DOMAIN & WHOIS/RDAP -->
    <div class="section-title">2. Analisis Domain (RDAP / WHOIS Pasif)</div>
    @if($investigation->domainRecord)
    <table class="data-table">
        <tr>
            <th style="width: 25%;">Registrar</th>
            <td style="width: 35%;">{{ $investigation->domainRecord->registrar ?: 'Privasi / Tidak Dipublikasi' }}</td>
            <th style="width: 20%;">Sumber Data</th>
            <td style="width: 20%;"><span class="tag-ext">EXTERNAL SOURCE</span></td>
        </tr>
        <tr>
            <th>Tanggal Registrasi</th>
            <td>{{ $investigation->domainRecord->registered_at ? $investigation->domainRecord->registered_at->format('d M Y') : 'Tidak Tersedia' }}</td>
            <th>Tanggal Kedaluwarsa</th>
            <td>{{ $investigation->domainRecord->expires_at ? $investigation->domainRecord->expires_at->format('d M Y') : 'Tidak Tersedia' }}</td>
        </tr>
        <tr>
            <th>DNSSEC Status</th>
            <td>{{ $investigation->domainRecord->dnssec_status ?: 'UNSIGNED' }}</td>
            <th>Nameservers</th>
            <td style="font-family: monospace; font-size: 7.5pt;">
                {{ is_array($investigation->domainRecord->nameservers) ? implode(', ', $investigation->domainRecord->nameservers) : '-' }}
            </td>
        </tr>
    </table>
    @else
    <p style="font-style: italic; color: #94A3B8;">Informasi domain RDAP tidak tersedia pada target ini.</p>
    @endif

    <!-- 3. DNS RECORDS -->
    <div class="section-title">3. Catatan DNS Publik (DNS Records)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Tipe</th>
                <th style="width: 38%;">Host / Subdomain</th>
                <th style="width: 35%;">Tujuan (Target / Value)</th>
                <th style="width: 15%;">TTL</th>
            </tr>
        </thead>
        <tbody>
            @forelse($investigation->dnsRecords as $dns)
            <tr>
                <td><strong>{{ $dns->record_type }}</strong></td>
                <td style="font-family: monospace; font-size: 8pt;">{{ $dns->host }}</td>
                <td style="font-family: monospace; font-size: 8pt; word-break: break-all;">{{ $dns->target }}</td>
                <td>{{ $dns->ttl ?? '-' }}s</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #94A3B8;">Tidak ada DNS record yang ditemukan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 4. SERVER IP & GEOLOCATION -->
    <div class="section-title">4. Server IP, ASN & Estimasi Geolokasi</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Alamat IP</th>
                <th>Tipe / CDN</th>
                <th>ASN & Organisasi</th>
                <th>Perkiraan Lokasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($investigation->ipAddresses as $ip)
            @php
                $asn = $investigation->asnRecords->firstWhere('ip_address', $ip->ip_address);
                $geo = $investigation->hostingRecords->firstWhere('ip_address', $ip->ip_address);
            @endphp
            <tr>
                <td style="font-family: monospace; font-weight: bold;">{{ $ip->ip_address }}</td>
                <td>
                    @if($ip->is_cdn_or_proxy)
                        <span style="color: #2563EB; font-weight: bold;">CDN: {{ $ip->cdn_provider }}</span>
                    @else
                        Direct IP
                    @endif
                </td>
                <td>{{ $asn->asn ?? '-' }} ({{ $asn->asn_org ?? ($geo->isp ?? '-') }})</td>
                <td>
                    {{ $geo->city ?? '' }}, {{ $geo->region ?? '' }} {{ $geo->country ?? 'Unknown' }}
                    @if($geo && $geo->latitude)<br><span style="font-size: 7.5pt; color: #64748B;">({{ $geo->latitude }}, {{ $geo->longitude }})</span>@endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #94A3B8;">Tidak ada alamat IP yang terpetakan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 5. SSL / TLS -->
    <div class="section-title">5. Analisis Sertifikat SSL / TLS</div>
    @if($investigation->sslCertificate)
    <table class="data-table">
        <tr>
            <th style="width: 20%;">Penerbit (Issuer)</th>
            <td style="width: 30%;">{{ $investigation->sslCertificate->issuer_cn ?: ($investigation->sslCertificate->issuer_org ?: 'Unknown') }}</td>
            <th style="width: 20%;">Status Keabsahan</th>
            <td style="width: 30%;">
                @if($investigation->sslCertificate->is_valid)
                    <span style="color: #059669; font-weight: bold;">VALID</span> (Berlaku s/d {{ $investigation->sslCertificate->valid_until ? $investigation->sslCertificate->valid_until->format('d M Y') : '-' }})
                @else
                    <span style="color: #DC2626; font-weight: bold;">KEDALUWARSA / TIDAK VALID</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>Protokol & Cipher</th>
            <td>{{ $investigation->sslCertificate->tls_version ?: 'TLS' }} / {{ $investigation->sslCertificate->cipher ?: 'Standard' }}</td>
            <th>SAN (Alternative Names)</th>
            <td style="font-size: 7.5pt;">
                {{ is_array($investigation->sslCertificate->san_list) ? implode(', ', array_slice($investigation->sslCertificate->san_list, 0, 5)) : '-' }}
            </td>
        </tr>
    </table>
    @else
    <p style="font-style: italic; color: #94A3B8;">Tidak ada sertifikat SSL/TLS yang terdeteksi.</p>
    @endif

    <!-- 6. HTTP & SECURITY HEADERS -->
    <div class="section-title">6. HTTP Metadata & Skor Keamanan</div>
    @if($investigation->httpResult)
    <table class="data-table">
        <tr>
            <th style="width: 25%;">HTTP Status</th>
            <td>{{ $investigation->httpResult->http_status ?? '-' }}</td>
            <th style="width: 25%;">Skor Security Header</th>
            <td><strong>{{ $investigation->httpResult->security_score ?? 0 }}/100</strong></td>
        </tr>
        <tr>
            <th>Web Server Header</th>
            <td>{{ $investigation->httpResult->server_header ?: 'Hidden / Masked' }}</td>
            <th>HSTS Protection</th>
            <td>{{ $investigation->httpResult->hsts_header ? 'Aktif' : 'Tidak Ditemukan' }}</td>
        </tr>
        <tr>
            <th>Content-Security-Policy</th>
            <td>{{ $investigation->httpResult->csp_header ? 'Terpasang' : 'Tidak Ditemukan' }}</td>
            <th>X-Frame-Options</th>
            <td>{{ $investigation->httpResult->x_frame_options ?: 'Tidak Ditemukan' }}</td>
        </tr>
    </table>
    @endif

    <!-- 7. REPUTASI PUBLIK -->
    <div class="section-title">7. Penilaian Reputasi Publik (Threat Intelligence)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Layanan Provider</th>
                <th>Status Reputasi</th>
                <th>Kategori Ancaman</th>
                <th>Klasifikasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($investigation->reputationResults as $rep)
            <tr>
                <td><strong>{{ $rep->provider_name }}</strong></td>
                <td>
                    @if($rep->status === 'CLEAN')
                        <span style="color: #059669; font-weight: bold;">CLEAN / AMAN</span>
                    @elseif($rep->status === 'SUSPICIOUS')
                        <span style="color: #D97706; font-weight: bold;">SUSPICIOUS / MENCURIGAKAN</span>
                    @else
                        <span style="color: #DC2626; font-weight: bold;">{{ $rep->status }}</span>
                    @endif
                </td>
                <td>{{ $rep->threat_type ?: 'Tidak Ada Ancaman Publik' }}</td>
                <td><span class="tag-ext">EXTERNAL SOURCE</span></td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #94A3B8;">Pemeriksaan reputasi tidak menghasilkan data ancaman.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 8. DAFTAR BARANG BUKTI (EVIDENCE) -->
    <div class="section-title">8. Rekapitulasi Barang Bukti Digital (Evidence Vault)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Kode Bukti</th>
                <th style="width: 25%;">Tipe Bukti</th>
                <th style="width: 20%;">Sumber</th>
                <th style="width: 40%;">Integritas SHA-256 Checksum</th>
            </tr>
        </thead>
        <tbody>
            @forelse($investigation->evidences as $ev)
            <tr>
                <td style="font-family: monospace; font-weight: bold;">{{ $ev->evidence_code }}</td>
                <td>{{ $ev->type }}</td>
                <td>{{ $ev->source }}</td>
                <td style="font-family: monospace; font-size: 6.5pt; word-break: break-all;">{{ $ev->sha256 }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #94A3B8;">Tidak ada barang bukti tercatat.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- DISCLAIMERS -->
    <div class="disclaimer-box">
        <strong>PEMBERITAHUAN HUKUM & BATASAN TEKNIS:</strong><br>
        1. Laporan ini disusun semata-mata berdasarkan pemeriksaan pasif terhadap informasi publik yang sah. Sistem tidak melakukan penetrasi, eksploitasi kerentanan, atau tindakan ofensif terhadap target.<br>
        2. Lokasi IP yang tertera merupakan estimasi berbasis database IP Geolocation publik dan bukan bukti keberadaan fisik server secara mutlak.<br>
        3. Keberadaan proxy atau CDN dapat menyebabkan alamat IP yang terdeteksi merupakan node edge penyedia layanan, bukan alamat origin sebenarnya.<br>
        4. Status reputasi dan security header merupakan indikator teknis dan bukan merupakan vonis hukum atau penetapan pelanggaran secara otomatis.
    </div>

    <div class="footer">
        Dicetak secara otomatis oleh Platform WebGuard Investigasi &bull; Integritas Dokumen Terenkripsi &bull; {{ config('app.url') }}
    </div>

</body>
</html>
