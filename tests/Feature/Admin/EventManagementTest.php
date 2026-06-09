<?php

namespace Tests\Feature\Admin;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_manager_can_create_an_event(): void
    {
        $manager = User::factory()->eventManager()->create();
        $venue = Venue::factory()->create();
        $eventDate = now()->addDay()->toDateString();

        $response = $this->actingAs($manager)->post(route('events.store'), [
            'title' => 'Monday Convocation',
            'description' => 'Weekly assembly',
            'venue_id' => $venue->id,
            'type' => EventType::Convocation->value,
            'status' => EventStatus::Scheduled->value,
            'is_multi_day' => false,
            'schedule' => [
                [
                    'event_date' => $eventDate,
                    'check_in_time' => '08:00',
                    'check_out_time' => '17:00',
                    'late_cutoff_time' => '09:00',
                ],
            ],
            'qr_rotation_seconds' => 60,
            'duplicate_policy' => 'per_event',
        ]);

        $response->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('events', [
            'title' => 'Monday Convocation',
            'venue_id' => $venue->id,
            'created_by' => $manager->id,
            'is_multi_day' => false,
        ]);

        $event = Event::query()->where('title', 'Monday Convocation')->first();
        $this->assertNotNull($event);

        $this->assertDatabaseHas('event_dates', [
            'event_id' => $event->id,
            'check_in_time' => '08:00:00',
            'late_cutoff_time' => '09:00:00',
        ]);

        $this->assertSame($eventDate, $event->dates()->first()?->event_date->format('Y-m-d'));
    }

    public function test_viewer_can_list_events_but_cannot_create(): void
    {
        $viewer = User::factory()->viewer()->create();
        Event::factory()->create(['title' => 'Flag Raising']);

        $this->actingAs($viewer)
            ->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/Index')
                ->has('events.data', 1)
            );

        $this->actingAs($viewer)
            ->get(route('events.create'))
            ->assertForbidden();
    }

    public function test_event_with_sessions_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $event = Event::factory()->create();
        EventSession::factory()->create(['event_id' => $event->id]);

        $this->actingAs($admin)
            ->delete(route('events.destroy', $event))
            ->assertRedirect()
            ->assertSessionHasErrors('event');

        $this->assertModelExists($event);
    }

    public function test_multi_day_event_requires_at_least_two_dates(): void
    {
        $manager = User::factory()->eventManager()->create();
        $venue = Venue::factory()->create();

        $this->actingAs($manager)
            ->post(route('events.store'), [
                'title' => 'Two-day Workshop',
                'venue_id' => $venue->id,
                'type' => EventType::Training->value,
                'status' => EventStatus::Scheduled->value,
                'is_multi_day' => true,
                'schedule' => [
                    [
                        'event_date' => now()->addDay()->toDateString(),
                        'check_in_time' => '08:00',
                        'check_out_time' => '17:00',
                        'late_cutoff_time' => '09:00',
                    ],
                ],
                'qr_rotation_seconds' => 60,
                'duplicate_policy' => 'per_event',
            ])
            ->assertSessionHasErrors('schedule');
    }
}
