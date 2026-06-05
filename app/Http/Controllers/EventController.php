<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Services\Event\EventScheduleService;
use App\Support\Admin\EventFormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(private EventScheduleService $scheduleService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $events = Event::query()
            ->with('venue:id,name')
            ->with('dates')
            ->withCount(['sessions', 'attendances'])
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (Event $event) => $this->eventPayload($event, $request));

        return Inertia::render('events/Index', [
            'events' => $events,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Event::class);

        return Inertia::render('events/Create', EventFormOptions::all());
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $schedule = $validated['schedule'];
        unset($validated['schedule']);

        $event = Event::query()->create([
            ...$validated,
            ...$this->scheduleBounds($schedule),
            'created_by' => $request->user()->id,
        ]);

        $this->scheduleService->syncEventDates(
            $event,
            $request->boolean('is_multi_day'),
            $schedule,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event created.')]);

        return to_route('events.index');
    }

    public function edit(Request $request, Event $event): Response
    {
        $this->authorize('update', $event);

        $event->load(['venue:id,name', 'dates'])->loadCount(['sessions', 'attendances']);

        return Inertia::render('events/Edit', [
            'event' => $this->eventPayload($event, $request),
            ...EventFormOptions::all(),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();
        $schedule = $validated['schedule'];
        unset($validated['schedule']);

        $event->update([
            ...$validated,
            ...$this->scheduleBounds($schedule),
        ]);

        $this->scheduleService->syncEventDates(
            $event,
            $request->boolean('is_multi_day'),
            $schedule,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event updated.')]);

        return to_route('events.index');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        if ($event->sessions()->exists()) {
            return back()->withErrors([
                'event' => __('This event has check-in sessions and cannot be deleted.'),
            ]);
        }

        if ($event->attendances()->exists()) {
            return back()->withErrors([
                'event' => __('This event has attendance records and cannot be deleted.'),
            ]);
        }

        $event->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event deleted.')]);

        return to_route('events.index');
    }

    /**
     * @param  list<array{event_date: string}>  $schedule
     * @return array{starts_at: string, ends_at: string}
     */
    private function scheduleBounds(array $schedule): array
    {
        $firstDate = $schedule[0]['event_date'];
        $lastDate = $schedule[array_key_last($schedule)]['event_date'];

        return [
            'starts_at' => $firstDate.' 00:00:00',
            'ends_at' => $lastDate.' 23:59:59',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(Event $event, Request $request): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'venue_id' => $event->venue_id,
            'venue_name' => $event->venue?->name,
            'type' => $event->type->value,
            'type_label' => $event->type->label(),
            'status' => $event->status->value,
            'status_label' => $event->status->label(),
            'is_multi_day' => $event->is_multi_day,
            'schedule' => $event->dates->map(fn ($date) => [
                'event_date' => $date->event_date->format('Y-m-d'),
                'check_in_time' => substr((string) $date->check_in_time, 0, 5),
                'check_out_time' => substr((string) $date->check_out_time, 0, 5),
                'late_cutoff_time' => substr((string) $date->late_cutoff_time, 0, 5),
            ])->values()->all(),
            'starts_at' => $event->starts_at?->format('Y-m-d'),
            'ends_at' => $event->ends_at?->format('Y-m-d'),
            'qr_rotation_seconds' => $event->qr_rotation_seconds,
            'duplicate_policy' => $event->duplicate_policy->value,
            'duplicate_policy_label' => $event->duplicate_policy->label(),
            'sessions_count' => $event->sessions_count ?? 0,
            'attendances_count' => $event->attendances_count ?? 0,
            'can' => [
                'update' => $request->user()?->can('update', $event) ?? false,
                'delete' => $request->user()?->can('delete', $event) ?? false,
                'manageSession' => $request->user()?->can('manageSession', $event) ?? false,
                'viewAttendances' => $request->user()?->can('viewAttendances', $event) ?? false,
            ],
        ];
    }
}
