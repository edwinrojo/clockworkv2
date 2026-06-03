<?php

namespace Tests\Feature\Api\V1;

use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_check_in_eligible_live_events(): void
    {
        $employee = User::factory()->employee()->create();
        Sanctum::actingAs($employee);

        $eligible = Event::factory()->live()->create(['title' => 'Live Convocation']);
        EventSession::factory()->for($eligible)->create([
            'status' => EventSessionStatus::Active,
        ]);

        $scheduled = Event::factory()->create([
            'title' => 'Scheduled Event',
            'status' => EventStatus::Scheduled,
        ]);
        EventSession::factory()->for($scheduled)->create();

        $response = $this->getJson('/api/v1/events')->assertOk();

        $titles = collect($response->json('data.events'))->pluck('title');

        $this->assertTrue($titles->contains('Live Convocation'));
        $this->assertFalse($titles->contains('Scheduled Event'));
    }
}
