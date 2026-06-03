<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\Reports\AttendanceReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private AttendanceReportService $reports) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;

        return Inertia::render('reports/Index', [
            'filters' => [
                'from' => $from ?? now()->subDays(30)->format('Y-m-d'),
                'to' => $to ?? now()->format('Y-m-d'),
            ],
            'events' => $this->reports->eventsInRange($from, $to),
        ]);
    }

    public function show(Event $event): Response
    {
        $this->authorize('view', $event);

        return Inertia::render('reports/Show', $this->reports->eventDetail($event));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Event::class);

        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;

        $filename = 'attendance-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($from, $to): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Event',
                'Venue',
                'Status',
                'Starts At',
                'Attendances',
            ]);

            foreach ($this->reports->eventsInRange($from, $to) as $row) {
                fputcsv($handle, [
                    $row['title'],
                    $row['venue_name'],
                    $row['status_label'],
                    $row['starts_at'],
                    $row['attendances_count'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
