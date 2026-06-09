<?php

namespace App\Services\Exports;

use App\Models\Attendance;
use App\Models\Event;
use Illuminate\Support\Collection;

class AttlogExportService
{
    /**
     * @return list<string>
     */
    public function linesForEvent(Event $event): array
    {
        $lines = [];

        Attendance::query()
            ->where('event_id', $event->id)
            ->with('user:id,employee_number')
            ->orderBy('checked_in_at')
            ->chunk(200, function (Collection $rows) use (&$lines): void {
                foreach ($rows as $attendance) {
                    $line = $this->formatRow($attendance);

                    if ($line !== null) {
                        $lines[] = $line;
                    }
                }
            });

        return $lines;
    }

    public function skippedCountForEvent(Event $event): int
    {
        return Attendance::query()
            ->where('event_id', $event->id)
            ->whereHas('user', fn ($query) => $query->whereNull('employee_number')->orWhere('employee_number', ''))
            ->count();
    }

    public function filename(): string
    {
        return $this->deviceUid().'_attlog.dat';
    }

    public function deviceUid(): int
    {
        return (int) config('clockwork.legacy_attlog_export.device_uid', 99);
    }

    public function formatRow(Attendance $attendance): ?string
    {
        $employeeNumber = $attendance->user->employee_number;

        if ($employeeNumber === null || $employeeNumber === '') {
            return null;
        }

        return implode("\t", [
            $employeeNumber,
            $attendance->checked_in_at->format('Y-m-d H:i:s'),
            (string) $this->deviceUid(),
            (string) $this->state(),
            (string) $this->mode(),
            (string) $this->workCode(),
        ]);
    }

    private function state(): int
    {
        return (int) config('clockwork.legacy_attlog_export.state', 0);
    }

    private function mode(): int
    {
        return (int) config('clockwork.legacy_attlog_export.mode', 3);
    }

    private function workCode(): int
    {
        return (int) config('clockwork.legacy_attlog_export.work_code', 0);
    }
}
