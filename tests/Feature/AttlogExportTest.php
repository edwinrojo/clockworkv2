<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use App\Services\Exports\AttlogExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttlogExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'clockwork.legacy_attlog_export.device_uid' => 99,
            'clockwork.legacy_attlog_export.state' => 0,
            'clockwork.legacy_attlog_export.mode' => 3,
            'clockwork.legacy_attlog_export.work_code' => 0,
        ]);
    }

    public function test_attlog_row_uses_employee_number_and_legacy_columns(): void
    {
        $event = Event::factory()->live()->create();
        $employee = User::factory()->employee()->create([
            'employee_number' => '10042',
        ]);

        Attendance::factory()->for($event)->for($employee)->create([
            'checked_in_at' => '2026-06-02 08:03:15',
        ]);

        $lines = app(AttlogExportService::class)->linesForEvent($event);

        $this->assertSame([
            '10042	2026-06-02 08:03:15	99	0	3	0',
        ], $lines);
    }

    public function test_attlog_export_skips_attendances_without_employee_number(): void
    {
        $event = Event::factory()->live()->create();
        $employee = User::factory()->employee()->create([
            'employee_number' => null,
        ]);

        Attendance::factory()->for($event)->for($employee)->create();

        $service = app(AttlogExportService::class);

        $this->assertSame([], $service->linesForEvent($event));
        $this->assertSame(1, $service->skippedCountForEvent($event));
    }

    public function test_attlog_export_download_returns_plain_text_dat_file(): void
    {
        $manager = User::factory()->eventManager()->create();
        $event = Event::factory()->live()->create();
        $employee = User::factory()->employee()->create([
            'employee_number' => 'EMP-20001',
        ]);

        Attendance::factory()->for($event)->for($employee)->create([
            'checked_in_at' => '2026-06-02 09:15:00',
        ]);

        $response = $this->actingAs($manager)
            ->get(route('events.attendances.export-attlog', $event));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/plain; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename=99_attlog.dat');
        $this->assertStringContainsString(
            'EMP-20001	2026-06-02 09:15:00	99	0	3	0',
            $response->streamedContent(),
        );
    }

    public function test_employee_cannot_export_attlog(): void
    {
        $employee = User::factory()->employee()->create();
        $event = Event::factory()->live()->create();

        $this->actingAs($employee)
            ->get(route('events.attendances.export-attlog', $event))
            ->assertForbidden();
    }
}
