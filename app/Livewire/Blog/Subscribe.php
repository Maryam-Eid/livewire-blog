<?php

namespace App\Livewire\Blog;

use App\Models\Subscriber;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Subscribe extends Component
{
    #[Validate('required|email|unique:subscribers,email')]
    public string $email = '';

    public function subscribe()
    {
        $this->validate();

        $subscriber = new Subscriber([
            'email' => $this->email,
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        $subscriber->save();

        session()->flash('subscribe-success', 'Thanks for subscribing! You will receive notifications when new posts are published.');

        $this->email = '';
    }

    public function render()
    {
        return view('livewire.blog.subscribe');
    }
}
