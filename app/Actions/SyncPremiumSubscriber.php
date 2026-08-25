<?php

namespace App\Actions;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncPremiumSubscriber
{
    public function execute(User $user): Subscriber
    {
        return DB::transaction(function () use ($user): Subscriber {
            $linkedSubscriber = Subscriber::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();
            $emailSubscriber = Subscriber::query()
                ->where('email', $user->email)
                ->lockForUpdate()
                ->first();

            if ($linkedSubscriber !== null && $emailSubscriber !== null && ! $linkedSubscriber->is($emailSubscriber)) {
                $linkedSubscriber->update(['user_id' => null]);
                $linkedSubscriber = null;
            }

            $subscriber = $emailSubscriber ?? $linkedSubscriber ?? new Subscriber;
            $emailChanged = $subscriber->exists && $subscriber->email !== $user->email;

            $subscriber->fill([
                'user_id' => $user->getKey(),
                'email' => $user->email,
            ]);

            if ($user->hasVerifiedEmail()) {
                $subscriber->is_verified = true;
                $subscriber->verified_at ??= now();
            } elseif ($emailChanged) {
                $subscriber->is_verified = false;
                $subscriber->verified_at = null;
            }

            $subscriber->save();

            return $subscriber;
        });
    }
}
