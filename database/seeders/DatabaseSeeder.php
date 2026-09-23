<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Rank;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSecurity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RankSeeder::class,
            UnitSeeder::class,
            PositionSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $kolonel = Rank::where('code', 'KOL')->first();
        $kapten = Rank::where('code', 'KAPT')->first();
        $letda = Rank::where('code', 'LETDA')->first();

        $mabes = Unit::where('code', 'MABES')->first();
        $satsiber = Unit::where('code', 'SATSBER')->first();
        $timAlpha = Unit::where('code', 'TIM-ALPHA')->first();

        $dansat = Position::where('code', 'DANSAT')->first();
        $invUtama = Position::where('code', 'INV-UTAMA')->first();
        $opIntel = Position::where('code', 'OP-INTEL')->first();

        // 1. Super Admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@webguard.id'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Super Administrator WebGuard',
                'nrp' => '990001',
                'rank_id' => $kolonel?->id,
                'position_id' => $dansat?->id,
                'unit_id' => $mabes?->id,
                'phone' => '081234567890',
                'whatsapp_number' => '6281234567890',
                'password' => Hash::make('WebGuardPassword123!'),
                'status' => 'ACTIVE',
                'active_from' => now(),
                'activated_by_admin_at' => now(),
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
            ]
        );
        $superAdmin->assignRole('super_admin');

        UserProfile::updateOrCreate(['user_id' => $superAdmin->id], [
            'nik' => '3171010101900001',
            'birthplace' => 'Jakarta',
            'birthdate' => '1985-05-15',
            'gender' => 'Laki-laki',
            'address' => 'Jl. Medan Merdeka Barat No. 1, Jakarta Pusat',
            'city' => 'Jakarta Pusat',
            'province' => 'DKI Jakarta',
            'emergency_contact_name' => 'Keluarga Super Admin',
            'emergency_contact_phone' => '081234567899',
        ]);

        // 2. Investigator
        $investigator = User::updateOrCreate(
            ['email' => 'investigator@webguard.id'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Kapten Satria Pratama, S.Kom',
                'nrp' => '210105',
                'rank_id' => $kapten?->id,
                'position_id' => $invUtama?->id,
                'unit_id' => $satsiber?->id,
                'phone' => '081298765432',
                'whatsapp_number' => '6281298765432',
                'password' => Hash::make('WebGuardPassword123!'),
                'status' => 'ACTIVE',
                'active_from' => now(),
                'activated_by_admin_at' => now(),
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
            ]
        );
        $investigator->assignRole('investigator');

        UserProfile::updateOrCreate(['user_id' => $investigator->id], [
            'nik' => '3271020202920002',
            'birthplace' => 'Bandung',
            'birthdate' => '1992-08-20',
            'gender' => 'Laki-laki',
            'address' => 'Komplek Satuan Siber No. 12',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
        ]);

        // 3. Viewer
        $viewer = User::updateOrCreate(
            ['email' => 'viewer@webguard.id'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Letda Dimas Aditya',
                'nrp' => '240210',
                'rank_id' => $letda?->id,
                'position_id' => $opIntel?->id,
                'unit_id' => $timAlpha?->id,
                'phone' => '081311223344',
                'whatsapp_number' => '6281311223344',
                'password' => Hash::make('WebGuardPassword123!'),
                'status' => 'ACTIVE',
                'active_from' => now(),
                'activated_by_admin_at' => now(),
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
            ]
        );
        $viewer->assignRole('viewer');

        // Initial Settings
        Setting::set('app_name', 'WebGuard Investigasi', 'general', true);
        Setting::set('app_tagline', 'Sistem Investigasi & Dokumentasi Bukti Siber Pasif', 'general', true);
        Setting::set('rate_limit_per_user', '10', 'rate_limit', false);
        Setting::set('rate_limit_per_domain_seconds', '30', 'rate_limit', false);
        Setting::set('document_retention_days', '365', 'retention', false);
        Setting::set('require_admin_approval', 'false', 'security', false);
    }
}
