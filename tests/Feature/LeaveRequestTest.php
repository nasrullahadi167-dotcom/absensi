<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::create([
            'username' => 'admin_leave',
            'name' => 'Admin Leave',
            'password' => Hash::make('password'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->member = User::create([
            'username' => 'member_leave',
            'name' => 'Member Leave',
            'password' => Hash::make('password'),
            'role' => 'MEMBER',
            'is_active' => true,
        ]);

        $this->event = Event::create([
            'code' => 'EVT-LV-001',
            'title' => 'Kegiatan Izin Uji',
            'location' => 'Aula Utama',
            'event_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
        ]);

        EventParticipant::create([
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
            'registered_at' => now(),
        ]);
    }

    public function test_member_can_submit_leave_request(): void
    {
        $file = UploadedFile::fake()->create('surat_dokter.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->member)->post(route('member.leaves.store', $this->event), [
            'type' => 'SAKIT',
            'reason' => 'Mengalami demam tinggi dan disarankan dokter istirahat.',
            'attachment' => $file,
        ]);

        $response->assertRedirect(route('member.events.show', $this->event));

        $this->assertDatabaseHas('leave_requests', [
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
            'type' => 'SAKIT',
            'status' => 'PENDING',
        ]);
    }

    public function test_member_cannot_submit_duplicate_leave_request(): void
    {
        LeaveRequest::create([
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
            'type' => 'IZIN',
            'reason' => 'Ada keperluan keluarga mendesak di luar kota.',
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->member)->post(route('member.leaves.store', $this->event), [
            'type' => 'IZIN',
            'reason' => 'Pengajuan kedua yang seharusnya ditolak sistem.',
        ]);

        $response->assertSessionHasErrors('leave');
        $this->assertEquals(1, LeaveRequest::where('event_id', $this->event->id)->where('user_id', $this->member->id)->count());
    }

    public function test_admin_can_approve_leave_request_and_updates_attendance(): void
    {
        $leave = LeaveRequest::create([
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
            'type' => 'IZIN',
            'reason' => 'Keperluan keluarga mendesak.',
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.leaves.review', $leave), [
            'action' => 'APPROVED',
            'admin_notes' => 'Disetujui oleh admin.',
        ]);

        $response->assertRedirect(route('admin.leaves.index'));

        $leave->refresh();
        $this->assertEquals('APPROVED', $leave->status);
        $this->assertEquals($this->admin->id, $leave->reviewed_by);

        // Verification: attendance record was automatically created with status IZIN
        $this->assertDatabaseHas('attendances', [
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
            'status' => 'IZIN',
        ]);
    }

    public function test_admin_can_reject_leave_request(): void
    {
        $leave = LeaveRequest::create([
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
            'type' => 'IZIN',
            'reason' => 'Alasan tidak jelas.',
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.leaves.review', $leave), [
            'action' => 'REJECTED',
            'admin_notes' => 'Alasan tidak memenuhi ketentuan organisasi.',
        ]);

        $response->assertRedirect(route('admin.leaves.index'));

        $leave->refresh();
        $this->assertEquals('REJECTED', $leave->status);

        // No attendance record created on rejection
        $this->assertDatabaseMissing('attendances', [
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
        ]);
    }
}
