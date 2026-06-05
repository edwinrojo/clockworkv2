<?php

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('events', 'is_multi_day')) {
            Schema::table('events', function (Blueprint $table): void {
                $table->boolean('is_multi_day')->default(false)->after('status');
            });
        }

        if (! Schema::hasTable('event_dates')) {
            Schema::create('event_dates', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
                $table->date('event_date');
                $table->time('check_in_time');
                $table->time('check_out_time');
                $table->time('late_cutoff_time');
                $table->timestamps();

                $table->unique(['event_id', 'event_date']);
                $table->index(['event_date', 'check_in_time']);
            });
        }

        if (! Schema::hasColumn('event_sessions', 'event_date_id')) {
            Schema::table('event_sessions', function (Blueprint $table): void {
                $table->foreignUlid('event_date_id')->nullable()->after('event_id')->constrained('event_dates')->nullOnDelete();
            });
        }

        $this->migrateExistingEvents();
        $this->linkExistingSessions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('event_sessions', 'event_date_id')) {
            Schema::table('event_sessions', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('event_date_id');
            });
        }

        Schema::dropIfExists('event_dates');

        if (Schema::hasColumn('events', 'is_multi_day')) {
            Schema::table('events', function (Blueprint $table): void {
                $table->dropColumn('is_multi_day');
            });
        }
    }

    private function migrateExistingEvents(): void
    {
        Event::query()->each(function (Event $event): void {
            if (DB::table('event_dates')->where('event_id', $event->id)->exists()) {
                return;
            }

            $startsAt = $event->starts_at;
            $endsAt = $event->ends_at;

            if ($startsAt === null || $endsAt === null) {
                return;
            }

            $checkInOpens = $event->check_in_opens_at ?? $startsAt;
            $lateCutoff = $event->check_in_closes_at ?? $startsAt->copy()->addHour();
            $checkOut = $event->check_in_closes_at ?? $endsAt;

            $startDate = $startsAt->copy()->startOfDay();
            $endDate = $endsAt->copy()->startOfDay();

            if ($endDate->lt($startDate)) {
                $endDate = $startDate->copy();
            }

            $isMultiDay = ! $startDate->isSameDay($endDate);

            $event->update(['is_multi_day' => $isMultiDay]);

            $cursor = $startDate->copy();
            $seenDates = [];

            while ($cursor->lte($endDate)) {
                $dateString = $cursor->toDateString();

                if (isset($seenDates[$dateString])) {
                    $cursor->addDay();

                    continue;
                }

                $seenDates[$dateString] = true;

                $isFirstDay = $cursor->isSameDay($startDate);
                $isLastDay = $cursor->isSameDay($endDate);

                DB::table('event_dates')->insert([
                    'id' => (string) str()->ulid(),
                    'event_id' => $event->id,
                    'event_date' => $dateString,
                    'check_in_time' => ($isFirstDay ? $checkInOpens : $startsAt->copy()->startOfDay())->format('H:i:s'),
                    'check_out_time' => ($isLastDay ? $checkOut : $endsAt->copy()->endOfDay())->format('H:i:s'),
                    'late_cutoff_time' => ($isFirstDay ? $lateCutoff : $startsAt->copy()->setTime(9, 0))->format('H:i:s'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $cursor->addDay();
            }

            if ($event->status === EventStatus::Live) {
                $event->update(['status' => EventStatus::Scheduled]);
            }
        });
    }

    private function linkExistingSessions(): void
    {
        $sessions = DB::table('event_sessions')
            ->whereNull('event_date_id')
            ->get(['id', 'event_id', 'started_at']);

        foreach ($sessions as $session) {
            $startedAt = $session->started_at ?? now();

            $eventDateId = DB::table('event_dates')
                ->where('event_id', $session->event_id)
                ->whereDate('event_date', $startedAt)
                ->value('id');

            if ($eventDateId === null) {
                $eventDateId = DB::table('event_dates')
                    ->where('event_id', $session->event_id)
                    ->orderBy('event_date')
                    ->value('id');
            }

            if ($eventDateId !== null) {
                DB::table('event_sessions')
                    ->where('id', $session->id)
                    ->update(['event_date_id' => $eventDateId]);
            }
        }
    }
};
