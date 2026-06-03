<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceSource;
use App\Enums\CheckInErrorCode;
use App\Enums\DuplicatePolicy;
use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Exceptions\CheckInException;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\QrToken;
use App\Models\User;
use App\Services\Geofence\GeofenceValidator;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CheckInService
{
    public function __construct(private GeofenceValidator $geofenceValidator) {}

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
            ->with(['eventSession.event.venue'])
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

        $venue = $event->venue;

        if (! $this->geofenceValidator->isWithin($venue, $latitude, $longitude, $accuracyMeters)) {
            throw new CheckInException(CheckInErrorCode::OutsideGeofence);
        }

        $checkedInAt = now();
        $status = app(AttendanceStatusResolver::class)->forCheckIn($event, $checkedInAt);

        $attendance = DB::transaction(function () use (
            $user,
            $event,
            $session,
            $latitude,
            $longitude,
            $accuracyMeters,
            $gpsCapturedAt,
            $idempotencyKey,
            $checkedInAt,
            $status,
        ): Attendance {
            $this->assertNotDuplicate($user, $event);

            return Attendance::query()->create([
                'event_id' => $event->id,
                'event_session_id' => $session->id,
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
            'attendance' => $attendance->load(['event.venue']),
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

        $now = now();

        if ($event->check_in_opens_at !== null && $now->lt($event->check_in_opens_at)) {
            throw new CheckInException(CheckInErrorCode::EventNotActive);
        }

        if ($event->check_in_closes_at !== null && $now->gt($event->check_in_closes_at)) {
            throw new CheckInException(CheckInErrorCode::EventNotActive);
        }
    }

    private function assertNotDuplicate(User $user, Event $event): void
    {
        $query = Attendance::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id);

        if ($event->duplicate_policy === DuplicatePolicy::PerCalendarDay) {
            $query->whereDate('checked_in_at', today());
        }

        if ($query->exists()) {
            throw new CheckInException(CheckInErrorCode::AlreadyCheckedIn);
        }
    }
}
