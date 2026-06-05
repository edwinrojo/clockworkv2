<?php

namespace App\Console\Commands;

use App\Services\Event\EventScheduleService;
use App\Services\Event\EventSessionService;
use Illuminate\Console\Command;

class AutoStartEventSessionsCommand extends Command
{
    protected $signature = 'clockwork:auto-start-sessions';

    protected $description = 'Start check-in sessions when scheduled check-in time is reached';

    public function handle(
        EventScheduleService $scheduleService,
        EventSessionService $sessionService,
    ): int {
        $started = 0;

        foreach ($scheduleService->schedulesReadyForAutoStart() as $schedule) {
            $event = $schedule->event;

            if ($event === null) {
                continue;
            }

            $sessionService->autoStart(
                $event,
                $schedule,
                $scheduleService->resolveStarterForAutoStart($event),
            );

            $started++;
            $this->info("Started session for {$event->title} on {$schedule->event_date->toDateString()}.");
        }

        if ($started === 0) {
            $this->comment('No sessions to auto-start.');
        }

        return self::SUCCESS;
    }
}
