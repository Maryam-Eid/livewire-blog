<?php

namespace App\Observers;

use App\Models\Post;
use App\Models\Subscriber;
use App\Notifications\NewPostPublished;
use Illuminate\Support\Facades\Notification;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        //
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        if ($post->isDirty('status') && $post->status == 'published'
            && $post->getOriginal('status') != 'published') {
            $subscribers = Subscriber::where('is_verified', true)->get();
            if ($subscribers->count() > 0) {
                Notification::send($subscribers, new NewPostPublished($post));
            }
        }
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        //
    }

    /**
     * Handle the Post "restored" event.
     */
    public function restored(Post $post): void
    {
        //
    }

    /**
     * Handle the Post "force deleted" event.
     */
    public function forceDeleted(Post $post): void
    {
        //
    }
}
