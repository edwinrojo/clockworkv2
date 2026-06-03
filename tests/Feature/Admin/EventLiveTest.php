<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventLiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_manager_can_view_live_operations_page(): void
    {
        $manager = User::factory()->eventManager()->create();
        $event = Event::factory()->live()->create();

        $this->actingAs($manager)
            ->get(route('events.live', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/Live')
                ->where('event.id', $event->id)
                ->has('can')
            );
    }
}
