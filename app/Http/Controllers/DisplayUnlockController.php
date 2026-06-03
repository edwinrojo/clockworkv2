<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnlockDisplayRequest;
use App\Models\Event;
use App\Support\Display\DisplayAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class DisplayUnlockController extends Controller
{
    public function create(string $displaySecret): Response|RedirectResponse
    {
        $event = $this->resolveEvent($displaySecret);

        if (! DisplayAccess::requiresPin($event)) {
            return redirect()->route('display.show', $displaySecret);
        }

        return Inertia::render('display/Unlock', [
            'eventTitle' => $event->title,
            'displaySecret' => $displaySecret,
        ]);
    }

    public function store(UnlockDisplayRequest $request, string $displaySecret): RedirectResponse
    {
        $event = $this->resolveEvent($displaySecret);

        if ($event->display_pin_hash === null || ! Hash::check($request->validated('pin'), $event->display_pin_hash)) {
            return back()->withErrors([
                'pin' => __('Invalid display PIN.'),
            ]);
        }

        $request->session()->put(DisplayAccess::sessionKey($event), true);

        return redirect()->route('display.show', $displaySecret);
    }

    private function resolveEvent(string $displaySecret): Event
    {
        return Event::query()
            ->where('display_secret', $displaySecret)
            ->firstOrFail();
    }
}
