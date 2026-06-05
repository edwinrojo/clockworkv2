<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\EmployeeEmailVerificationCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmployeeEmailVerificationService
{
    public function sendCode(User $user): bool
    {
        if (! $user->isEmployee() || ! $user->is_active || $user->hasVerifiedEmail()) {
            return false;
        }

        $code = $this->generateCode();
        $ttlMinutes = (int) config('clockwork.email_verification_code_ttl_minutes', 30);

        Cache::put(
            $this->cacheKey($user),
            Hash::make($code),
            now()->addMinutes($ttlMinutes),
        );

        $user->notify(new EmployeeEmailVerificationCode($code, $ttlMinutes));

        return true;
    }

    public function verify(string $email, string $code): User
    {
        $user = User::query()
            ->where('email', $email)
            ->first();

        if ($user === null || ! $user->isEmployee() || ! $user->is_active) {
            throw ValidationException::withMessages([
                'code' => [__('The verification code is invalid or has expired.')],
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return $user;
        }

        $cachedHash = Cache::get($this->cacheKey($user));

        if (! is_string($cachedHash) || ! Hash::check($code, $cachedHash)) {
            throw ValidationException::withMessages([
                'code' => [__('The verification code is invalid or has expired.')],
            ]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        Cache::forget($this->cacheKey($user));

        return $user;
    }

    private function generateCode(): string
    {
        $length = (int) config('clockwork.email_verification_code_length', 6);
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function cacheKey(User $user): string
    {
        return 'employee_email_verify:'.$user->id;
    }
}
