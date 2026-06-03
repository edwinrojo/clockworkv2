<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEventDisplayPinRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class EventDisplayPinController extends Controller
{
    public function update(UpdateEventDisplayPinRequest $request, Event $event): RedirectResponse
    {
        if ($request->filled('pin')) {
            $event->update([
                'display_pin_hash' => Hash::make($request->validated('pin')),
            ]);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Display PIN updated.')]);
        } else {
            $event->update(['display_pin_hash' => null]);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Display PIN removed.')]);
        }

        return back();
    }
}
