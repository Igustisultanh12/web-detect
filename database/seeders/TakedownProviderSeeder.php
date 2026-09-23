<?php

namespace Database\Seeders;

use App\Models\TakedownProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TakedownProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            // Otoritas Regulasi & CSIRT Indonesia
            [
                'name' => 'PANDI (Pengelola Nama Domain Internet Indonesia)',
                'type' => 'Domain Registrar',
                'website' => 'https://pandi.id',
                'abuse_email' => 'abuse@pandi.id',
                'abuse_url' => 'https://pandi.id/lapor-domain',
                'api_endpoint' => null,
                'report_types' => ['Phishing', 'Hoax / Disinformasi', 'Penipuan Finansial', 'Judi Online'],
                'requirements' => 'Tangkapan layar bukti, URL spesifik, data WHOIS domain .id, dan kronologi dugaan pelanggaran.',
                'sla_hours' => 24,
                'integration_status' => 'MANUAL',
                'notes' => 'Otoritas resmi domain tingkat tinggi negara Indonesia (.id). Memiliki kewenangan penangguhan domain lokal.',
            ],
            [
                'name' => 'Aduan Konten Kominfo (Kementerian Komdigi)',
                'type' => 'Regulator',
                'website' => 'https://aduankonten.id',
                'abuse_email' => 'aduankonten@kominfo.go.id',
                'abuse_url' => 'https://aduankonten.id',
                'api_endpoint' => null,
                'report_types' => ['Hoax / Disinformasi', 'Judi Online', 'Penipuan', 'Pornografi', 'Konten Negatif'],
                'requirements' => 'URL lengkap, screenshot bukti otentik dengan timestamp, kategori pelanggaran berdasarkan UU ITE.',
                'sla_hours' => 48,
                'integration_status' => 'MANUAL',
                'notes' => 'Portal resmi pemblokiran domain dan tautan konten ilegal pada sistem Trust Positif / Nawala.',
            ],
            [
                'name' => 'BSSN CSIRT (Gov-CSIRT Indonesia)',
                'type' => 'CERT/CSIRT',
                'website' => 'https://csirt.bssn.go.id',
                'abuse_email' => 'bantuan.70@bssn.go.id',
                'abuse_url' => 'https://csirt.bssn.go.id/kontak',
                'api_endpoint' => null,
                'report_types' => ['Web Defacement', 'Malware', 'Phishing', 'Insiden Sektor Pemerintah'],
                'requirements' => 'Laporan teknis insiden, IP server target, hash malware, artefak log insiden.',
                'sla_hours' => 24,
                'integration_status' => 'MANUAL',
                'notes' => 'Penanganan insiden siber nasional dan koordinasi antar-CSIRT sektor pemerintah.',
            ],
            [
                'name' => 'Bareskrim Polri - Dittipidsiber (Patroli Siber)',
                'type' => 'Law Enforcement',
                'website' => 'https://patrolisiber.id',
                'abuse_email' => 'lapor@patrolisiber.id',
                'abuse_url' => 'https://patrolisiber.id',
                'api_endpoint' => null,
                'report_types' => ['Penipuan Finansial', 'Hoax / Provokasi', 'Pencurian Identitas', 'Pemerasan'],
                'requirements' => 'Identitas pelapor resmi, bukti transfer/rekening (jika ada penipuan), printout barang bukti digital SHA-256.',
                'sla_hours' => 72,
                'integration_status' => 'MANUAL',
                'notes' => 'Unit kepolisian siber untuk proses penegakan hukum dan penyelidikan pro-justitia.',
            ],

            // CDN & Infrastruktur Global
            [
                'name' => 'Cloudflare Abuse Desk',
                'type' => 'CDN Provider',
                'website' => 'https://www.cloudflare.com',
                'abuse_email' => 'abuse@cloudflare.com',
                'abuse_url' => 'https://abuse.cloudflare.com',
                'api_endpoint' => 'https://api.cloudflare.com/client/v4/abuse',
                'report_types' => ['Phishing', 'Malware', 'Copyright Infringement', 'Financial Scam'],
                'requirements' => 'Origin server IP (jika terdeteksi), URL target, logs, screenshot.',
                'sla_hours' => 24,
                'integration_status' => 'API_AVAILABLE',
                'notes' => 'Penyedia Reverse Proxy dan CDN terbesar. Meneruskan laporan abuse ke host asli (origin IP) dan registrar.',
            ],
            [
                'name' => 'Google Safe Browsing & Web Risk',
                'type' => 'Search Engine',
                'website' => 'https://safebrowsing.google.com',
                'abuse_email' => null,
                'abuse_url' => 'https://safebrowsing.google.com/safebrowsing/report_phish/',
                'api_endpoint' => 'https://webrisk.googleapis.com/v1',
                'report_types' => ['Phishing', 'Social Engineering', 'Malware Distribution'],
                'requirements' => 'Target URL, klasifikasi ancaman.',
                'sla_hours' => 12,
                'integration_status' => 'API_AVAILABLE',
                'notes' => 'Melabeli domain sebagai ancaman merah pada browser Chrome, Firefox, Safari, dan Android.',
            ],
            [
                'name' => 'Namecheap Trust & Safety',
                'type' => 'Domain Registrar',
                'website' => 'https://www.namecheap.com',
                'abuse_email' => 'abuse@namecheap.com',
                'abuse_url' => 'https://support.namecheap.com/index.php?/Tickets/Submit',
                'api_endpoint' => null,
                'report_types' => ['Fraud', 'Phishing', 'Hoax', 'Malicious Domain Registration'],
                'requirements' => 'Domain name, bukti kepemilikan/laporan penipuan, full header email/DNS.',
                'sla_hours' => 48,
                'integration_status' => 'MANUAL',
                'notes' => 'Registrar domain internasional terpopuler dengan tim respons Trust & Safety aktif.',
            ],
            [
                'name' => 'DigitalOcean Trust & Safety',
                'type' => 'Hosting Provider',
                'website' => 'https://www.digitalocean.com',
                'abuse_email' => 'abuse@digitalocean.com',
                'abuse_url' => 'https://cloudsupport.digitalocean.com/s/submit-abuse-ticket',
                'api_endpoint' => null,
                'report_types' => ['Malware Hosting', 'Phishing Server', 'Botnet C2'],
                'requirements' => 'Droplet IP address, timestamp dengan timezone UTC, bukti aktivitas berbahaya.',
                'sla_hours' => 24,
                'integration_status' => 'MANUAL',
                'notes' => 'Hosting cloud VPS terkemuka untuk menangguhkan server C2 atau hosting website ilegal.',
            ],
            [
                'name' => 'Telegram Trust & Abuse Desk',
                'type' => 'Social Media Platform',
                'website' => 'https://telegram.org',
                'abuse_email' => 'abuse@telegram.org',
                'abuse_url' => 'https://telegram.org/faq#q-there-is-illegal-content-on-telegram-how-do-i-take-it-down',
                'api_endpoint' => null,
                'report_types' => ['Kanal Judi Online', 'Distribusi Hoax', 'Penipuan Investasi'],
                'requirements' => 'Tautan t.me publik, screenshot konten, rincian pelanggaran.',
                'sla_hours' => 48,
                'integration_status' => 'MANUAL',
                'notes' => 'Pelaporan kanal atau grup yang mempromosikan atau mengarahkan ke website ilegal.',
            ],
        ];

        foreach ($providers as $item) {
            TakedownProvider::updateOrCreate(
                ['name' => $item['name']],
                array_merge($item, ['uuid' => (string) Str::uuid()])
            );
        }
    }
}
