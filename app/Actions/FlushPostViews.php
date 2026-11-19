<?php

namespace App\Actions;

use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use App\Support\PostViewQueue;
use Illuminate\Support\Facades\DB;

class FlushPostViews
{
    public function __construct(private PostViewQueue $views) {}

    public function __invoke(int $limit = 500): int
    {
        $payloads = $this->views->claim($limit);

        if ($payloads === []) {
            return 0;
        }

        $rows = $this->rows($payloads);

        if ($rows !== []) {
            DB::transaction(function () use ($rows): void {
                foreach (collect($rows)->countBy('post_id') as $postId => $count) {
                    Post::query()->whereKey($postId)->increment('views_count', $count);
                }

                PostView::query()->insert($rows);
            });
        }

        $this->views->acknowledge(count($payloads));

        return count($rows);
    }

    /**
     * @param  list<string>  $payloads
     * @return list<array{
     *     post_id: int,
     *     user_id: int|null,
     *     ip_address: string|null,
     *     user_agent: string|null,
     *     viewed_at: string,
     *     created_at: string,
     *     updated_at: string
     * }>
     */
    private function rows(array $payloads): array
    {
        $decoded = [];

        foreach ($payloads as $payload) {
            $view = json_decode($payload, true);

            if (! is_array($view) || ! isset($view['post_id'])) {
                continue;
            }

            $decoded[] = $view;
        }

        if ($decoded === []) {
            return [];
        }

        $postIds = collect($decoded)->pluck('post_id')->map(fn (mixed $id): int => (int) $id)->unique()->all();
        $existingPostIds = Post::query()->whereIn('id', $postIds)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        $userIds = collect($decoded)->pluck('user_id')->filter()->map(fn (mixed $id): int => (int) $id)->unique()->all();
        $existingUserIds = $userIds === []
            ? []
            : User::query()->whereIn('id', $userIds)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        $timestamp = now()->toDateTimeString();
        $rows = [];

        foreach ($decoded as $view) {
            $postId = (int) $view['post_id'];

            if (! in_array($postId, $existingPostIds, true)) {
                continue;
            }

            $userId = isset($view['user_id']) ? (int) $view['user_id'] : null;

            if ($userId === 0 || ($userId !== null && ! in_array($userId, $existingUserIds, true))) {
                $userId = null;
            }

            $rows[] = [
                'post_id' => $postId,
                'user_id' => $userId,
                'ip_address' => isset($view['ip_address']) ? (string) $view['ip_address'] : null,
                'user_agent' => isset($view['user_agent']) ? (string) $view['user_agent'] : null,
                'viewed_at' => isset($view['viewed_at']) ? (string) $view['viewed_at'] : $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        return $rows;
    }
}
