<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrgaSetupAdminCommand extends Command
{
    protected $signature = 'orga:create-admin 
                            {--username= : Administrator username}
                            {--name= : Administrator full name}
                            {--password= : Administrator password}
                            {--email= : Administrator email address}';

    protected $description = 'Create or initialize an Administrator user securely';

    public function handle(): int
    {
        $this->info('=== Setup Administrator ORGA-ATTEND ===');

        $username = $this->option('username') ?: $this->ask('Masukkan Username Admin', 'admin');
        $name = $this->option('name') ?: $this->ask('Masukkan Nama Lengkap Admin', 'Administrator Utama');
        $email = $this->option('email') ?: $this->ask('Masukkan Email Admin (opsional)', 'admin@orga-attend.local');

        $existing = User::where('username', $username)->first();
        if ($existing) {
            $this->error("Username '{$username}' sudah terdaftar!");
            if (! $this->confirm('Apakah Anda ingin mereset password akun admin ini?', false)) {
                return 1;
            }
        }

        $password = $this->option('password');
        if (! $password) {
            $password = $this->secret('Masukkan Password Admin (biarkan kosong untuk generate acak)') ?: Str::password(12);
        }

        $userData = [
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'ADMIN',
            'is_active' => true,
        ];

        if ($existing) {
            $existing->update($userData);
            $user = $existing;
            $this->info("Admin '{$username}' berhasil diperbarui.");
        } else {
            $user = User::create($userData);
            $this->info("Admin '{$username}' berhasil dibuat!");
        }

        AuditLog::record(
            action: 'ADMIN_SETUP_CLI',
            description: "Admin {$username} dibuat/diperbarui via artisan CLI.",
            entity: $user
        );

        $this->table(['Field', 'Value'], [
            ['Username', $username],
            ['Nama', $name],
            ['Email', $email],
            ['Role', 'ADMIN'],
            ['Password', $password],
        ]);

        $this->warn('Catat dan simpan password ini dengan aman!');

        return 0;
    }
}
