<?php

namespace App\Support;

class PostCatalog
{
    /**
     * @return array{
     *     categories: list<array{name: string, slug: string, description: string, color: string}>,
     *     tags: list<array{name: string, slug: string}>,
     *     featured_images: list<string>,
     *     posts: list<array{
     *         title: string,
     *         excerpt: string,
     *         blocks: list<array{type: string, text: string}>,
     *         categories: list<string>,
     *         tags: list<string>
     *     }>
     * }
     */
    public static function data(): array
    {
        return require database_path('data/post-catalog.php');
    }

    /**
     * @param  list<array{type: string, text: string}>  $blocks
     */
    public static function buildContent(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            $html .= match ($block['type']) {
                'heading' => '<h2>'.e($block['text']).'</h2>',
                'quote' => '<blockquote>'.e($block['text']).'</blockquote>',
                default => '<p>'.e($block['text']).'</p>',
            };
        }

        return $html;
    }

    public static function featuredImageForIndex(int $index): string
    {
        $images = self::data()['featured_images'];

        return $images[$index % count($images)];
    }
}
