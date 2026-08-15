<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['email', 'token', 'is_verified', 'verified_at'])]
class Subscriber extends Model
{
    use Notifiable;

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

    public function routeNotificationForMail()
    {
        return $this->email;
    }
}
