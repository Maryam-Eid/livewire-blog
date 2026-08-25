<?php

namespace App\Console\Commands;

use App\Jobs\DispatchNewsletter;
use App\Models\Newsletter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('newsletters:dispatch-scheduled')]
#[Description('Dispatch newsletters whose scheduled delivery time has arrived')]
class DispatchScheduledNewsletters extends Command
{
    public function handle(): int
    {
        $dispatchedCount = 0;

        Newsletter::query()
            ->dueForSending()
            ->eachById(function (Newsletter $newsletter) use (&$dispatchedCount): void {
                DispatchNewsletter::dispatch($newsletter);
                $dispatchedCount++;
            });

        $this->info("Dispatched {$dispatchedCount} scheduled newsletter(s).");

        return self::SUCCESS;
    }
}
