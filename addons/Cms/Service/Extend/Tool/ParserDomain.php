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

namespace Addon\Cms\Service\Extend\Tool;

class ParserDomain
{
    /**
     * 解析域名.
     *
     * @param string $url
     *
     * @return array
     */
    public static function parserDomain(string $url): ?array
    {
        $parser = new self();
        if (!$parser->isDomain($url)) {
            return null;
        }
        $data = $parser->parseUrl($url);
        if (false === $data) {
            return null;
        }

        // 提取后缀
        $data['suffix'] = pathinfo($data['path'], PATHINFO_EXTENSION);
        $data['url'] = $url;

        return $data;
    }

    /**
     *  解析 URL.
     *
     * @param string $url
     *
     * @return array|false
     */
    private function parseUrl(string $url)
    {
        $info = parse_url($url);
        if (!$info || !isset($info['host'])) {
            return false;
        }
        $host = explode(':', $info['host']);
        $port = $info['port'] ?? $host[1] ?? (($info['scheme'] ?? '') === 'https' ? '443' : '80');

        return [
            'port' => $port, // 端口
            'url' => $url,  // 链接地址
            'domain' => $host[0], // 域名
            'scheme' => $info['scheme'] ?? '',
            'path' => $info['path'] ?? '',
        ];
    }

    /**
     * 简单判断是否为一个域名.
     *
     * @param string $domain
     *
     * @return bool
     */
    private function isDomain(string $domain): bool
    {
        return false !== strpos($domain, '.');
    }
}
