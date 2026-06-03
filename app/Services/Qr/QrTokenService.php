<?php

namespace App\Services\Qr;

use App\Enums\EventSessionStatus;
use App\Models\EventSession;
use App\Models\QrToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QrTokenService
{
    /**
     * @return array{plain: string, expires_at: int}
     */
    public function issueToken(EventSession $session): array
    {
        $session->loadMissing('event');

        $plainToken = Str::random(48);
        $issuedAt = now();
        $expiresAt = $issuedAt->copy()->addSeconds($session->event->qr_rotation_seconds);

        QrToken::query()->create([
            'event_session_id' => $session->id,
            'token_hash' => hash('sha256', $plainToken),
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ]);

        $payload = [
            'plain' => $plainToken,
            'expires_at' => $expiresAt->getTimestamp(),
        ];

        Cache::put($this->cacheKey($session), $payload, $expiresAt);

        return $payload;
    }

    /**
     * @return array{plain: string, expires_at: int}|null
     */
    public function rotate(EventSession $session): ?array
    {
        if ($session->status !== EventSessionStatus::Active) {
            return null;
        }

        return $this->issueToken($session);
    }

    /**
     * @return array{plain: string, expires_at: int}|null
     */
    public function currentToken(EventSession $session): ?array
    {
        /** @var array{plain: string, expires_at: int}|null $cached */
        $cached = Cache::get($this->cacheKey($session));

        if ($cached === null) {
            return null;
        }

        if ($cached['expires_at'] <= now()->getTimestamp()) {
            return null;
        }

        return $cached;
    }

    public function clearCache(EventSession $session): void
    {
        Cache::forget($this->cacheKey($session));
    }

    public function rotateExpiredTokens(): int
    {
        $rotated = 0;

        EventSession::query()
            ->where('status', EventSessionStatus::Active)
            ->with('event')
            ->each(function (EventSession $session) use (&$rotated): void {
                $current = $this->currentToken($session);

                if ($current !== null && $current['expires_at'] > now()->getTimestamp()) {
                    return;
                }

                $this->issueToken($session);
                $rotated++;
            });

        return $rotated;
    }

    public function cacheKey(EventSession $session): string
    {
        return "qr_session:{$session->id}:current";
    }
}
