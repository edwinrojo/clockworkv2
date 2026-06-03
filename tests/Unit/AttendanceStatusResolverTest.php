<?php

namespace Tests\Unit;

use App\Enums\AttendanceStatus;
use App\Models\Event;
use App\Services\Attendance\AttendanceStatusResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceStatusResolverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_attendance_as_late_after_grace_period(): void
    {
        config(['clockwork.late_grace_minutes' => 15]);

        $event = Event::factory()->live()->create([
            'check_in_opens_at' => now()->subHour(),
            'starts_at' => now()->subHour(),
        ]);

        $resolver = new AttendanceStatusResolver;

        $this->assertSame(
            AttendanceStatus::Late,
            $resolver->forCheckIn($event, now()),
        );
    }

    #[Test]
    public function it_marks_attendance_as_present_within_grace_period(): void
    {
        config(['clockwork.late_grace_minutes' => 30]);

        $event = Event::factory()->live()->create([
            'check_in_opens_at' => now()->subMinutes(10),
            'starts_at' => now()->subMinutes(10),
        ]);

        $resolver = new AttendanceStatusResolver;

        $this->assertSame(
            AttendanceStatus::Present,
            $resolver->forCheckIn($event, now()),
        );
    }
}
