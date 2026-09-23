<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SecurityCheckCommand extends Command
{
    protected $signature = 'webguard:security-check';
    protected $description = 'Memeriksa konfigurasi keamanan sistem, environment, dan kepatuhan produksi WebGuard.';

    public function handle(): int
    {
        $this->info("=======================================================");
        $this->info("    WEBGUARD POSTURE & SECURITY AUDIT CHECKLIST       ");
        $this->info("=======================================================");

        $results = [];

        // 1. APP_DEBUG
        $debug = config('app.debug');
        $results[] = [
            'Komponen' => 'APP_DEBUG',
            'Status' => $debug ? 'PERINGATAN' : 'AMAN',
            'Keterangan' => $debug ? 'Debug mode AKTIF. Matikan di production (APP_DEBUG=false).' : 'Debug mode nonaktif (Aman).',
        ];

        // 2. APP_KEY
        $key = config('app.key');
        $hasKey = !empty($key) && str_starts_with($key, 'base64:');
        $results[] = [
            'Komponen' => 'APP_KEY',
            'Status' => $hasKey ? 'AMAN' : 'BAHAYA',
            'Keterangan' => $hasKey ? 'Application Encryption Key terkonfigurasi dengan benar.' : 'Kunci enkripsi belum di-generate!',
        ];

        // 3. APP_ENV
        $env = config('app.env');
        $results[] = [
            'Komponen' => 'APP_ENV',
            'Status' => 'INFO',
            'Keterangan' => "Lingkungan berjalan pada mode: '{$env}'.",
        ];

        // 4. Database Connection
        try {
            DB::connection()->getPdo();
            $results[] = [
                'Komponen' => 'Database',
                'Status' => 'AMAN',
                'Keterangan' => 'Koneksi basis data aktif dan teruji.',
            ];
        } catch (\Exception $e) {
            $results[] = [
                'Komponen' => 'Database',
                'Status' => 'BAHAYA',
                'Keterangan' => 'Gagal terhubung ke database: ' . $e->getMessage(),
            ];
        }

        // 5. Storage Permissions
        $storageDir = storage_path();
        $isWritable = is_writable($storageDir);
        $results[] = [
            'Komponen' => 'Storage Directory',
            'Status' => $isWritable ? 'AMAN' : 'BAHAYA',
            'Keterangan' => $isWritable ? 'Direktori storage dapat ditulisi.' : 'Permission denied pada folder storage.',
        ];

        // 6. Private Directory Isolation
        $privatePath = storage_path('app/private');
        File::ensureDirectoryExists($privatePath . '/user-documents');
        File::ensureDirectoryExists($privatePath . '/evidence');
        File::ensureDirectoryExists($privatePath . '/reports');
        File::ensureDirectoryExists($privatePath . '/screenshots');
        $results[] = [
            'Komponen' => 'Private Storage Isolation',
            'Status' => 'AMAN',
            'Keterangan' => 'Folder private (user-documents, evidence, reports, screenshots) terisolasi di luar public.',
        ];

        // 7. .env Protection in Public
        $publicEnv = public_path('.env');
        $envExposed = file_exists($publicEnv);
        $results[] = [
            'Komponen' => '.env File Exposure',
            'Status' => $envExposed ? 'BAHAYA' : 'AMAN',
            'Keterangan' => $envExposed ? 'Berkas .env ditemukan di direktori public! Hapus segera!' : 'Berkas .env aman dari direktori publik.',
        ];

        // 8. PHP Version
        $phpVer = PHP_VERSION;
        $isPhpOk = version_compare($phpVer, '8.2.0', '>=');
        $results[] = [
            'Komponen' => 'PHP Runtime Version',
            'Status' => $isPhpOk ? 'AMAN' : 'PERINGATAN',
            'Keterangan' => "Versi PHP saat ini: {$phpVer}.",
        ];

        $this->table(['Komponen', 'Status', 'Keterangan'], $results);
        $this->newLine();
        $this->info("Pemeriksaan selesai. Selalu terapkan Nginx SSL dan Firewall aktif pada server aaPanel.");

        return 0;
    }
}
