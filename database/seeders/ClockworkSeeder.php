<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Department;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class ClockworkSeeder extends Seeder
{
    public function run(): void
    {
        $hr = Department::factory()->create([
            'name' => 'Human Resource Management Office',
            'code' => 'HRMO',
        ]);

        User::factory()->superAdmin()->create([
            'name' => 'System Administrator',
            'email' => 'admin@clockwork.test',
        ]);

        User::factory()->eventManager()->create([
            'name' => 'Event Coordinator',
            'email' => 'coordinator@clockwork.test',
        ]);

        User::factory()->employee()->forDepartment($hr)->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'employee@clockwork.test',
            'employee_number' => 'EMP-00001',
        ]);

        $venue = Venue::factory()->create([
            'name' => 'Provincial Capitol Session Hall',
            'address' => 'Digos City, Davao del Sur',
            'latitude' => 6.7495,
            'longitude' => 125.3557,
            'geofence_radius_meters' => 200,
        ]);

        Event::factory()->create([
            'venue_id' => $venue->id,
            'title' => 'Monday Convocation',
            'type' => EventType::Convocation,
            'status' => EventStatus::Scheduled,
        ]);
    }
}
