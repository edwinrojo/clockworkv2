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
    public function it_marks_attendance_as_late_after_on_time_cutoff(): void
    {
        $event = Event::factory()->live()->create();
        $schedule = $event->dates()->first();
        $this->assertNotNull($schedule);

        $schedule->update([
            'late_cutoff_time' => now()->subMinutes(30)->format('H:i:s'),
        ]);

        $resolver = app(AttendanceStatusResolver::class);

        $this->assertSame(
            AttendanceStatus::Late,
            $resolver->forCheckIn($event, now(), $schedule->fresh()),
        );
    }

    #[Test]
    public function it_marks_attendance_as_present_on_or_before_on_time_cutoff(): void
    {
        $event = Event::factory()->live()->create();
        $schedule = $event->dates()->first();
        $this->assertNotNull($schedule);

        $schedule->update([
            'late_cutoff_time' => now()->addMinutes(30)->format('H:i:s'),
        ]);

        $resolver = app(AttendanceStatusResolver::class);

        $this->assertSame(
            AttendanceStatus::Present,
            $resolver->forCheckIn($event, now(), $schedule->fresh()),
        );
    }
}
