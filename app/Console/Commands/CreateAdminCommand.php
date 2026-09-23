<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Rank;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAdminCommand extends Command
{
    protected $signature = 'webguard:create-admin {--name=} {--email=} {--password=}';
    protected $description = 'Membuat akun Administrator / Super Admin baru secara interaktif dan aman.';

    public function handle(): int
    {
        $this->info("=================================================");
        $this->info("  WEBGUARD INVESTIGASI - PEMBUATAN AKUN ADMIN   ");
        $this->info("=================================================");

        $name = $this->option('name') ?: $this->ask('Masukkan Nama Lengkap Administrator:');
        $email = $this->option('email') ?: $this->ask('Masukkan Alamat Email Administrator:');

        if (User::where('email', $email)->exists()) {
            $this->error("Email '{$email}' sudah terdaftar dalam sistem!");
            return 1;
        }

        $password = $this->option('password') ?: $this->secret('Masukkan Password (min. 12 karakter):');

        if (strlen($password) < 12) {
            $this->error('Password harus memiliki panjang minimal 12 karakter untuk mematuhi standar keamanan.');
            return 1;
        }

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'status' => 'ACTIVE',
            'active_from' => now(),
            'activated_by_admin_at' => now(),
            'email_verified_at' => now(),
            'two_factor_enabled' => false,
        ]);

        $user->assignRole('super_admin');

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'USER_CREATED_VIA_CLI',
            'target_type' => 'User',
            'target_id' => (string) $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Artisan CLI',
            'result' => 'SUCCESS',
            'details' => ['email' => $email, 'role' => 'super_admin'],
            'created_at' => now(),
        ]);

        $this->newLine();
        $this->info("Akun Super Admin berhasil dibuat!");
        $this->table(
            ['ID', 'UUID', 'Nama', 'Email', 'Role', 'Status'],
            [[$user->id, $user->uuid, $user->name, $user->email, 'SUPER ADMIN', $user->status]]
        );
        $this->comment("Catatan: Silakan login dan aktifkan Two-Factor Authentication (TOTP) pada menu Profil Keamanan.");

        return 0;
    }
}
