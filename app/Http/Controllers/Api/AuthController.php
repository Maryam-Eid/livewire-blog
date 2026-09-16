<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\PasswordResetOtp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\LogoutRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use DateTimeInterface;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\CompletePasswordReset;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

#[Group('Auth', weight: 0)]
class AuthController extends Controller
{
    #[Endpoint(
        title: 'Register',
        description: 'Create an account, send a verification email, and return an access token. `device_name` is required. The token expires in 24 hours, same as login without `remember`. The user can use the app while `email_verified_at` is still null until they tap the link in the email.',
    )]
    #[Response(201, type: 'array{user: UserResource, token: string, expires_at: string}')]
    public function register(RegisterRequest $request, CreateNewUser $creator): JsonResponse
    {
        $user = $creator->create($request->safe()->only([
            'name',
            'email',
            'password',
            'password_confirmation',
        ]));

        event(new Registered($user));

        return $this->tokenResponse($user, $request, 201, $this->tokenExpiresAt(remember: false));
    }

    #[Endpoint(
        title: 'Login',
        description: 'Authenticate with email and password and return an access token. `device_name` is required. Pass `remember: true` for a 30-day token; otherwise the token expires in 24 hours. A new login on the same device name replaces the previous token for that device.',
    )]
    #[Response(200, type: 'array{user: UserResource, token: string, expires_at: string}')]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        return $this->tokenResponse($user, $request, expiresAt: $this->tokenExpiresAt($request->boolean('remember')));
    }

    #[Endpoint(
        title: 'Forgot password',
        description: 'Email a 6-digit OTP (valid 10 minutes). Send `email`. Unknown emails and resend throttling return 422 on `email`, same idea as the website broker.',
    )]
    #[Response(200, type: 'array{message: string}')]
    public function forgotPassword(ForgotPasswordRequest $request, PasswordResetOtp $passwordResetOtp): JsonResponse
    {
        $status = $passwordResetOtp->send($request->validated('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        }

        return response()->json([
            'message' => 'A password reset code has been sent to your email.',
        ]);
    }

    #[Endpoint(
        title: 'Reset password',
        description: 'Send `email`, the 6-digit `otp` from the email, `password`, and `password_confirmation`. After success, sign in with Login. Password rules match registration (`Password::default()`).',
    )]
    #[Response(200, type: 'array{message: string}')]
    public function resetPassword(
        ResetPasswordRequest $request,
        PasswordResetOtp $passwordResetOtp,
        ResetsUserPasswords $resetsPasswords,
        CompletePasswordReset $completePasswordReset,
        StatefulGuard $guard,
    ): JsonResponse {
        $user = $passwordResetOtp->consume(
            $request->validated('email'),
            $request->validated('otp'),
        );

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'otp' => ['This password reset code is invalid.'],
            ]);
        }

        $resetsPasswords->reset($user, $request->validated());
        $completePasswordReset($guard, $user);

        return response()->json([
            'message' => trans(Password::PASSWORD_RESET),
        ]);
    }

    #[Endpoint(
        title: 'Current user',
        description: 'Return the authenticated user.',
    )]
    #[Response(200, type: 'array{user: UserResource}')]
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => UserResource::make($request->user()),
        ]);
    }

    #[Endpoint(
        title: 'Logout',
        description: 'Revoke all access tokens issued for `device_name`. Send the current Bearer token plus the same `device_name` used at login or register.',
    )]
    #[Response(200, type: 'array{message: string}')]
    public function logout(LogoutRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $user->tokens()->where('name', $request->validated('device_name'))->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    private function tokenExpiresAt(bool $remember): DateTimeInterface
    {
        return $remember
            ? now()->addDays((int) config('sanctum.remember_expiration_days'))
            : now()->addHours((int) config('sanctum.token_expiration_hours'));
    }

    private function tokenResponse(User $user, Request $request, int $status = 200, ?DateTimeInterface $expiresAt = null): JsonResponse
    {
        $deviceName = $request->string('device_name')->trim()->value();

        $user->tokens()->where('name', $deviceName)->delete();

        $accessToken = $user->createToken(
            $deviceName,
            ['*'],
            $expiresAt,
        );

        return response()->json([
            'user' => UserResource::make($user),
            'token' => $accessToken->plainTextToken,
            'expires_at' => $accessToken->accessToken->expires_at,
        ], $status);
    }
}
