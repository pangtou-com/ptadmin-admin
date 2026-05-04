<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature;

use PTAdmin\Admin\Tests\TestCase;

class PTAdminSeoHelperTest extends TestCase
{
    public function test_seo_helpers_read_shared_context_and_render_social_payloads(): void
    {
        share_seo_context([
            'title' => 'PTAdmin CMS',
            'keywords' => 'cms,ptadmin',
            'description' => '宿主层 SEO helper 测试',
            'canonical' => '/cms',
            'robots' => 'index,follow',
            'open_graph' => [
                'type' => 'website',
                'title' => 'PTAdmin CMS',
                'url' => '/cms',
            ],
            'twitter' => [
                'card' => 'summary',
                'title' => 'PTAdmin CMS',
            ],
            'structured_data' => [[
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => 'PTAdmin CMS',
            ]],
        ]);

        self::assertSame('PTAdmin CMS', seo_title());
        self::assertSame('cms,ptadmin', seo_keywords());
        self::assertSame('宿主层 SEO helper 测试', seo_description());
        self::assertSame('/cms', seo_canonical());
        self::assertSame('index,follow', seo_robots());
        self::assertStringContainsString('<meta name="keywords" content="cms,ptadmin">', seo_meta_keywords());
        self::assertStringContainsString('<meta name="description" content="宿主层 SEO helper 测试">', seo_meta_description());
        self::assertStringContainsString('<link rel="canonical" href="/cms">', seo_link_canonical());
        self::assertStringContainsString('<meta name="robots" content="index,follow">', seo_meta_robots());
        self::assertStringContainsString('<meta property="og:title" content="PTAdmin CMS">', seo_social());
        self::assertStringContainsString('<meta name="twitter:card" content="summary">', seo_social());
        self::assertStringContainsString('"@type":"WebPage"', seo_jsonld());
    }

    public function test_blade_sections_override_shared_seo_context(): void
    {
        share_seo_context([
            'title' => 'shared title',
            'keywords' => 'shared keywords',
            'description' => 'shared description',
            'canonical' => '/shared',
            'robots' => 'index,follow',
        ]);

        $path = sys_get_temp_dir().'/ptadmin-seo-helper-'.uniqid('', true).'.blade.php';
        file_put_contents($path, <<<'BLADE'
@section('title', 'section title')
@section('keywords', 'section keywords')
@section('description', 'section description')
@section('canonical', '/section')
@section('robots', 'noindex,follow')
{{ seo_title() }}|{{ seo_keywords() }}|{{ seo_description() }}|{{ seo_canonical() }}|{{ seo_robots() }}
BLADE
        );

        try {
            $html = trim((string) view()->file($path)->render());
        } finally {
            @unlink($path);
        }

        self::assertSame(
            'section title|section keywords|section description|/section|noindex,follow',
            $html
        );
    }

    public function test_apply_seo_overrides_supports_replace_and_append_modes(): void
    {
        share_seo_context([
            'title' => '原始标题',
            'keywords' => 'cms,ptadmin',
            'description' => '原始描述',
            'canonical' => '/original',
            'robots' => 'index,follow',
            'structured_data' => [[
                '@type' => 'WebPage',
            ]],
        ]);

        $context = apply_seo_overrides([
            'title' => '活动专题',
            'title_mode' => 'replace',
            'keywords' => 'activity',
            'keywords_mode' => 'append',
            'structured_data' => [[
                '@type' => 'CollectionPage',
            ]],
        ], false);

        self::assertSame('活动专题', $context['title']);
        self::assertSame('cms,ptadmin, activity', $context['keywords']);
        self::assertCount(2, $context['structured_data']);
    }
}
