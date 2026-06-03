<?php

namespace App\Console\Commands;

use App\Services\Qr\QrTokenService;
use Illuminate\Console\Command;

class RotateQrTokensCommand extends Command
{
    protected $signature = 'clockwork:rotate-qr-tokens';

    protected $description = 'Rotate expired QR tokens for active event sessions';

    public function handle(QrTokenService $qrTokenService): int
    {
        $rotated = $qrTokenService->rotateExpiredTokens();

        if ($rotated > 0) {
            $this->components->info("Rotated {$rotated} QR token(s).");
        }

        return self::SUCCESS;
    }
}
