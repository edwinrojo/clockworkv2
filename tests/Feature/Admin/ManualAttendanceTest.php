<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_manager_can_record_manual_attendance(): void
    {
        $manager = User::factory()->eventManager()->create();
        $event = Event::factory()->live()->create();
        $employee = User::factory()->employee()->create();

        $this->actingAs($manager)
            ->post(route('events.attendances.store', $event), [
                'user_id' => $employee->id,
                'reason' => 'Walk-in verified by HR staff',
                'status' => AttendanceStatus::ManualOverride->value,
            ])
            ->assertRedirect(route('events.attendances', $event));

        $this->assertDatabaseHas('attendances', [
            'event_id' => $event->id,
            'user_id' => $employee->id,
            'manual_override_by' => $manager->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $manager->id,
            'action' => 'manual_attendance',
        ]);
    }

    public function test_manual_attendance_rejects_duplicate(): void
    {
        $manager = User::factory()->eventManager()->create();
        $event = Event::factory()->live()->create();
        $employee = User::factory()->employee()->create();

        Attendance::factory()->for($event)->for($employee)->create();

        $this->actingAs($manager)
            ->post(route('events.attendances.store', $event), [
                'user_id' => $employee->id,
                'reason' => 'Duplicate attempt',
                'status' => AttendanceStatus::Present->value,
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_attendance_export_returns_csv(): void
    {
        $manager = User::factory()->eventManager()->create();
        $event = Event::factory()->live()->create();
        Attendance::factory()->for($event)->create();

        $response = $this->actingAs($manager)
            ->get(route('events.attendances.export', $event));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
