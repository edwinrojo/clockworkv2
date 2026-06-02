<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class AdminLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user !== null && ! $user->canAccessAdmin()) {
            auth()->logout();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => __('Your account does not have access to the admin dashboard.'),
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => __('Your account does not have access to the admin dashboard.'),
            ]);
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(Fortify::redirects('login'));
    }
}
