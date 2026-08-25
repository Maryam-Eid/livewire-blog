<?php

namespace App\Jobs;

use App\Models\Newsletter;
use App\Models\NewsletterDelivery;
use App\Models\Subscriber;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchNewsletter implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public array $backoff = [10, 60, 300];

    public function __construct(public Newsletter $newsletter)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $newsletter = Newsletter::query()->find($this->newsletter->getKey());

        if ($newsletter === null || ! in_array($newsletter->status, ['draft', 'scheduled', 'sending'], true)) {
            return;
        }

        if ($newsletter->status === 'scheduled'
            && ($newsletter->scheduled_at === null || $newsletter->scheduled_at->isFuture())) {
            return;
        }

        Subscriber::query()
            ->select(['id', 'email', 'token'])
            ->verified()
            ->when(
                $newsletter->audience === 'premium',
                fn ($query) => $query->premium(),
            )
            ->chunkById(500, function (Collection $subscribers) use ($newsletter): void {
                $timestamp = now();
                $deliveries = $subscribers
                    ->map(fn (Subscriber $subscriber): array => [
                        'newsletter_id' => $newsletter->getKey(),
                        'subscriber_id' => $subscriber->getKey(),
                        'email' => $subscriber->email,
                        'unsubscribe_token' => $subscriber->token,
                        'status' => 'pending',
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ])
                    ->all();

                NewsletterDelivery::query()->insertOrIgnore($deliveries);
            });

        $recipientCount = $newsletter->deliveries()->count();

        if ($recipientCount === 0) {
            $newsletter->update([
                'status' => 'sent',
                'recipient_count' => 0,
                'sent_at' => now(),
            ]);

            return;
        }

        $newsletter->update([
            'status' => 'sending',
            'recipient_count' => $recipientCount,
        ]);

        $newsletter->deliveries()
            ->pending()
            ->eachById(function (NewsletterDelivery $delivery): void {
                SendNewsletterDelivery::dispatch($delivery);
            });
    }

    public function uniqueId(): string
    {
        return (string) $this->newsletter->getKey();
    }

    public function failed(?Throwable $exception): void
    {
        Newsletter::query()
            ->whereKey($this->newsletter->getKey())
            ->whereIn('status', ['draft', 'scheduled', 'sending'])
            ->update(['status' => 'failed']);

        Log::error('Newsletter dispatch failed.', [
            'newsletter_id' => $this->newsletter->getKey(),
            'exception' => $exception?->getMessage(),
        ]);
    }
}
