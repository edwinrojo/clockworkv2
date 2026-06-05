<?php

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
        Schema::table('attendances', function (Blueprint $table): void {
            $table->foreignUlid('event_date_id')
                ->nullable()
                ->after('event_session_id')
                ->constrained('event_dates')
                ->nullOnDelete();
        });

        $this->backfillEventDateIds();

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropUnique(['event_id', 'user_id']);
            $table->unique(['event_date_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropUnique(['event_date_id', 'user_id']);
            $table->unique(['event_id', 'user_id']);
            $table->dropConstrainedForeignId('event_date_id');
        });
    }

    private function backfillEventDateIds(): void
    {
        $attendances = DB::table('attendances')
            ->whereNull('event_date_id')
            ->orderBy('id')
            ->get(['id', 'event_id', 'event_session_id', 'checked_in_at']);

        foreach ($attendances as $attendance) {
            $eventDateId = DB::table('event_sessions')
                ->where('id', $attendance->event_session_id)
                ->value('event_date_id');

            if ($eventDateId === null) {
                $eventDateId = DB::table('event_dates')
                    ->where('event_id', $attendance->event_id)
                    ->whereDate('event_date', $attendance->checked_in_at)
                    ->value('id');
            }

            if ($eventDateId !== null) {
                DB::table('attendances')
                    ->where('id', $attendance->id)
                    ->update(['event_date_id' => $eventDateId]);
            }
        }
    }
};
