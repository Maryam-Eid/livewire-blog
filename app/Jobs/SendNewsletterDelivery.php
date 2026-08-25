<?php

namespace App\Jobs;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\NewsletterDelivery;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendNewsletterDelivery implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public array $backoff = [30, 120, 300];

    public function __construct(public NewsletterDelivery $delivery)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $delivery = NewsletterDelivery::query()
            ->with(['newsletter', 'subscriber'])
            ->find($this->delivery->getKey());

        if ($delivery === null || $delivery->status !== 'pending') {
            return;
        }

        if ($delivery->subscriber === null
            || ! $delivery->subscriber->is_verified
            || $delivery->subscriber->verified_at === null) {
            $this->recordFailure('The subscriber is missing or no longer verified.');

            return;
        }

        if (
            $delivery->newsletter->audience === 'premium'
            && ! ($delivery->subscriber->user?->isPremiumSubscriber() ?? false)
        ) {
            $this->recordFailure('The subscriber no longer has an active Premium membership.');

            return;
        }

        Mail::to($delivery->email)->send(
            new NewsletterMail($delivery->newsletter, $delivery)
        );

        $this->recordSent();
    }

    public function uniqueId(): string
    {
        return (string) $this->delivery->getKey();
    }

    public function failed(?Throwable $exception): void
    {
        $this->recordFailure($exception?->getMessage() ?? 'Newsletter delivery failed.');
    }

    private function recordSent(): void
    {
        DB::transaction(function (): void {
            $delivery = NewsletterDelivery::query()
                ->lockForUpdate()
                ->find($this->delivery->getKey());

            if ($delivery === null || $delivery->status !== 'pending') {
                return;
            }

            $newsletter = Newsletter::query()
                ->lockForUpdate()
                ->find($delivery->newsletter_id);

            if ($newsletter === null) {
                return;
            }

            $delivery->update([
                'status' => 'sent',
                'sent_at' => now(),
                'failure_message' => null,
            ]);

            $newsletter->increment('sent_count');
            $this->finalizeNewsletter($newsletter);
        });
    }

    private function recordFailure(string $message): void
    {
        DB::transaction(function () use ($message): void {
            $delivery = NewsletterDelivery::query()
                ->lockForUpdate()
                ->find($this->delivery->getKey());

            if ($delivery === null || $delivery->status !== 'pending') {
                return;
            }

            $newsletter = Newsletter::query()
                ->lockForUpdate()
                ->find($delivery->newsletter_id);

            if ($newsletter === null) {
                return;
            }

            $delivery->update([
                'status' => 'failed',
                'sent_at' => null,
                'failure_message' => Str::limit($message, 65535, ''),
            ]);

            $newsletter->increment('failed_count');
            $this->finalizeNewsletter($newsletter);
        });
    }

    private function finalizeNewsletter(Newsletter $newsletter): void
    {
        $hasPendingDeliveries = NewsletterDelivery::query()
            ->whereBelongsTo($newsletter)
            ->pending()
            ->exists();

        if ($hasPendingDeliveries) {
            return;
        }

        $newsletter->refresh();

        if ($newsletter->failed_count > 0) {
            $newsletter->update([
                'status' => 'failed',
                'sent_at' => null,
            ]);

            return;
        }

        $newsletter->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
