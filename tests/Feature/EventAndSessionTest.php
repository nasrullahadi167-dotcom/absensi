<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EventAndSessionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin_tester',
            'name' => 'Admin Tester',
            'password' => Hash::make('password'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->member = User::create([
            'username' => 'member_tester',
            'name' => 'Member Tester',
            'password' => Hash::make('password'),
            'role' => 'MEMBER',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_event(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.events.store'), [
            'code' => 'EVT-TEST-001',
            'title' => 'Rapat Pleno Pengurus',
            'description' => 'Pembahasan agenda kerja.',
            'location' => 'Ruang Rapat 1',
            'event_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => 'PUBLISHED',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('events', [
            'code' => 'EVT-TEST-001',
            'title' => 'Rapat Pleno Pengurus',
        ]);
    }

    public function test_admin_can_create_single_attendance_session(): void
    {
        $event = Event::create([
            'code' => 'EVT-SESS-001',
            'title' => 'Kegiatan Sesi Tunggal',
            'location' => 'Aula',
            'event_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '11:00',
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.events.session.store', $event), [
            'title' => 'Sesi Presensi Pagi',
            'open_time' => now()->subHour()->format('Y-m-d H:i'),
            'close_time' => now()->addHours(2)->format('Y-m-d H:i'),
            'late_tolerance_minutes' => 15,
            'status' => 'OPEN',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendance_sessions', [
            'event_id' => $event->id,
            'title' => 'Sesi Presensi Pagi',
            'status' => 'OPEN',
        ]);
    }

    public function test_cannot_create_second_attendance_session_for_same_event(): void
    {
        $event = Event::create([
            'code' => 'EVT-SESS-002',
            'title' => 'Kegiatan Cek Sesi Ganda',
            'location' => 'Aula',
            'event_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '11:00',
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
        ]);

        // First session
        AttendanceSession::create([
            'event_id' => $event->id,
            'title' => 'Sesi Pertama',
            'open_time' => now()->subHour(),
            'close_time' => now()->addHour(),
            'late_tolerance_minutes' => 15,
            'status' => 'OPEN',
        ]);

        // Attempt second session
        $response = $this->actingAs($this->admin)->post(route('admin.events.session.store', $event), [
            'title' => 'Sesi Kedua Ilegal',
            'open_time' => now()->format('Y-m-d H:i'),
            'close_time' => now()->addHours(3)->format('Y-m-d H:i'),
            'late_tolerance_minutes' => 15,
            'status' => 'OPEN',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, AttendanceSession::where('event_id', $event->id)->count());
    }

    public function test_admin_can_enroll_member_to_event(): void
    {
        $event = Event::create([
            'code' => 'EVT-ENROLL-001',
            'title' => 'Event Pendaftaran',
            'location' => 'Ruang 2',
            'event_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '11:00',
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.events.participants.store', $event), [
            'user_id' => $this->member->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'user_id' => $this->member->id,
        ]);
    }

    public function test_cannot_enroll_same_member_twice(): void
    {
        $event = Event::create([
            'code' => 'EVT-ENROLL-002',
            'title' => 'Event Duplikasi',
            'location' => 'Ruang 2',
            'event_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '11:00',
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
        ]);

        EventParticipant::create([
            'event_id' => $event->id,
            'user_id' => $this->member->id,
            'registered_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.events.participants.store', $event), [
            'user_id' => $this->member->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, EventParticipant::where('event_id', $event->id)->where('user_id', $this->member->id)->count());
    }

    public function test_member_cannot_access_admin_event_routes(): void
    {
        $response = $this->actingAs($this->member)->get(route('admin.events.index'));
        $response->assertRedirect(route('member.dashboard'));
    }
}
