<?php

namespace Tests\Feature\Display;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DisplayPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_requires_pin_when_configured(): void
    {
        $event = Event::factory()->live()->create([
            'display_pin_hash' => Hash::make('1234'),
        ]);

        $this->get(route('display.show', $event->display_secret))
            ->assertRedirect(route('display.unlock', $event->display_secret));
    }

    public function test_event_manager_can_save_display_pin(): void
    {
        $manager = User::factory()->eventManager()->create();
        $event = Event::factory()->live()->create();

        $this->actingAs($manager)
            ->post(route('events.display-pin.update', $event), ['pin' => '5678'])
            ->assertRedirect();

        $event->refresh();

        $this->assertNotNull($event->display_pin_hash);
        $this->assertTrue(Hash::check('5678', $event->display_pin_hash));
    }

    public function test_correct_pin_unlocks_display(): void
    {
        $event = Event::factory()->live()->create([
            'display_pin_hash' => Hash::make('1234'),
        ]);

        $this->post(route('display.unlock.store', $event->display_secret), [
            'pin' => '1234',
        ])
            ->assertRedirect(route('display.show', $event->display_secret));

        $this->get(route('display.show', $event->display_secret))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('display/Show'));
    }
}
