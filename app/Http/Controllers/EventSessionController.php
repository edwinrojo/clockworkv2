<?php

namespace App\Http\Controllers;

use App\Enums\EventSessionStatus;
use App\Models\Event;
use App\Services\Event\EventSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventSessionController extends Controller
{
    public function __construct(private EventSessionService $sessionService) {}

    public function start(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('manageSession', $event);

        $this->sessionService->start($event, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Check-in session started.')]);

        return to_route('events.live', $event);
    }

    public function pause(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('manageSession', $event);

        $session = $event->sessions()
            ->where('status', EventSessionStatus::Active)
            ->latest('started_at')
            ->first() ?? abort(404, __('No active session found.'));

        $this->sessionService->pause($session);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Session paused.')]);

        return to_route('events.live', $event);
    }

    public function resume(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('manageSession', $event);

        $session = $event->sessions()
            ->where('status', EventSessionStatus::Paused)
            ->latest('started_at')
            ->first() ?? abort(404, __('No paused session found.'));

        $this->sessionService->resume($session);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Session resumed.')]);

        return to_route('events.live', $event);
    }

    public function end(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('manageSession', $event);

        $session = $this->sessionService->activeSessionFor($event)
            ?? abort(404, __('No session to end.'));

        $this->sessionService->end($session);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Check-in session ended.')]);

        return to_route('events.live', $event);
    }

    public function rotate(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('manageSession', $event);

        $session = $event->sessions()
            ->where('status', EventSessionStatus::Active)
            ->latest('started_at')
            ->first() ?? abort(404, __('No active session found.'));

        $this->sessionService->rotateNow($session);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('QR code rotated.')]);

        return to_route('events.live', $event);
    }
}
