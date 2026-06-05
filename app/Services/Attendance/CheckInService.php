<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceSource;
use App\Enums\CheckInErrorCode;
use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Exceptions\CheckInException;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\QrToken;
use App\Models\User;
use App\Models\Venue;
use App\Services\Event\EventScheduleService;
use App\Services\Geofence\GeofenceValidator;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CheckInService
{
    public function __construct(
        private GeofenceValidator $geofenceValidator,
        private AttendanceDuplicateGuard $duplicateGuard,
    ) {}

    /**
     * @return array{attendance: Attendance, replayed: bool}
     */
    public function checkIn(
        User $user,
        string $qrToken,
        float $latitude,
        float $longitude,
        ?float $accuracyMeters,
        ?CarbonInterface $gpsCapturedAt,
        ?string $idempotencyKey = null,
    ): array {
        if (! $user->isEmployee() || ! $user->is_active) {
            throw new CheckInException(CheckInErrorCode::Unauthorized);
        }

        if ($idempotencyKey !== null) {
            $existing = Attendance::query()
                ->where('idempotency_key', $idempotencyKey)
                ->where('user_id', $user->id)
                ->first();

            if ($existing !== null) {
                return ['attendance' => $existing->load(['event.venue']), 'replayed' => true];
            }
        }

        $token = QrToken::query()
            ->where('token_hash', hash('sha256', $qrToken))
            ->with(['eventSession.event.venue', 'eventSession.eventDate'])
            ->first();

        if ($token === null) {
            throw new CheckInException(CheckInErrorCode::InvalidQr);
        }

        if ($token->isExpired()) {
            throw new CheckInException(CheckInErrorCode::QrExpired);
        }

        $session = $token->eventSession;
        $event = $session->event;

        $this->assertEventIsOpenForCheckIn($event, $session);

        $venue = $this->venueForGeofenceCheck($event->venue);

        $geofence = $this->geofenceValidator->evaluate($venue, $latitude, $longitude, $accuracyMeters);

        if (! $geofence['within']) {
            throw new CheckInException(CheckInErrorCode::OutsideGeofence, [
                'geofence' => $geofence,
                'submitted' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy' => $accuracyMeters,
                ],
                'venue' => [
                    'latitude' => (float) $venue->latitude,
                    'longitude' => (float) $venue->longitude,
                ],
            ]);
        }

        $checkedInAt = now();
        $schedule = $session->eventDate
            ?? app(EventScheduleService::class)->scheduleForDate($event, $checkedInAt);
        $status = app(AttendanceStatusResolver::class)->forCheckIn($event, $checkedInAt, $schedule);

        $attendance = DB::transaction(function () use (
            $user,
            $event,
            $session,
            $schedule,
            $latitude,
            $longitude,
            $accuracyMeters,
            $gpsCapturedAt,
            $idempotencyKey,
            $checkedInAt,
            $status,
        ): Attendance {
            $this->duplicateGuard->assertNotDuplicate($user, $event, $schedule);

            return Attendance::query()->create([
                'event_id' => $event->id,
                'event_session_id' => $session->id,
                'event_date_id' => $schedule?->id,
                'user_id' => $user->id,
                'checked_in_at' => $checkedInAt,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy_meters' => $accuracyMeters,
                'gps_captured_at' => $gpsCapturedAt ?? $checkedInAt,
                'source' => AttendanceSource::Mobile,
                'status' => $status,
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        return [
            'attendance' => $attendance->load(['event.venue', 'eventDate']),
            'replayed' => false,
        ];
    }

    private function assertEventIsOpenForCheckIn(Event $event, EventSession $session): void
    {
        if ($event->status !== EventStatus::Live) {
            throw new CheckInException(CheckInErrorCode::EventNotActive);
        }

        if ($session->status !== EventSessionStatus::Active) {
            throw new CheckInException(CheckInErrorCode::EventNotActive);
        }

        $schedule = $session->eventDate
            ?? app(EventScheduleService::class)->scheduleForToday($event);

        if ($schedule === null) {
            throw new CheckInException(CheckInErrorCode::EventNotActive);
        }

        if (now()->lt($schedule->checkInOpensAt())) {
            throw new CheckInException(CheckInErrorCode::EventNotActive);
        }
    }

    private function venueForGeofenceCheck(?Venue $venue): ?Venue
    {
        if ($venue === null) {
            return null;
        }

        $ttl = (int) config('clockwork.venue_geofence_cache_seconds', 300);

        if ($ttl <= 0) {
            return $venue;
        }

        /** @var array<string, mixed> $attributes */
        $attributes = Cache::remember(
            "venue:geofence:{$venue->id}",
            $ttl,
            function () use ($venue): array {
                $fresh = $venue->fresh() ?? $venue;

                return [
                    'id' => $fresh->id,
                    'latitude' => $fresh->latitude,
                    'longitude' => $fresh->longitude,
                    'geofence_radius_meters' => $fresh->geofence_radius_meters,
                    'geofence_polygon' => $fresh->geofence_polygon,
                    'accuracy_buffer_meters' => $fresh->accuracy_buffer_meters,
                ];
            },
        );

        return (new Venue)->forceFill($attributes)->syncOriginal();
    }
}
