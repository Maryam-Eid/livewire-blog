<?php

namespace App\Models;

use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

#[Fillable(['user_id', 'email', 'token', 'is_verified', 'verified_at'])]
class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory, Notifiable;

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($subscriber) {
            $subscriber->token = Str::random(32);
        });
    }

    #[Scope]
    public function verified(Builder $query): void
    {
        $query
            ->where('is_verified', true)
            ->whereNotNull('verified_at');
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    public function premium(Builder $query): void
    {
        $query->whereIn(
            'user_id',
            Subscription::query()
                ->active()
                ->where('type', 'premium')
                ->select('user_id'),
        );
    }
}
