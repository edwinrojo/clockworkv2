<?php

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $latitude = fake()->latitude(6.4, 6.9);
        $longitude = fake()->longitude(125.0, 125.6);

        return [
            'name' => fake()->words(3, true).' Hall',
            'address' => fake()->address(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'geofence_radius_meters' => 150,
            'geofence_polygon' => null,
            'accuracy_buffer_meters' => 50,
            'is_active' => true,
        ];
    }

    /**
     * @param  list<array{lat: float, lng: float}>  $vertices
     */
    public function withPolygonGeofence(array $vertices): static
    {
        return $this->state(fn (array $attributes) => [
            'geofence_polygon' => $vertices,
            'geofence_radius_meters' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
