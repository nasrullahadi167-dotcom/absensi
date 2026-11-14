<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'ADMIN')->first();
        $budi = User::where('username', 'budi_santoso')->first();
        $siti = User::where('username', 'siti_aminah')->first();

        if (! $admin || ! $budi || ! $siti) {
            return;
        }

        // 1. Active Event Today with OPEN Session
        $todayEvent = Event::firstOrCreate(
            ['code' => 'EVT-' . date('Ym') . '-001'],
            [
                'title' => 'Rapat Kerja & Koordinasi Anggota Periode 2026',
                'description' => 'Evaluasi program kerja divisi, penyelarasan roadmap tahunan, serta koordinasi operasional.',
                'location' => 'Aula Utama Gd. Grha Organisasi Lt. 3',
                'event_date' => Carbon::today(),
                'start_time' => '08:00',
                'end_time' => '17:00',
                'status' => 'PUBLISHED',
                'created_by' => $admin->id,
            ]
        );

        // Single attendance session
        AttendanceSession::firstOrCreate(
            ['event_id' => $todayEvent->id],
            [
                'title' => 'Presensi Sesi Pagi',
                'open_time' => Carbon::today()->setTime(7, 0, 0),
                'close_time' => Carbon::today()->setTime(18, 0, 0),
                'late_tolerance_minutes' => 30,
                'status' => 'OPEN',
            ]
        );

        // Enroll participants
        EventParticipant::firstOrCreate([
            'event_id' => $todayEvent->id,
            'user_id' => $budi->id,
        ], ['registered_at' => now()]);

        EventParticipant::firstOrCreate([
            'event_id' => $todayEvent->id,
            'user_id' => $siti->id,
        ], ['registered_at' => now()]);

        // Record Budi's attendance
        Attendance::firstOrCreate(
            [
                'event_id' => $todayEvent->id,
                'user_id' => $budi->id,
            ],
            [
                'attendance_session_id' => $todayEvent->attendanceSession->id,
                'check_in_time' => Carbon::today()->setTime(7, 45, 0),
                'status' => 'HADIR',
                'notes' => 'Presensi hadir tepat waktu di lokasi kegiatan.',
                'drive_sync_status' => 'PENDING',
                'ip_address' => '127.0.0.1',
            ]
        );

        // 2. Upcoming Event Next Week
        $upcomingEvent = Event::firstOrCreate(
            ['code' => 'EVT-' . date('Ym') . '-002'],
            [
                'title' => 'Workshop Pengembangan Kapasitas & Soft Skills',
                'description' => 'Pelatihan kepemimpinan dan manajemen organisasi bagi seluruh anggota aktif.',
                'location' => 'Ruang Seminar 2, Kampus Pusat',
                'event_date' => Carbon::today()->addDays(7),
                'start_time' => '09:00',
                'end_time' => '15:00',
                'status' => 'PUBLISHED',
                'created_by' => $admin->id,
            ]
        );

        AttendanceSession::firstOrCreate(
            ['event_id' => $upcomingEvent->id],
            [
                'title' => 'Presensi Workshop',
                'open_time' => Carbon::today()->addDays(7)->setTime(8, 30, 0),
                'close_time' => Carbon::today()->addDays(7)->setTime(16, 0, 0),
                'late_tolerance_minutes' => 15,
                'status' => 'OPEN',
            ]
        );

        EventParticipant::firstOrCreate([
            'event_id' => $upcomingEvent->id,
            'user_id' => $budi->id,
        ], ['registered_at' => now()]);

        EventParticipant::firstOrCreate([
            'event_id' => $upcomingEvent->id,
            'user_id' => $siti->id,
        ], ['registered_at' => now()]);
    }
}
