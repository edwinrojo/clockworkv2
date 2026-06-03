<?php

namespace App\Http\Controllers;

use App\Enums\EventSessionStatus;
use App\Models\Event;
use App\Services\Qr\QrTokenService;
use App\Support\Display\DisplayAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QrDisplayController extends Controller
{
    public function __construct(private QrTokenService $qrTokenService) {}

    public function show(Request $request, string $displaySecret): Response|RedirectResponse
    {
        $event = $this->resolveEvent($displaySecret);

        if (DisplayAccess::requiresPin($event) && ! DisplayAccess::isUnlocked($event, $request)) {
            return redirect()->route('display.unlock', $displaySecret);
        }

        return Inertia::render('display/Show', [
            'event' => [
                'title' => $event->title,
                'venue_name' => $event->venue?->name,
                'qr_rotation_seconds' => $event->qr_rotation_seconds,
            ],
            'displaySecret' => $displaySecret,
            'tokenUrl' => route('display.token', $displaySecret),
        ]);
    }

    public function token(Request $request, string $displaySecret): JsonResponse
    {
        $event = $this->resolveEvent($displaySecret);

        if (DisplayAccess::requiresPin($event) && ! DisplayAccess::isUnlocked($event, $request)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = $event->sessions()
            ->where('status', EventSessionStatus::Active)
            ->latest('started_at')
            ->first();

        if ($session === null) {
            return response()->json([
                'active' => false,
                'message' => __('Check-in is not open yet.'),
            ]);
        }

        $token = $this->qrTokenService->currentToken($session);

        if ($token === null) {
            return response()->json([
                'active' => false,
                'message' => __('Waiting for QR code…'),
            ]);
        }

        return response()->json([
            'active' => true,
            'qr_token' => $token['plain'],
            'expires_at' => $token['expires_at'],
            'seconds_remaining' => max(0, $token['expires_at'] - now()->getTimestamp()),
            'event_title' => $event->title,
        ]);
    }

    private function resolveEvent(string $displaySecret): Event
    {
        return Event::query()
            ->where('display_secret', $displaySecret)
            ->with('venue:id,name')
            ->firstOrFail();
    }
}
