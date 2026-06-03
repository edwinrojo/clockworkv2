<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Support\Admin\EventFormOptions;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $events = Event::query()
            ->with('venue:id,name')
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
        Event::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event created.')]);

        return to_route('events.index');
    }

    public function edit(Request $request, Event $event): Response
    {
        $this->authorize('update', $event);

        $event->load('venue:id,name')->loadCount(['sessions', 'attendances']);

        return Inertia::render('events/Edit', [
            'event' => $this->eventPayload($event, $request),
            ...EventFormOptions::all(),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());

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
            'starts_at' => $this->formatDateTimeLocal($event->starts_at),
            'ends_at' => $this->formatDateTimeLocal($event->ends_at),
            'check_in_opens_at' => $this->formatDateTimeLocal($event->check_in_opens_at),
            'check_in_closes_at' => $this->formatDateTimeLocal($event->check_in_closes_at),
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

    private function formatDateTimeLocal(?CarbonInterface $dateTime): ?string
    {
        return $dateTime?->format('Y-m-d\TH:i');
    }
}
