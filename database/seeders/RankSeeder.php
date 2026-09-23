<?php

namespace Database\Seeders;

use App\Models\Rank;
use Illuminate\Database\Seeder;

class RankSeeder extends Seeder
{
    public function run(): void
    {
        $ranks = [
            // Perwira Tinggi (Pati)
            ['name' => 'Jenderal', 'code' => 'JEND', 'order' => 1],
            ['name' => 'Letnan Jenderal', 'code' => 'LETJEN', 'order' => 2],
            ['name' => 'Mayor Jenderal', 'code' => 'MAYJEN', 'order' => 3],
            ['name' => 'Brigadir Jenderal', 'code' => 'BRIGJEN', 'order' => 4],
            // Perwira Menengah (Pamen)
            ['name' => 'Kolonel', 'code' => 'KOL', 'order' => 5],
            ['name' => 'Letnan Kolonel', 'code' => 'LETKOL', 'order' => 6],
            ['name' => 'Mayor', 'code' => 'MAY', 'order' => 7],
            // Perwira Pertama (Pama)
            ['name' => 'Kapten', 'code' => 'KAPT', 'order' => 8],
            ['name' => 'Letnan Satu', 'code' => 'LETTU', 'order' => 9],
            ['name' => 'Letnan Dua', 'code' => 'LETDA', 'order' => 10],
            // Bintara
            ['name' => 'Pembantu Letnan Satu', 'code' => 'PELTU', 'order' => 11],
            ['name' => 'Pembantu Letnan Dua', 'code' => 'PELDA', 'order' => 12],
            ['name' => 'Sersan Mayor', 'code' => 'SERMA', 'order' => 13],
            ['name' => 'Sersan Kepala', 'code' => 'SERKA', 'order' => 14],
            ['name' => 'Sersan Satu', 'code' => 'SERTU', 'order' => 15],
            ['name' => 'Sersan Dua', 'code' => 'SERDA', 'order' => 16],
            // Tamtama / Sipil
            ['name' => 'Kopral Kepala', 'code' => 'KOPKA', 'order' => 17],
            ['name' => 'Kopral Satu', 'code' => 'KOPTU', 'order' => 18],
            ['name' => 'Kopral Dua', 'code' => 'KOPDA', 'order' => 19],
            ['name' => 'Prajurit Satu', 'code' => 'PRATU', 'order' => 20],
            ['name' => 'Prajurit Dua', 'code' => 'PRADA', 'order' => 21],
            ['name' => 'PNS / ASN Siber', 'code' => 'ASN', 'order' => 22],
            ['name' => 'Pakar Tamu / Mitra', 'code' => 'MITRA', 'order' => 23],
        ];

        foreach ($ranks as $r) {
            Rank::updateOrCreate(['code' => $r['code']], $r);
        }
    }
}
