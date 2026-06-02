<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

class AdminRegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $request->wantsJson()
                ? response()->json('', 201)
                : redirect()->route('login');
        }

        if (! $user->canAccessAdmin()) {
            auth()->logout();

            return redirect()->route('login')->withErrors([
                'email' => __('Registration is limited to admin accounts. Contact HR for employee mobile access.'),
            ]);
        }

        return $request->wantsJson()
            ? response()->json('', 201)
            : redirect()->intended(Fortify::redirects('register'));
    }
}
