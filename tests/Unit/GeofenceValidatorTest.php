<?php

namespace Tests\Unit;

use App\Models\Venue;
use App\Services\Geofence\GeofenceValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeofenceValidatorTest extends TestCase
{
    #[Test]
    public function it_accepts_points_within_radius_geofence(): void
    {
        $venue = new Venue([
            'latitude' => 6.75,
            'longitude' => 125.35,
            'geofence_radius_meters' => 200,
            'accuracy_buffer_meters' => 0,
            'geofence_polygon' => null,
        ]);

        $validator = new GeofenceValidator;

        $this->assertTrue($validator->isWithin($venue, 6.7501, 125.3501));
    }

    #[Test]
    public function it_accepts_points_inside_polygon_geofence(): void
    {
        $venue = new Venue([
            'latitude' => 6.75,
            'longitude' => 125.35,
            'geofence_radius_meters' => null,
            'accuracy_buffer_meters' => 0,
            'geofence_polygon' => [
                ['lat' => 6.74, 'lng' => 125.34],
                ['lat' => 6.74, 'lng' => 125.36],
                ['lat' => 6.76, 'lng' => 125.36],
                ['lat' => 6.76, 'lng' => 125.34],
            ],
        ]);

        $validator = new GeofenceValidator;

        $this->assertTrue($validator->isWithin($venue, 6.75, 125.35));
        $this->assertFalse($validator->isWithin($venue, 6.80, 125.40));
    }
}
