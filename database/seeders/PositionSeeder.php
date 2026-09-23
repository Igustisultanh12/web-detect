<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'Komandan Satuan', 'code' => 'DANSAT', 'description' => 'Pimpinan Tertinggi Satuan Operasi'],
            ['name' => 'Kepala Tim Analis', 'code' => 'KATIM', 'description' => 'Supervisi Investigasi dan Bukti'],
            ['name' => 'Investigator Utama', 'code' => 'INV-UTAMA', 'description' => 'Pelaksana Analisis Teknis Utama'],
            ['name' => 'Analis Forensik Jaringan', 'code' => 'AN-NET', 'description' => 'Spesialis DNS & IP Intelligence'],
            ['name' => 'Penyidik Siber', 'code' => 'PENYIDIK', 'description' => 'Penyusun Berkas Laporan Investigasi'],
            ['name' => 'Operator Intelijen Siber', 'code' => 'OP-INTEL', 'description' => 'Monitoring Ancaman Domain'],
            ['name' => 'Verifikator Dokumen Personel', 'code' => 'VERIF-PERS', 'description' => 'Pemeriksa Legalitas Personel'],
        ];

        foreach ($positions as $p) {
            Position::updateOrCreate(['code' => $p['code']], $p);
        }
    }
}
