<?php

namespace App\Models;

use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'name', 'stripe_price_id', 'price_amount', 'currency', 'billing_interval', 'is_active'])]
class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<SubscriptionPlan>  $query
     */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query
            ->where('is_active', true)
            ->whereNotNull('stripe_price_id');
    }

    public function isConfigured(): bool
    {
        return $this->is_active && filled($this->stripe_price_id);
    }

    public function formattedPrice(): string
    {
        if ($this->price_amount === null) {
            return 'Contact us';
        }

        $amount = number_format((float) $this->price_amount, 2);

        return strtoupper((string) $this->currency).' '.$amount;
    }
}
