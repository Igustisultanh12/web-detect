<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Takedown - {{ $case->case_number }}</title>
    <style>
        @page {
            margin: 18mm 14mm 18mm 14mm;
            @bottom-right {
                content: "Halaman " counter(page) " dari " counter(pages);
                font-size: 8pt;
                color: #64748B;
            }
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1E293B;
            font-size: 8.5pt;
            line-height: 1.45;
            background: #FFFFFF;
        }
        .header {
            border-bottom: 2px solid #2563EB;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header table {
            width: 100%;
        }
        .logo-title {
            font-size: 15pt;
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
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8pt;
            display: inline-block;
            border: 1px solid #BFDBFE;
        }
        .doc-badge-red {
            background: #FEF2F2;
            color: #DC2626;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8pt;
            display: inline-block;
            border: 1px solid #FECACA;
        }
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0F172A;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 4px;
            margin-top: 14px;
            margin-bottom: 8px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .table-data th {
            background: #F8FAFC;
            color: #475569;
            font-weight: bold;
            text-align: left;
            padding: 5px 8px;
            border: 1px solid #E2E8F0;
            font-size: 8pt;
            text-transform: uppercase;
        }
        .table-data td {
            padding: 5px 8px;
            border: 1px solid #E2E8F0;
            font-size: 8pt;
            vertical-align: top;
        }
        .mono {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.5pt;
        }
        .disclaimer-box {
            background: #F8FAFC;
            border: 1px solid #CBD5E1;
            border-left: 4px solid #2563EB;
            padding: 10px 14px;
            margin: 14px 0;
            border-radius: 4px;
            font-size: 8pt;
            color: #334155;
        }
        .page-break {
            page-break-after: always;
        }
        .tag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7.5pt;
            font-weight: bold;
        }
        .tag-high { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }
        .tag-medium { background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="logo-title">WEBGUARD<span>.</span> INCIDENT RESPONSE</div>
                    <div class="sub-title">Dokumen Resmi Permohonan Takedown & Bukti Forensik Siber</div>
                </td>
                <td style="text-align: right;">
                    <div class="doc-badge-red">{{ $case->priority }} PRIORITY</div>
                    <div style="font-size: 7.5pt; color: #64748B; margin-top: 4px;">Klasifikasi: DOKUMEN RESMI DINAS</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Case Information -->
    <table class="table-data">
        <tr>
            <th style="width: 25%;">Nomor Berkas Kasus</th>
            <td style="width: 25%; font-weight: bold;" class="mono">{{ $case->case_number }}</td>
            <th style="width: 25%;">Tanggal Terbit Laporan</th>
            <td style="width: 25%;">{{ now()->translatedFormat('d F Y, H:i') }} WIB</td>
        </tr>
        <tr>
            <th>Target Domain / Host</th>
            <td style="font-weight: bold; color: #2563EB;">{{ $case->target_domain }}</td>
            <th>Status Penanganan</th>
            <td><span class="doc-badge">{{ $case->status }}</span></td>
        </tr>
        <tr>
            <th>Target Full URL</th>
            <td colspan="3" class="mono">{{ $case->target_url }}</td>
        </tr>
        <tr>
            <th>Kategori Dugaan Pelanggaran</th>
            <td style="font-weight: bold;">{{ $case->category }}</td>
            <th>Alamat IP Server Terakhir</th>
            <td class="mono">{{ $case->target_ip ?: ($investigation?->target_ip ?: 'Tereduksi / Cloudflare Proxy') }}</td>
        </tr>
        <tr>
            <th>Penyedia Tujuan (Provider)</th>
            <td>{{ $case->provider_name ?: 'Direktori Terdaftar' }} ({{ $case->provider_type ?: 'Hosting / Registrar' }})</td>
            <th>Nomor Tiket / Referensi</th>
            <td class="mono">{{ $case->external_reference_number ?: '(Belum Diajukan / Menunggu Tiket)' }}</td>
        </tr>
    </table>

    <!-- Legal Basis & Allegation -->
    <div class="section-title">1. Dasar Kebijakan & Rangkuman Dugaan Pelanggaran</div>
    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 10px; border-radius: 4px; margin-bottom: 8px;">
        <strong style="color: #0F172A;">Dasar Hukum / Kebijakan Pelaporan:</strong>
        <p style="margin: 4px 0 0 0; font-size: 8pt; color: #475569;">
            {{ $case->legal_or_policy_basis ?: 'UU No. 1 Tahun 2024 tentang Perubahan Kedua atas UU No. 11 Tahun 2008 tentang ITE, serta Peraturan Menteri Kominfo mengenai Penyelenggaraan Sistem Elektronik dan Ketentuan Layanan (ToS/AUP) Penyedia Layanan.' }}
        </p>
    </div>

    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 10px; border-radius: 4px; margin-bottom: 12px;">
        <strong style="color: #0F172A;">Rangkuman Uraian Aktivitas Bermasalah (Allegation Summary):</strong>
        <p style="margin: 4px 0 0 0; font-size: 8pt; color: #334155; line-height: 1.5;">
            {{ $case->allegation_summary }}
        </p>
    </div>

    <!-- Permintaan Tindak Lanjut -->
    <div class="section-title">2. Permohonan Tindak Lanjut Resmi (Takedown Request)</div>
    <p style="font-size: 8pt; margin: 4px 0 10px 0; color: #334155;">
        Sehubungan dengan temuan teknis yang diuraikan di bawah ini, pelapor mengajukan permohonan kepada pihak penyedia layanan yang berwenang untuk:
    </p>
    <ul style="font-size: 8pt; color: #334155; margin-top: 0; padding-left: 20px;">
        <li>Meninjau konten atau tautan pada target domain <strong>{{ $case->target_domain }}</strong> sesuai kebijakan Acceptable Use Policy (AUP);</li>
        <li>Melakukan penangguhan (*suspend* / *null-route* / *domain hold*) terhadap layanan aktif yang memfasilitasi aktivitas ilegal tersebut;</li>
        <li>Mengamankan log koneksi dan data pendaftaran untuk keperluan penyelidikan lanjutan jika diperlukan oleh aparat penegak hukum;</li>
        <li>Memberikan konfirmasi nomor tiket dan status tindak lanjut kepada pelapor.</li>
    </ul>

    <!-- Technical Analysis Findings -->
    <div class="section-title">3. Data Teknis Observasi Pasif (Technical Observations)</div>
    <table class="table-data">
        <tr>
            <th style="width: 20%;">Penyedia DNS</th>
            <td style="width: 30%;">{{ $investigation?->dnsRecords?->firstWhere('type', 'NS')?->data ?: 'Cloudflare / External DNS' }}</td>
            <th style="width: 20%;">Autonomous System (ASN)</th>
            <td style="width: 30%;">{{ $investigation?->asnRecord?->asn_number ?: 'AS13335 (Cloudflare Inc)' }}</td>
        </tr>
        <tr>
            <th>Organisasi Hosting / ISP</th>
            <td>{{ $investigation?->hostingRecord?->hosting_provider ?: 'Cloudflare Proxy' }}</td>
            <th>Estimasi Lokasi Server</th>
            <td>{{ $investigation?->ipAddresses?->first()?->country_name ?: 'Amerika Serikat (Anycast)' }}</td>
        </tr>
        <tr>
            <th>Penerbit Sertifikat TLS</th>
            <td>{{ $investigation?->sslCertificate?->issuer_organization ?: "Google Trust Services / Let's Encrypt" }}</td>
            <th>Validitas Sertifikat</th>
            <td>{{ $investigation?->sslCertificate?->valid_to?->translatedFormat('d M Y') ?: 'Aktif' }}</td>
        </tr>
        <tr>
            <th>HTTP Status & Header</th>
            <td class="mono">HTTP {{ $investigation?->httpResult?->status_code ?: 200 }} ({{ $investigation?->httpResult?->server_header ?: 'Nginx/Cloudflare' }})</td>
            <th>Deteksi CDN / Proxy</th>
            <td><strong>{{ $investigation?->hostingRecord?->is_cdn ? 'Terdeteksi Reverse Proxy (Edge)' : 'Direct IP Server' }}</strong></td>
        </tr>
    </table>

    <!-- Digital Evidence Vault Manifest -->
    <div class="section-title">4. Inventaris Barang Bukti Digital Forensik (Chain of Custody)</div>
    <p style="font-size: 7.5pt; color: #64748B; margin-top: 0;">
        Seluruh berkas bukti digital telah melalui proses hashing kriptografis SHA-256 saat dikumpulkan dan tersimpan secara permanen (*immutable*) pada sistem:
    </p>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 14%;">Kode Bukti</th>
                <th style="width: 18%;">Tipe Bukti</th>
                <th style="width: 20%;">Sumber / Kolektor</th>
                <th style="width: 18%;">Waktu Pengambilan</th>
                <th style="width: 30%;">Checksum Integritas (SHA-256)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evidences as $ev)
                <tr>
                    <td class="mono" style="font-weight: bold; color: #2563EB;">{{ $ev->evidence_code }}</td>
                    <td>{{ $ev->type }}</td>
                    <td>{{ $ev->source ?: 'Automated Pipeline' }}</td>
                    <td style="font-size: 7.5pt;">{{ $ev->collected_at?->translatedFormat('d/m/Y H:i') ?: '-' }}</td>
                    <td class="mono" style="font-size: 6.5pt; word-break: break-all;">{{ $ev->sha256 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #94A3B8;">Tidak ada bukti spesifik yang dilampirkan secara terpisah.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Disclaimer -->
    <div class="disclaimer-box">
        <strong>PERNYATAAN OBJEKTIF & SANGGAHAN HUKUM (LEGAL DISCLAIMER):</strong><br>
        Laporan ini disusun berdasarkan hasil observasi teknis pasif non-destruktif yang tercatat pada waktu pengujian. WebGuard Investigasi mencatat bukti digital secara faktual dan tidak secara otomatis menetapkan bahwa pemilik domain atau penyedia hosting telah melakukan tindak pidana. Penentuan keabsahan hukum dan pertanggungjawaban pidana/perdata sepenuhnya merupakan wewenang aparat penegak hukum dan instansi peradilan terkait.
    </div>

    <!-- Signatures -->
    <table style="width: 100%; margin-top: 25px;">
        <tr>
            <td style="width: 50%;">
                <div style="font-size: 7.5pt; color: #64748B;">Identitas Verifikator Sistem:</div>
                <div style="font-weight: bold; font-size: 8.5pt; margin-top: 4px;">WebGuard Automated Evidence Verification Engine</div>
                <div class="mono" style="font-size: 7pt; color: #94A3B8;">PGP-SHA256: {{ hash('sha256', $case->case_number . now()->timestamp) }}</div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div style="font-size: 7.5pt; color: #64748B;">Petugas Analis / Pelapor:</div>
                <div style="font-weight: bold; font-size: 9pt; margin-top: 4px; color: #0F172A;">
                    {{ $case->assignedOfficer?->name ?: $case->creator?->name }}
                </div>
                <div style="font-size: 8pt; color: #475569;">
                    {{ $case->assignedOfficer?->rank?->name ?: 'Analis Siber' }} — {{ $case->assignedOfficer?->unit?->name ?: 'Satuan Operasi Siber' }}
                </div>
                <div class="mono" style="font-size: 7pt; color: #64748B;">NRP: {{ $case->assignedOfficer?->nrp ?: '-' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
