<?php

namespace Database\Seeders;

use App\Models\Newsletter;
use App\Models\NewsletterDelivery;
use App\Models\Subscriber;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoAudienceSeeder extends Seeder
{
    /**
     * Seed demo subscribers, subscriptions, and newsletters without deleting existing data.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first()
            ?? User::query()->orderBy('id')->first();

        $monthlyPrice = SubscriptionPlan::query()->where('key', 'monthly')->value('stripe_price_id')
            ?? 'price_monthly_demo';
        $yearlyPrice = SubscriptionPlan::query()->where('key', 'yearly')->value('stripe_price_id')
            ?? 'price_yearly_demo';

        $readers = [
            [
                'name' => 'Sarah Bennett',
                'email' => 'sara.reader@example.com',
                'verified' => true,
                'subscription' => 'monthly',
            ],
            [
                'name' => 'James Carter',
                'email' => 'omar.premium@example.com',
                'verified' => true,
                'subscription' => 'yearly',
            ],
            [
                'name' => 'Emily Walsh',
                'email' => 'lina.free@example.com',
                'verified' => true,
                'subscription' => null,
            ],
            [
                'name' => 'Daniel Brooks',
                'email' => 'karim.pending@example.com',
                'verified' => false,
                'subscription' => null,
            ],
            [
                'name' => 'Natalie Reed',
                'email' => 'nour.member@example.com',
                'verified' => true,
                'subscription' => 'canceled',
            ],
            [
                'name' => 'Hannah Miles',
                'email' => 'hana.grace@example.com',
                'verified' => true,
                'subscription' => 'grace',
            ],
            [
                'name' => 'Michael Turner',
                'email' => 'michael.turner@example.com',
                'verified' => true,
                'subscription' => 'monthly',
            ],
            [
                'name' => 'Olivia Grant',
                'email' => 'olivia.grant@example.com',
                'verified' => true,
                'subscription' => 'yearly',
            ],
            [
                'name' => 'Ethan Clarke',
                'email' => 'ethan.clarke@example.com',
                'verified' => true,
                'subscription' => 'past_due',
            ],
            [
                'name' => 'Chloe Martin',
                'email' => 'chloe.martin@example.com',
                'verified' => true,
                'subscription' => null,
            ],
            [
                'name' => 'Ryan Foster',
                'email' => 'ryan.foster@example.com',
                'verified' => false,
                'subscription' => null,
            ],
            [
                'name' => 'Sophia Allen',
                'email' => 'sophia.allen@example.com',
                'verified' => true,
                'subscription' => 'monthly',
            ],
        ];

        $subscriberIdsByEmail = [];
        $premiumEmails = [];

        foreach ($readers as $reader) {
            $user = User::query()->firstOrCreate(
                ['email' => $reader['email']],
                [
                    'name' => $reader['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            if ($user->name !== $reader['name']) {
                $user->update(['name' => $reader['name']]);
            }

            if (blank($user->stripe_id)) {
                $user->forceFill([
                    'stripe_id' => 'cus_demo_'.Str::lower(Str::random(14)),
                    'pm_type' => 'card',
                    'pm_last_four' => (string) fake()->numberBetween(1000, 9999),
                ])->save();
            }

            $subscriber = Subscriber::query()->firstOrCreate(
                ['email' => $reader['email']],
                [
                    'user_id' => $user->id,
                    'is_verified' => $reader['verified'],
                    'verified_at' => $reader['verified'] ? now()->subDays(fake()->numberBetween(1, 30)) : null,
                ],
            );

            $subscriber->fill([
                'user_id' => $user->id,
                'is_verified' => $reader['verified'],
                'verified_at' => $reader['verified']
                    ? ($subscriber->verified_at ?? now()->subDays(fake()->numberBetween(1, 30)))
                    : null,
            ])->save();

            $subscriberIdsByEmail[$reader['email']] = $subscriber->id;

            if ($reader['subscription'] === null) {
                continue;
            }

            $this->ensureSubscription(
                $user,
                match ($reader['subscription']) {
                    'yearly' => $yearlyPrice,
                    default => $monthlyPrice,
                },
                $reader['subscription'],
            );

            if (in_array($reader['subscription'], ['monthly', 'yearly', 'grace', 'past_due'], true)) {
                $premiumEmails[] = $reader['email'];
            }
        }

        $guestEmails = [
            'alex.morgan@example.com',
            'jordan.lee@example.com',
            'casey.wright@example.com',
            'taylor.hayes@example.com',
            'morgan.bell@example.com',
            'jamie.cole@example.com',
            'riley.stone@example.com',
            'avery.park@example.com',
            // keep older guest emails too
            'guest.one@example.com',
            'guest.two@example.com',
            'reader.notes@example.com',
            'weekend.reader@example.com',
        ];

        foreach ($guestEmails as $email) {
            $subscriber = Subscriber::query()->firstOrCreate(
                ['email' => $email],
                [
                    'user_id' => null,
                    'is_verified' => true,
                    'verified_at' => now()->subDays(fake()->numberBetween(2, 45)),
                ],
            );

            $subscriberIdsByEmail[$email] = $subscriber->id;
        }

        if ($admin === null) {
            return;
        }

        $sentAll = Newsletter::query()->firstOrCreate(
            [
                'user_id' => $admin->id,
                'subject' => 'Three essays worth your attention this week',
            ],
            [
                'content' => '<p>Hello from Mind Whispers,</p><p>Three short reads for a quieter week:</p><ul><li><strong>The Quiet Hour Before Email</strong></li><li><strong>Why Sunday Mornings Deserve a Slower Pace</strong></li><li><strong>Learning to Read Without Highlighting Everything</strong></li></ul><blockquote>Clarity is often what remains after the noise is refused.</blockquote><p>Thank you for reading with us.</p><p>— Mind Whispers</p>',
                'audience' => 'all',
                'status' => 'sent',
                'sent_at' => now()->subDays(3),
            ],
        );

        $sentPremium = Newsletter::query()->firstOrCreate(
            [
                'user_id' => $admin->id,
                'subject' => 'Premium desk: Notes Worth Reopening',
            ],
            [
                'content' => '<p>A quiet note for Premium members.</p><p>This week we published a longer reflection on making notes useful again — less collecting, more reopening.</p><p>Thank you for supporting the work.</p>',
                'audience' => 'premium',
                'status' => 'sent',
                'sent_at' => now()->subDay(),
            ],
        );

        $sentHabits = Newsletter::query()->firstOrCreate(
            [
                'user_id' => $admin->id,
                'subject' => 'A gentler definition of productivity',
            ],
            [
                'content' => '<p>Output is not the only proof that a day was well spent.</p><p>This week, try one quieter hour and see what returns.</p>',
                'audience' => 'all',
                'status' => 'sent',
                'sent_at' => now()->subDays(8),
            ],
        );

        Newsletter::query()->firstOrCreate(
            [
                'user_id' => $admin->id,
                'subject' => 'Sunday quiet hour (scheduled)',
            ],
            [
                'content' => '<p>A short reminder to protect the first hour of your week.</p>',
                'audience' => 'all',
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(2)->setTime(9, 0),
            ],
        );

        Newsletter::query()->firstOrCreate(
            [
                'user_id' => $admin->id,
                'subject' => 'Members briefing: slower releases (scheduled)',
            ],
            [
                'content' => '<p>For Premium members: why shipping less often can improve the work.</p>',
                'audience' => 'premium',
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(5)->setTime(10, 30),
            ],
        );

        Newsletter::query()->firstOrCreate(
            [
                'user_id' => $admin->id,
                'subject' => 'Draft: Autumn reading list',
            ],
            [
                'content' => '<p>Still shaping the seasonal list for new subscribers.</p>',
                'audience' => 'all',
                'status' => 'draft',
            ],
        );

        Newsletter::query()->firstOrCreate(
            [
                'user_id' => $admin->id,
                'subject' => 'Draft: Year-end letter',
            ],
            [
                'content' => '<p>A draft thank-you note for readers who stayed through the year.</p>',
                'audience' => 'all',
                'status' => 'draft',
            ],
        );

        $this->seedDeliveries($sentAll, $subscriberIdsByEmail, $premiumEmails, premiumOnly: false);
        $this->seedDeliveries($sentPremium, $subscriberIdsByEmail, $premiumEmails, premiumOnly: true);
        $this->seedDeliveries($sentHabits, $subscriberIdsByEmail, $premiumEmails, premiumOnly: false);
    }

    private function ensureSubscription(User $user, string $priceId, string $state): void
    {
        $existing = $user->subscriptions()
            ->where('type', 'premium')
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due', 'canceled'])
            ->first();

        if ($existing !== null) {
            return;
        }

        $status = match ($state) {
            'canceled' => 'canceled',
            'past_due' => 'past_due',
            'grace' => 'active',
            default => 'active',
        };

        $subscription = $user->subscriptions()->create([
            'type' => 'premium',
            'stripe_id' => 'sub_demo_'.Str::lower(Str::random(16)),
            'stripe_status' => $status,
            'stripe_price' => $priceId,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => match ($state) {
                'canceled' => now()->subDays(5),
                'grace' => now()->addDays(7),
                default => null,
            },
        ]);

        $subscription->items()->create([
            'stripe_id' => 'si_demo_'.Str::lower(Str::random(14)),
            'stripe_product' => 'prod_demo_premium',
            'stripe_price' => $priceId,
            'quantity' => 1,
        ]);
    }

    /**
     * @param  array<string, int>  $subscriberIdsByEmail
     * @param  list<string>  $premiumEmails
     */
    private function seedDeliveries(
        Newsletter $newsletter,
        array $subscriberIdsByEmail,
        array $premiumEmails,
        bool $premiumOnly,
    ): void {
        $emails = $premiumOnly
            ? $premiumEmails
            : array_keys($subscriberIdsByEmail);

        $emails = array_values(array_unique($emails));

        $sent = 0;
        $failed = 0;

        foreach ($emails as $index => $email) {
            $status = $index === array_key_last($emails) && ! $premiumOnly ? 'failed' : 'sent';

            NewsletterDelivery::query()->firstOrCreate(
                [
                    'newsletter_id' => $newsletter->id,
                    'email' => $email,
                ],
                [
                    'subscriber_id' => $subscriberIdsByEmail[$email] ?? null,
                    'unsubscribe_token' => Str::random(40),
                    'status' => $status,
                    'sent_at' => $status === 'sent' ? ($newsletter->sent_at ?? now()->subDay()) : null,
                    'failure_message' => $status === 'failed' ? 'Mailbox unavailable (demo).' : null,
                ],
            );

            if ($status === 'sent') {
                $sent++;
            } else {
                $failed++;
            }
        }

        $newsletter->update([
            'recipient_count' => count($emails),
            'sent_count' => $sent,
            'failed_count' => $failed,
        ]);
    }
}
