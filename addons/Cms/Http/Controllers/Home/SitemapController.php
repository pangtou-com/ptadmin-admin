<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2024 重庆胖头网络技术有限公司，并保留所有权利。
 *  网站地址: https://www.pangtou.com
 *  ----------------------------------------------------------------------------
 *  尊敬的用户，
 *     感谢您对我们产品的关注与支持。我们希望提醒您，在商业用途中使用我们的产品时，请务必前往官方渠道购买正版授权。
 *  购买正版授权不仅有助于支持我们不断提供更好的产品和服务，更能够确保您在使用过程中不会引起不必要的法律纠纷。
 *  正版授权是保障您合法使用产品的最佳方式，也有助于维护您的权益和公司的声誉。我们一直致力于为客户提供高质量的解决方案，并通过正版授权机制确保产品的可靠性和安全性。
 *  如果您有任何疑问或需要帮助，我们的客户服务团队将随时为您提供支持。感谢您的理解与合作。
 *  诚挚问候，
 *  【重庆胖头网络技术有限公司】
 *  ============================================================================
 *  Author:    Zane
 *  Homepage:  https://www.pangtou.com
 *  Email:     vip@pangtou.com
 */

namespace Addon\Cms\Http\Controllers\Home;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SitemapController
{
    public function showForm()
    {
        return view('sitemap_xml', ['seoConfigData' => []]);
    }

    public function generateSitemap(Request $request): \Illuminate\Http\RedirectResponse
    {
        // 定义网站的基本URL
        $baseUrl = 'http://www.pt.com';

        // 定义要包含在Sitemap中的页面
        $pages = [
            1 => ['loc' => '/', 'lastmod' => now()->format('Y-m-d'), 'changefreq' => 'daily', 'priority' => '1.0'],
            2 => ['loc' => '/about', 'lastmod' => now()->format('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            4 => ['loc' => '/contact', 'lastmod' => now()->format('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            // 添加更多页面
        ];

        // 获取表单提交的数据
        $cacheProcessing = $request->input('cache_processing', []);

        // 创建XML文档
        $xml = new \SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" />');

        // 添加每个页面的信息
        foreach ($pages as $key => $page) {
            if (\in_array($key, $cacheProcessing, true)) {
                $url = $xml->addChild('url');
                $url->addChild('loc', $baseUrl.$page['loc']);
                $url->addChild('lastmod', $page['lastmod']);
                $url->addChild('changefreq', $page['changefreq']);
                $url->addChild('priority', $page['priority']);
            }
        }

        // 将XML内容保存到文件
        $sitemapContent = $xml->asXML();

        Storage::put('public/sitemap.xml', $sitemapContent);

        // 返回成功消息
        return redirect()->route('sitemap.form')->with('success', 'Sitemap generated successfully!');
    }
}
