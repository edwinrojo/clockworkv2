<?php

namespace Tests\Feature\Admin;

use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\EventSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoStartEventSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_starts_session_when_check_in_time_is_reached(): void
    {
        $event = Event::factory()->create([
            'status' => EventStatus::Scheduled,
            'starts_at' => today()->startOfDay(),
            'ends_at' => today()->endOfDay(),
        ]);
        $event->dates()->delete();
        EventDate::factory()->for($event)->create([
            'event_date' => today(),
            'check_in_time' => now()->subMinute()->format('H:i:s'),
            'check_out_time' => '17:00:00',
            'late_cutoff_time' => now()->addHour()->format('H:i:s'),
        ]);

        $this->artisan('clockwork:auto-start-sessions')
            ->assertSuccessful();

        $session = EventSession::query()->where('event_id', $event->id)->first();
        $this->assertNotNull($session);
        $this->assertSame(EventSessionStatus::Active, $session->status);
        $this->assertSame(EventStatus::Live, $event->fresh()->status);
    }

    public function test_command_does_not_start_session_before_check_in_time(): void
    {
        $event = Event::factory()->create([
            'status' => EventStatus::Scheduled,
            'starts_at' => today()->startOfDay(),
            'ends_at' => today()->endOfDay(),
        ]);
        $event->dates()->delete();
        EventDate::factory()->for($event)->create([
            'event_date' => today(),
            'check_in_time' => now()->addHour()->format('H:i:s'),
            'check_out_time' => '17:00:00',
            'late_cutoff_time' => now()->addHours(2)->format('H:i:s'),
        ]);

        $this->artisan('clockwork:auto-start-sessions')
            ->assertSuccessful();

        $this->assertNull(EventSession::query()->where('event_id', $event->id)->first());
    }
}
