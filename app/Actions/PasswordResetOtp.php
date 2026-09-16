<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetOtp
{
    public const LENGTH = 6;

    public const EXPIRES_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function send(string $email): string
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            return Password::INVALID_USER;
        }

        if (Cache::has($this->throttleKey($email))) {
            return Password::RESET_THROTTLED;
        }

        $otp = str_pad((string) random_int(0, 999999), self::LENGTH, '0', STR_PAD_LEFT);

        Cache::put($this->otpKey($email), [
            'hash' => Hash::make($otp),
            'attempts' => 0,
        ], now()->addMinutes(self::EXPIRES_MINUTES));

        Cache::put(
            $this->throttleKey($email),
            true,
            now()->addSeconds((int) config('auth.passwords.users.throttle', 60)),
        );

        $user->notify(new PasswordResetOtpNotification($otp));

        return Password::RESET_LINK_SENT;
    }

    public function consume(string $email, string $otp): ?User
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            return null;
        }

        $payload = Cache::get($this->otpKey($email));

        if (! is_array($payload) || ! Hash::check($otp, $payload['hash'] ?? '')) {
            $this->recordFailedAttempt($email, is_array($payload) ? $payload : null);

            return null;
        }

        Cache::forget($this->otpKey($email));
        Cache::forget($this->throttleKey($email));

        return $user;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function recordFailedAttempt(string $email, ?array $payload): void
    {
        if ($payload === null) {
            return;
        }

        $attempts = (int) ($payload['attempts'] ?? 0) + 1;

        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::forget($this->otpKey($email));

            return;
        }

        $payload['attempts'] = $attempts;

        Cache::put($this->otpKey($email), $payload, now()->addMinutes(self::EXPIRES_MINUTES));
    }

    private function otpKey(string $email): string
    {
        return 'password-reset-otp:'.hash('sha256', $email);
    }

    private function throttleKey(string $email): string
    {
        return 'password-reset-otp-throttle:'.hash('sha256', $email);
    }
}
