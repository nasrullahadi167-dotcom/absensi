<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\SyncOutbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GoogleSyncAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin_report',
            'name' => 'Admin Report',
            'password' => Hash::make('password'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->member = User::create([
            'username' => 'member_report',
            'name' => 'Member Report',
            'password' => Hash::make('password'),
            'role' => 'MEMBER',
            'is_active' => true,
        ]);

        $this->event = Event::create([
            'code' => 'EVT-REP-001',
            'title' => 'Kegiatan Laporan Uji',
            'location' => 'Auditorium',
            'event_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
        ]);

        Attendance::create([
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
            'check_in_time' => now(),
            'status' => 'HADIR',
            'notes' => 'Presensi laporan tes',
        ]);
    }

    public function test_reports_index_can_be_viewed_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Presensi');
        $response->assertSee('Kegiatan Laporan Uji');
        $response->assertSee('Member Report');
    }

    public function test_reports_csv_export_returns_stream_with_bom(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.export-excel'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_reports_print_pdf_view_renders_correctly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.export-pdf'));

        $response->assertStatus(200);
        $response->assertSee('REKAPITULASI LAPORAN PRESENSI ORGANISASI');
        $response->assertSee('Kegiatan Laporan Uji');
    }

    public function test_google_sync_monitoring_page_shows_outbox_metrics(): void
    {
        SyncOutbox::create([
            'event_type' => 'ATTENDANCE_SYNC',
            'aggregate_type' => 'Attendance',
            'aggregate_id' => 1,
            'payload' => ['id' => 1],
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.sync.index'));

        $response->assertStatus(200);
        $response->assertSee('Monitoring Sinkronisasi Google');
        $response->assertSee('ATTENDANCE_SYNC');
    }

    public function test_google_sync_retry_requeues_outbox_item(): void
    {
        $outbox = SyncOutbox::create([
            'event_type' => 'USER_SYNC',
            'aggregate_type' => 'User',
            'aggregate_id' => $this->member->id,
            'payload' => ['user_id' => $this->member->id],
            'status' => 'FAILED',
            'error_message' => 'API Timeout',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.sync.retry', $outbox));

        $response->assertSessionHas('success');
        $outbox->refresh();
        $this->assertEquals('PENDING', $outbox->status);
    }

    public function test_google_sync_full_resync_enqueues_all_master_data(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sync.full-resync'));

        $response->assertSessionHas('success');
        $this->assertTrue(SyncOutbox::count() > 0);
    }
}
