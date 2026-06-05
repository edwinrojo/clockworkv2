<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CheckInErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Requests\Api\V1\SendEmailVerificationCodeRequest;
use App\Http\Requests\Api\V1\VerifyEmailRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Notifications\MobileResetPassword;
use App\Services\Auth\EmployeeEmailVerificationService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(private EmployeeEmailVerificationService $emailVerification) {}

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

        if (! $user->hasVerifiedEmail()) {
            $codeSent = $this->emailVerification->sendCode($user);

            return ApiResponse::error(
                $codeSent
                    ? __('Confirm your email with the verification code we just sent.')
                    : CheckInErrorCode::EmailNotVerified->message(),
                403,
                CheckInErrorCode::EmailNotVerified->value,
                [],
                ['verification_code_sent' => $codeSent],
            );
        }

        $tokenName = $request->string('device_name')->toString() ?: 'mobile';

        $user->tokens()->delete();

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

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->validated('email'))
            ->first();

        if ($user !== null && $user->isEmployee() && $user->is_active) {
            Password::sendResetLink(
                $request->only('email'),
                function (User $employee, string $token): void {
                    $employee->notify(new MobileResetPassword($token));
                },
            );
        }

        return ApiResponse::success([
            'message' => __('If that email is registered, a password reset link has been sent.'),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                if (! $user->isEmployee() || ! $user->is_active) {
                    throw ValidationException::withMessages([
                        'email' => [__('Unable to reset password.')],
                    ]);
                }

                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error(__($status), 422);
        }

        return ApiResponse::success([
            'message' => __('Your password has been reset. Please sign in on your device.'),
        ]);
    }

    public function sendEmailVerificationCode(SendEmailVerificationCodeRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->validated('email'))
            ->first();

        $codeSent = false;

        if (
            $user !== null
            && $user->isEmployee()
            && $user->is_active
            && Hash::check($request->validated('password'), $user->password)
        ) {
            $codeSent = $this->emailVerification->sendCode($user);
        }

        return ApiResponse::success([
            'message' => $codeSent
                ? __('A new verification code has been sent to your email.')
                : __('If your account needs verification, a new code has been sent to your email.'),
            'verification_code_sent' => $codeSent,
        ]);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->emailVerification->verify(
            $request->validated('email'),
            $request->validated('code'),
        );

        return ApiResponse::success([
            'message' => __('Email confirmed. You can sign in now.'),
            'user' => new UserResource($user),
        ]);
    }
}
