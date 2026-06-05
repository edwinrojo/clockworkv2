<?php

namespace App\Http\Middleware;

use App\Enums\CheckInErrorCode;
use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileEmployee
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        if (! $user->is_active) {
            return ApiResponse::error(
                CheckInErrorCode::AccountInactive->message(),
                403,
                CheckInErrorCode::AccountInactive->value,
            );
        }

        if (! $user->isEmployee()) {
            return ApiResponse::error(
                CheckInErrorCode::Unauthorized->message(),
                403,
                CheckInErrorCode::Unauthorized->value,
            );
        }

        if (! $user->hasVerifiedEmail()) {
            return ApiResponse::error(
                CheckInErrorCode::EmailNotVerified->message(),
                403,
                CheckInErrorCode::EmailNotVerified->value,
            );
        }

        return $next($request);
    }
}
