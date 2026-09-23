<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        // Root Unit
        $mabes = Unit::updateOrCreate(
            ['code' => 'MABES'],
            ['name' => 'Markas Besar WebGuard', 'description' => 'Komando Pusat Operasi Investigasi']
        );

        // Child Units
        $siber = Unit::updateOrCreate(
            ['code' => 'SATSBER'],
            ['parent_id' => $mabes->id, 'name' => 'Satuan Analisis Siber & Forensik', 'description' => 'Pusat Analisis Pasif']
        );

        $intel = Unit::updateOrCreate(
            ['code' => 'PUSSINTEL'],
            ['parent_id' => $mabes->id, 'name' => 'Pusat Sandi & Intelijen Siber', 'description' => 'Deteksi Ancaman']
        );

        Unit::updateOrCreate(
            ['code' => 'TIM-ALPHA'],
            ['parent_id' => $siber->id, 'name' => 'Unit Investigasi Phishing & Malware', 'description' => 'Penanganan Konten Ilegal']
        );

        Unit::updateOrCreate(
            ['code' => 'TIM-BRAVO'],
            ['parent_id' => $siber->id, 'name' => 'Unit Verifikasi Domain & Bukti Digital', 'description' => 'Penyusunan Evidence']
        );
    }
}
