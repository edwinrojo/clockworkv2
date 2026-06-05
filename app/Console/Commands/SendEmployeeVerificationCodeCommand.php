<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Auth\EmployeeEmailVerificationService;
use Illuminate\Console\Command;

class SendEmployeeVerificationCodeCommand extends Command
{
    protected $signature = 'clockwork:send-verification-code {email : Employee work email}';

    protected $description = 'Send a six-digit email verification code to an unverified employee';

    public function handle(EmployeeEmailVerificationService $emailVerification): int
    {
        $user = User::query()
            ->where('email', $this->argument('email'))
            ->first();

        if ($user === null) {
            $this->error('No user found with that email.');

            return self::FAILURE;
        }

        if (! $user->isEmployee()) {
            $this->error('This account is not an employee.');

            return self::FAILURE;
        }

        if (! $user->is_active) {
            $this->error('This employee account is inactive.');

            return self::FAILURE;
        }

        if ($user->hasVerifiedEmail()) {
            $this->warn('This employee has already verified their email.');

            return self::SUCCESS;
        }

        $emailVerification->sendCode($user);

        $this->info("Verification code sent to {$user->email}.");

        return self::SUCCESS;
    }
}
