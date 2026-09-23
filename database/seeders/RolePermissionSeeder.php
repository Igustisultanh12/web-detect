<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Investigation
            ['name' => 'investigation.view', 'label' => 'Melihat Investigasi', 'group' => 'investigation'],
            ['name' => 'investigation.create', 'label' => 'Membuat Investigasi Baru', 'group' => 'investigation'],
            ['name' => 'investigation.export', 'label' => 'Mengekspor Data Investigasi', 'group' => 'investigation'],
            ['name' => 'investigation.delete', 'label' => 'Menghapus Investigasi', 'group' => 'investigation'],

            // Evidence
            ['name' => 'evidence.view', 'label' => 'Melihat Barang Bukti Digital', 'group' => 'evidence'],
            ['name' => 'evidence.create', 'label' => 'Menambah Catatan Bukti', 'group' => 'evidence'],
            ['name' => 'evidence.export', 'label' => 'Mengekspor Bukti Digital', 'group' => 'evidence'],

            // Report
            ['name' => 'report.view', 'label' => 'Melihat Laporan', 'group' => 'report'],
            ['name' => 'report.generate', 'label' => 'Membuat Laporan PDF/CSV', 'group' => 'report'],
            ['name' => 'report.download', 'label' => 'Mengunduh Laporan Resmi', 'group' => 'report'],

            // Personnel
            ['name' => 'user.view', 'label' => 'Melihat Data Personel', 'group' => 'user'],
            ['name' => 'user.create', 'label' => 'Menambah / Mengundang Personel', 'group' => 'user'],
            ['name' => 'user.edit', 'label' => 'Mengubah Data Personel', 'group' => 'user'],
            ['name' => 'user.activate', 'label' => 'Mengaktifkan Akun Personel', 'group' => 'user'],
            ['name' => 'user.deactivate', 'label' => 'Menonaktifkan Akun Personel', 'group' => 'user'],
            ['name' => 'user.delete', 'label' => 'Menghapus Akun Personel', 'group' => 'user'],

            // Document Security
            ['name' => 'user.document.view', 'label' => 'Melihat Dokumen KTP/KTA', 'group' => 'document'],
            ['name' => 'user.document.upload', 'label' => 'Mengunggah Dokumen KTP/KTA', 'group' => 'document'],
            ['name' => 'user.document.download', 'label' => 'Mengunduh Dokumen KTP/KTA Asli', 'group' => 'document'],
            ['name' => 'user.document.delete', 'label' => 'Menghapus Dokumen KTP/KTA', 'group' => 'document'],

            // Audit & Security
            ['name' => 'audit.view', 'label' => 'Melihat Audit Trail Sistem', 'group' => 'security'],
            ['name' => 'security.view', 'label' => 'Melihat Dashboard Keamanan', 'group' => 'security'],
            ['name' => 'settings.manage', 'label' => 'Mengelola Pengaturan Sistem', 'group' => 'system'],
            ['name' => 'api_provider.manage', 'label' => 'Mengelola Kredensial Provider API', 'group' => 'system'],
        ];

        $createdPermissions = [];
        foreach ($permissions as $p) {
            $createdPermissions[$p['name']] = Permission::updateOrCreate(['name' => $p['name']], $p);
        }

        // Roles
        $superAdmin = Role::updateOrCreate(['name' => 'super_admin'], [
            'label' => 'SUPER ADMIN',
            'description' => 'Akses penuh seluruh modul, konfigurasi keamanan, dan data rahasia.',
            'is_system' => true,
        ]);

        $admin = Role::updateOrCreate(['name' => 'admin'], [
            'label' => 'ADMIN',
            'description' => 'Mengelola personel, investigasi, dokumen, dan pelaporan.',
            'is_system' => true,
        ]);

        $investigator = Role::updateOrCreate(['name' => 'investigator'], [
            'label' => 'INVESTIGATOR',
            'description' => 'Melakukan analisis website pasif, mengumpulkan bukti, dan menghasilkan laporan.',
            'is_system' => true,
        ]);

        $viewer = Role::updateOrCreate(['name' => 'viewer'], [
            'label' => 'VIEWER',
            'description' => 'Akses baca saja terhadap investigasi dan laporan.',
            'is_system' => true,
        ]);

        // Attach permissions
        // SUPER ADMIN gets all
        $superAdmin->permissions()->sync(array_column($createdPermissions, 'id'));

        // ADMIN
        $adminPermissions = [
            'investigation.view', 'investigation.create', 'investigation.export',
            'evidence.view', 'evidence.create', 'evidence.export',
            'report.view', 'report.generate', 'report.download',
            'user.view', 'user.create', 'user.edit', 'user.activate', 'user.deactivate',
            'user.document.view', 'user.document.upload', 'user.document.download',
            'audit.view', 'security.view',
        ];
        $admin->permissions()->sync(
            Permission::whereIn('name', $adminPermissions)->pluck('id')
        );

        // INVESTIGATOR
        $investigatorPermissions = [
            'investigation.view', 'investigation.create', 'investigation.export',
            'evidence.view', 'evidence.create', 'evidence.export',
            'report.view', 'report.generate', 'report.download',
        ];
        $investigator->permissions()->sync(
            Permission::whereIn('name', $investigatorPermissions)->pluck('id')
        );

        // VIEWER
        $viewerPermissions = [
            'investigation.view', 'report.view',
        ];
        $viewer->permissions()->sync(
            Permission::whereIn('name', $viewerPermissions)->pluck('id')
        );
    }
}
