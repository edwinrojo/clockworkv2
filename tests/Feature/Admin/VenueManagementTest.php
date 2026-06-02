<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_manager_can_create_a_venue(): void
    {
        $manager = User::factory()->eventManager()->create();

        $response = $this->actingAs($manager)->post(route('venues.store'), [
            'name' => 'Capitol Grounds',
            'address' => 'Digos City',
            'latitude' => 6.7495,
            'longitude' => 125.3557,
            'geofence_radius_meters' => 200,
            'accuracy_buffer_meters' => 50,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('venues.index'));

        $this->assertDatabaseHas('venues', [
            'name' => 'Capitol Grounds',
            'geofence_radius_meters' => 200,
        ]);
    }

    public function test_viewer_cannot_create_a_venue(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)
            ->post(route('venues.store'), [
                'name' => 'Test Venue',
                'latitude' => 6.7495,
                'longitude' => 125.3557,
                'accuracy_buffer_meters' => 50,
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_venue_with_events_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $venue = Venue::factory()->create();
        Event::factory()->create(['venue_id' => $venue->id]);

        $this->actingAs($admin)
            ->delete(route('venues.destroy', $venue))
            ->assertRedirect()
            ->assertSessionHasErrors('venue');

        $this->assertModelExists($venue);
    }
}
