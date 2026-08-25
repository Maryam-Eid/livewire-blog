<?php

namespace App\Models;

use Database\Factories\NewsletterDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['newsletter_id', 'subscriber_id', 'email', 'unsubscribe_token', 'status', 'sent_at', 'failure_message'])]
class NewsletterDelivery extends Model
{
    /** @use HasFactory<NewsletterDeliveryFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function newsletter(): BelongsTo
    {
        return $this->belongsTo(Newsletter::class);
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    #[Scope]
    public function pending(Builder $query): void
    {
        $query->where('status', 'pending');
    }
}
