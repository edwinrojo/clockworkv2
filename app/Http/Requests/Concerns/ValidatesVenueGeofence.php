<?php

namespace App\Http\Requests\Concerns;

trait ValidatesVenueGeofence
{
    protected function prepareVenueGeofence(): void
    {
        $polygon = $this->input('geofence_polygon');

        if (is_string($polygon) && $polygon !== '') {
            $decoded = json_decode($polygon, true);
            $this->merge([
                'geofence_polygon' => is_array($decoded) ? $decoded : null,
            ]);
        }

        if ($polygon === '' || $polygon === 'null') {
            $this->merge(['geofence_polygon' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function venueGeofenceRules(): array
    {
        return [
            'geofence_polygon' => ['nullable', 'array'],
            'geofence_polygon.*.lat' => ['required_with:geofence_polygon', 'numeric', 'between:-90,90'],
            'geofence_polygon.*.lng' => ['required_with:geofence_polygon', 'numeric', 'between:-180,180'],
        ];
    }
}
