<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database safely.
     */
    public function run(): void
    {
        $this->call(DivisionSeeder::class);

        // Initial Admin Setup (secure, not hardcoded permanently)
        $adminUsername = env('ADMIN_DEFAULT_USERNAME', 'admin');
        $adminPassword = env('ADMIN_DEFAULT_PASSWORD', 'Admin@2026!');

        $admin = User::firstOrCreate(
            ['username' => $adminUsername],
            [
                'name' => 'Administrator Utama',
                'email' => 'admin@orga-attend.local',
                'password' => Hash::make($adminPassword),
                'role' => 'ADMIN',
                'is_active' => true,
            ]
        );

        // Initial sample members for testing
        $itDiv = Division::where('code', 'DIV-IT')->first();
        $humasDiv = Division::where('code', 'DIV-HUMAS')->first();

        $members = [
            [
                'username' => 'budi_santoso',
                'name' => 'Budi Santoso',
                'email' => 'budi@orga-attend.local',
                'phone' => '081234567890',
                'password' => Hash::make('Member@2026!'),
                'role' => 'MEMBER',
                'division_id' => $itDiv ? $itDiv->id : null,
                'is_active' => true,
            ],
            [
                'username' => 'siti_aminah',
                'name' => 'Siti Aminah',
                'email' => 'siti@orga-attend.local',
                'phone' => '081298765432',
                'password' => Hash::make('Member@2026!'),
                'role' => 'MEMBER',
                'division_id' => $humasDiv ? $humasDiv->id : null,
                'is_active' => true,
            ],
        ];

        foreach ($members as $mem) {
            User::firstOrCreate(['username' => $mem['username']], $mem);
        }

        $this->call(DemoDataSeeder::class);

        AuditLog::record(
            action: 'DATABASE_SEEDED',
            description: 'Database berhasil di-seed dengan data awal divisi dan akun pengguna.',
            entity: $admin
        );
    }
}
