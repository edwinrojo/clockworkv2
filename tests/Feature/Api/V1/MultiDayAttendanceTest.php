<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DuplicatePolicy;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\EventSession;
use App\Models\QrToken;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MultiDayAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_per_calendar_day_policy_allows_check_in_on_each_event_date(): void
    {
        $employee = User::factory()->employee()->create();
        $venue = Venue::factory()->create([
            'latitude' => 6.75,
            'longitude' => 125.35,
            'geofence_radius_meters' => 500,
        ]);

        $event = Event::factory()->live()->for($venue)->create([
            'duplicate_policy' => DuplicatePolicy::PerCalendarDay,
            'is_multi_day' => true,
        ]);
        $event->dates()->delete();

        $dayOne = EventDate::factory()->for($event)->create([
            'event_date' => today()->subDay()->toDateString(),
            'check_in_time' => '08:00:00',
            'late_cutoff_time' => '09:00:00',
            'check_out_time' => '17:00:00',
        ]);
        $dayTwo = EventDate::factory()->for($event)->create([
            'event_date' => today()->toDateString(),
            'check_in_time' => now()->subHour()->format('H:i:s'),
            'late_cutoff_time' => now()->addHour()->format('H:i:s'),
            'check_out_time' => '17:00:00',
        ]);

        $dayOneSession = EventSession::factory()->for($event)->create([
            'event_date_id' => $dayOne->id,
            'started_at' => today()->subDay(),
        ]);
        $dayTwoSession = EventSession::factory()->for($event)->create([
            'event_date_id' => $dayTwo->id,
        ]);

        QrToken::factory()
            ->for($dayOneSession)
            ->forPlainToken('day-one-token')
            ->create(['expires_at' => now()->addMinutes(5)]);

        QrToken::factory()
            ->for($dayTwoSession)
            ->forPlainToken('day-two-token')
            ->create(['expires_at' => now()->addMinutes(5)]);

        Sanctum::actingAs($employee);

        $this->postJson('/api/v1/check-in', [
            'qr_token' => 'day-one-token',
            'latitude' => 6.75,
            'longitude' => 125.35,
        ])->assertCreated()
            ->assertJsonPath('data.attendance.event_date_id', $dayOne->id);

        $this->postJson('/api/v1/check-in', [
            'qr_token' => 'day-two-token',
            'latitude' => 6.75,
            'longitude' => 125.35,
        ])->assertCreated()
            ->assertJsonPath('data.attendance.event_date_id', $dayTwo->id);

        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_per_event_policy_blocks_check_in_on_second_event_date(): void
    {
        $employee = User::factory()->employee()->create();
        $venue = Venue::factory()->create([
            'latitude' => 6.75,
            'longitude' => 125.35,
            'geofence_radius_meters' => 500,
        ]);

        $event = Event::factory()->live()->for($venue)->create([
            'duplicate_policy' => DuplicatePolicy::PerEvent,
            'is_multi_day' => true,
        ]);
        $event->dates()->delete();

        $dayOne = EventDate::factory()->for($event)->create([
            'event_date' => today()->subDay()->toDateString(),
            'check_in_time' => '08:00:00',
            'late_cutoff_time' => '09:00:00',
            'check_out_time' => '17:00:00',
        ]);
        $dayTwo = EventDate::factory()->for($event)->create([
            'event_date' => today()->toDateString(),
            'check_in_time' => now()->subHour()->format('H:i:s'),
            'late_cutoff_time' => now()->addHour()->format('H:i:s'),
            'check_out_time' => '17:00:00',
        ]);

        $dayOneSession = EventSession::factory()->for($event)->create([
            'event_date_id' => $dayOne->id,
            'started_at' => today()->subDay(),
        ]);
        $dayTwoSession = EventSession::factory()->for($event)->create([
            'event_date_id' => $dayTwo->id,
        ]);

        QrToken::factory()
            ->for($dayOneSession)
            ->forPlainToken('day-one-token')
            ->create(['expires_at' => now()->addMinutes(5)]);

        QrToken::factory()
            ->for($dayTwoSession)
            ->forPlainToken('day-two-token')
            ->create(['expires_at' => now()->addMinutes(5)]);

        Sanctum::actingAs($employee);

        $this->postJson('/api/v1/check-in', [
            'qr_token' => 'day-one-token',
            'latitude' => 6.75,
            'longitude' => 125.35,
        ])->assertCreated();

        $this->postJson('/api/v1/check-in', [
            'qr_token' => 'day-two-token',
            'latitude' => 6.75,
            'longitude' => 125.35,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'ALREADY_CHECKED_IN');

        $this->assertDatabaseCount('attendances', 1);
    }
}
