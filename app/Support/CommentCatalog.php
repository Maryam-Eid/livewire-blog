<?php

namespace App\Support;

class CommentCatalog
{
    /**
     * @return array{
     *     approved: list<string>,
     *     pending: list<string>,
     *     spam: list<string>,
     *     replies: list<string>
     * }
     */
    public static function data(): array
    {
        return require database_path('data/comment-catalog.php');
    }

    public static function random(string $type = 'approved'): string
    {
        $comments = self::data()[$type] ?? self::data()['approved'];

        return $comments[array_rand($comments)];
    }
}
