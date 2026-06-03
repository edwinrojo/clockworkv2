<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CheckInErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
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

        $tokenName = $request->string('device_name')->toString() ?: 'mobile';

        $user->tokens()->where('name', $tokenName)->delete();

        $token = $user->createToken($tokenName)->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->user()?->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        } elseif ($request->bearerToken() !== null) {
            PersonalAccessToken::findToken($request->bearerToken())?->delete();
        }

        return ApiResponse::success(['message' => 'Logged out.']);
    }
}
