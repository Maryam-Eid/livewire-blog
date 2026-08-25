<?php

namespace App\Livewire\Blog;

use App\Models\Comment;
use App\Models\Post;
use App\Notifications\NewCommentNotification;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Comments extends Component
{
    public Post $post;

    #[Validate('required|string|min:3|max:1000')]
    public string $newComment = '';

    public ?int $replyingTo = null;

    #[Validate('required|string|min:3|max:1000')]
    public string $replyContent = '';

    public function mount(Post $post)
    {
        $this->post = $post;
    }

    public function postComment()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->validate(['newComment' => 'required|string|min:3|max:1000']);

        $comment = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => auth()->id(),
            'content' => $this->newComment,
            'status' => 'approved',
        ]);

        $this->newComment = '';

        if ($this->post->user_id !== auth()->id()) {
            $this->post->user->notify(new NewCommentNotification($comment));
        }

        $this->dispatch('comment-posted');

        session()->flash('comment-success', 'Comment posted successfully!');
    }

    public function startReply($commentId)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->replyingTo = $commentId;
        $this->replyContent = '';
    }

    public function cancelReply()
    {
        $this->replyingTo = null;
        $this->replyContent = '';
    }

    public function postReply($parentId)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $this->validate(['replyContent' => 'required|string|min:3|max:1000']);

        $comment = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => auth()->id(),
            'parent_id' => $parentId,
            'content' => $this->replyContent,
            'status' => 'approved',
        ]);

        if ($this->post->user_id !== auth()->id()) {
            $this->post->user->notify(new NewCommentNotification($comment));
        }

        $this->replyingTo = null;
        $this->replyContent = '';

        $this->dispatch('comment-posted');

        session()->flash('comment-success', 'Reply posted successfully!');
    }

    #[On('comment-posted')]
    public function render()
    {
        $comments = Comment::where('post_id', $this->post->id)
            ->approved()
            ->topLevel()
            ->with(['user', 'replies.user'])
            ->latest()
            ->get();

        return view('livewire.blog.comments', [
            'comments' => $comments,
        ]);
    }
}
