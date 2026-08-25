<?php

namespace App\Livewire\Blog;

use App\Mail\SubscriberVerificationMail;
use App\Models\Subscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Subscribe extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    public function subscribe(): void
    {
        $this->email = strtolower(trim($this->email));
        $this->validate();

        $subscriber = Subscriber::query()->firstOrCreate([
            'email' => $this->email,
        ]);

        if ($subscriber->is_verified && $subscriber->verified_at !== null) {
            session()->flash('subscribe-success', 'You are already subscribed and verified.');
        } else {
            Mail::to($subscriber->email)->send(
                new SubscriberVerificationMail($subscriber),
            );
            session()->flash('subscribe-success', 'Check your inbox and confirm your email to complete your subscription.');
        }

        $this->email = '';
    }

    public function render(): View
    {
        return view('livewire.blog.subscribe');
    }
}
