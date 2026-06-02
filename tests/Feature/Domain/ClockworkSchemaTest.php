<?php

namespace Tests\Feature\Domain;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\QrToken;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClockworkSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_use_ulid_primary_keys(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->getIncrementing());
        $this->assertSame('string', $user->getKeyType());
        $this->assertTrue(Str::isUlid($user->id));
    }

    public function test_domain_models_can_be_created_with_relationships(): void
    {
        $department = Department::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->employee()->forDepartment($department)->create();
        $venue = Venue::factory()->create();
        $event = Event::factory()->live()->create([
            'venue_id' => $venue->id,
            'created_by' => $admin->id,
        ]);
        $session = EventSession::factory()->create([
            'event_id' => $event->id,
            'started_by' => $admin->id,
        ]);
        $qrToken = QrToken::factory()->for($session)->create();

        $attendance = Attendance::factory()->create([
            'event_id' => $event->id,
            'event_session_id' => $session->id,
            'user_id' => $employee->id,
        ]);

        $this->assertTrue(Str::isUlid($event->id));
        $this->assertTrue(Str::isUlid($attendance->id));
        $this->assertTrue($event->venue->is($venue));
        $this->assertTrue($session->qrTokens->contains($qrToken));
        $this->assertTrue($employee->attendances->contains($attendance));
        $this->assertSame(UserRole::Employee, $employee->role);
    }

    public function test_duplicate_attendance_per_event_is_rejected(): void
    {
        $employee = User::factory()->employee()->create();
        $event = Event::factory()->live()->create();

        Attendance::factory()->create([
            'event_id' => $event->id,
            'user_id' => $employee->id,
            'event_session_id' => null,
        ]);

        $this->expectException(QueryException::class);

        Attendance::factory()->create([
            'event_id' => $event->id,
            'user_id' => $employee->id,
            'event_session_id' => null,
        ]);
    }

    public function test_event_generates_display_secret_on_create(): void
    {
        $event = Event::factory()->create(['display_secret' => null]);

        $this->assertNotNull($event->display_secret);
        $this->assertSame(64, strlen($event->display_secret));
    }
}
