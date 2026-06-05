<?php

namespace App\Services\Geofence;

use App\Models\Venue;

class GeofenceValidator
{
    public function isWithin(Venue $venue, float $latitude, float $longitude, ?float $accuracyMeters = null): bool
    {
        return $this->evaluate($venue, $latitude, $longitude, $accuracyMeters)['within'];
    }

    /**
     * @return array{
     *     within: bool,
     *     mode: string,
     *     distance_meters: float|null,
     *     allowed_meters: float|null,
     *     tolerance_meters: float
     * }
     */
    public function evaluate(Venue $venue, float $latitude, float $longitude, ?float $accuracyMeters = null): array
    {
        $toleranceMeters = (float) $venue->accuracy_buffer_meters;

        $effectiveAccuracy = $accuracyMeters ?? (float) config('clockwork.default_gps_accuracy_meters', 30);
        $toleranceMeters += $effectiveAccuracy;

        $polygon = $venue->geofence_polygon;

        if (is_array($polygon) && $polygon !== []) {
            return [
                'within' => $this->pointInPolygon($latitude, $longitude, $polygon),
                'mode' => 'polygon',
                'distance_meters' => null,
                'allowed_meters' => null,
                'tolerance_meters' => $toleranceMeters,
            ];
        }

        if ($venue->geofence_radius_meters !== null) {
            $distance = $this->distanceMeters(
                (float) $venue->latitude,
                (float) $venue->longitude,
                $latitude,
                $longitude,
            );
            $allowed = (float) $venue->geofence_radius_meters + $toleranceMeters;

            return [
                'within' => $distance <= $allowed,
                'mode' => 'radius',
                'distance_meters' => round($distance, 1),
                'allowed_meters' => round($allowed, 1),
                'tolerance_meters' => $toleranceMeters,
            ];
        }

        return [
            'within' => false,
            'mode' => 'none',
            'distance_meters' => null,
            'allowed_meters' => null,
            'tolerance_meters' => $toleranceMeters,
        ];
    }

    /**
     * @param  list<array{lat: float|int|string, lng: float|int|string}|list{float|int|string}>  $vertices
     */
    private function pointInPolygon(float $latitude, float $longitude, array $vertices): bool
    {
        $points = array_map(function (array $vertex): array {
            if (array_is_list($vertex) && count($vertex) >= 2) {
                return [(float) $vertex[0], (float) $vertex[1]];
            }

            return [(float) $vertex['lat'], (float) $vertex['lng']];
        }, $vertices);

        $inside = false;
        $count = count($points);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $yi = $points[$i][0];
            $xi = $points[$i][1];
            $yj = $points[$j][0];
            $xj = $points[$j][1];

            $intersects = (($yi > $latitude) !== ($yj > $latitude))
                && ($longitude < ($xj - $xi) * ($latitude - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
