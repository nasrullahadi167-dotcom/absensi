<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected Event $event;

    protected AttendanceSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::create([
            'username' => 'admin_attend',
            'name' => 'Admin Attend',
            'password' => Hash::make('password'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->member = User::create([
            'username' => 'member_attend',
            'name' => 'Member Attend',
            'password' => Hash::make('password'),
            'role' => 'MEMBER',
            'is_active' => true,
        ]);

        $this->event = Event::create([
            'code' => 'EVT-ATT-001',
            'title' => 'Kegiatan Absensi Uji',
            'location' => 'Gedung Serbaguna',
            'event_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
        ]);

        $this->session = AttendanceSession::create([
            'event_id' => $this->event->id,
            'title' => 'Sesi Uji Presensi',
            'open_time' => now()->subMinutes(10),
            'close_time' => now()->addMinutes(60),
            'late_tolerance_minutes' => 15,
            'status' => 'OPEN',
        ]);

        EventParticipant::create([
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
            'registered_at' => now(),
        ]);
    }

    /**
     * Generate a valid dummy base64 JPEG image for tests.
     */
    protected function generateBase64Photo(): string
    {
        $im = imagecreatetruecolor(200, 200);
        $bgColor = imagecolorallocate($im, 240, 240, 240);
        imagefill($im, 0, 0, $bgColor);
        $textColor = imagecolorallocate($im, 50, 50, 50);
        imagestring($im, 5, 20, 90, 'TEST SELFIE PHOTO', $textColor);

        ob_start();
        imagejpeg($im, null, 90);
        $imageData = ob_get_clean();
        imagedestroy($im);

        return 'data:image/jpeg;base64,'.base64_encode($imageData);
    }

    public function test_enrolled_member_can_record_attendance_with_selfie(): void
    {
        $photoData = $this->generateBase64Photo();

        $response = $this->actingAs($this->member)->post(route('member.attendance.store', $this->event), [
            'photo_data' => $photoData,
            'notes' => 'Presensi hadir tepat waktu',
        ]);

        $response->assertRedirect(route('member.events.show', $this->event));

        $this->assertDatabaseHas('attendances', [
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
            'status' => 'HADIR',
        ]);

        // Outbox check
        $this->assertDatabaseHas('sync_outbox', [
            'event_type' => 'ATTENDANCE_SYNC',
            'aggregate_type' => 'Attendance',
        ]);
    }

    public function test_unregistered_member_cannot_record_attendance(): void
    {
        $otherMember = User::create([
            'username' => 'other_member',
            'name' => 'Other Member',
            'password' => Hash::make('password'),
            'role' => 'MEMBER',
            'is_active' => true,
        ]);

        $photoData = $this->generateBase64Photo();

        $response = $this->actingAs($otherMember)->post(route('member.attendance.store', $this->event), [
            'photo_data' => $photoData,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('attendances', [
            'event_id' => $this->event->id,
            'user_id' => $otherMember->id,
        ]);
    }

    public function test_cannot_check_in_when_session_is_closed(): void
    {
        $this->session->update(['status' => 'CLOSED']);

        $photoData = $this->generateBase64Photo();

        $response = $this->actingAs($this->member)->post(route('member.attendance.store', $this->event), [
            'photo_data' => $photoData,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('attendances', [
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
        ]);
    }

    public function test_duplicate_attendance_is_strictly_rejected(): void
    {
        $photoData = $this->generateBase64Photo();

        // First attendance
        $this->actingAs($this->member)->post(route('member.attendance.store', $this->event), [
            'photo_data' => $photoData,
        ]);

        // Second attendance attempt
        $response = $this->actingAs($this->member)->post(route('member.attendance.store', $this->event), [
            'photo_data' => $photoData,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, Attendance::where('event_id', $this->event->id)->where('user_id', $this->member->id)->count());
    }

    public function test_late_status_is_assigned_when_checking_in_after_tolerance(): void
    {
        // Set open time to 30 minutes ago, tolerance is 15 minutes
        $this->session->update([
            'open_time' => now()->subMinutes(30),
            'late_tolerance_minutes' => 15,
        ]);

        $photoData = $this->generateBase64Photo();

        $this->actingAs($this->member)->post(route('member.attendance.store', $this->event), [
            'photo_data' => $photoData,
        ]);

        $this->assertDatabaseHas('attendances', [
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
            'status' => 'TERLAMBAT',
        ]);
    }

    public function test_attendance_photo_is_protected_by_authorization(): void
    {
        $photoData = $this->generateBase64Photo();

        $this->actingAs($this->member)->post(route('member.attendance.store', $this->event), [
            'photo_data' => $photoData,
        ]);

        $attendance = Attendance::where('user_id', $this->member->id)->firstOrFail();

        // 1. Owner can view photo
        $response = $this->actingAs($this->member)->get(route('attendance.photo', $attendance));
        $response->assertStatus(200);

        // 2. Admin can view photo
        $adminResponse = $this->actingAs($this->admin)->get(route('attendance.photo', $attendance));
        $adminResponse->assertStatus(200);

        // 3. Another member cannot view photo
        $stranger = User::create([
            'username' => 'stranger_user',
            'name' => 'Stranger User',
            'password' => Hash::make('password'),
            'role' => 'MEMBER',
            'is_active' => true,
        ]);

        $unauthorizedResponse = $this->actingAs($stranger)->get(route('attendance.photo', $attendance));
        $unauthorizedResponse->assertStatus(403);
    }
}
