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

namespace Addon\Cms\Service\Extend;

use Addon\Cms\Service\Extend\Tool\ParserDomain;
use PTAdmin\Admin\Service\Common\UploadService;

/**
 * 外部资源处理类.
 * 1、处理文章种的外部链接
 * 2、处理文章中的图片资源.
 */
class ArchiveLinkHandle extends AbstractArchiveHandle
{
    public const DEFAULT_LENGTH = 1024 * 1024 * 100;

    public function action($data)
    {
        $pattern = '/<[img|a]+\s+[^>]*(src|href)=["\']([^"\']+)["\'][^>]*>/i';
        $data['content'] = preg_replace_callback($pattern, function ($matches) {
            $domain = ParserDomain::parserDomain($matches[2]);
            if (null === $domain) {
                return $matches[0];
            }
            $content = 'href' === $matches[1] ? $this->compareALink($domain) : $this->compareImageLink($domain);
            if (null === $content) {
                return $matches[0];
            }

            return str_replace($matches[2], $content, $matches[0]);
        }, $data['content']);

        return $data;
    }

    protected function beforeRun($data)
    {
        $pregPreReturn = $this->pregPre($data['content']);
        $data['content'] = $pregPreReturn['content'];
        $data['oldContent'] = $pregPreReturn['oldContent'];

        return $data;
    }

    protected function afterRun($data)
    {
        // 替换占位符回原始 <pre> 内容
        foreach ($data['oldContent'] as $placeholder => $originalPre) {
            $data['content'] = str_replace($placeholder, $originalPre, $data['content']);
        }
        unset($data['oldContent']);

        return $data;
    }

    /**
     * 图片提取.
     *
     * @param array $data
     *
     * @return null|string
     */
    private function compareImageLink(array $data): ?string
    {
        if (\in_array($data['domain'], $this->getImageFillable(), true)) {
            return null;
        }
        $urlHeader = $this->getExternalLink($data['url']);
        if (null === $urlHeader || !preg_match('/image/i', $urlHeader['Content-Type'] ?? '')) {
            return $data['url'];
        }

        if ($this->overLength((int) ($urlHeader['Content-Length'] ?? 0))) {
            return $data['url'];
        }

        // 下载文件
        return UploadService::download($this->sideAgreement($data['url']), $urlHeader['Content-Type'], (int) ($urlHeader['Content-Length'] ?? 0));
    }

    /**
     * 返回安全链接.
     *
     * @param mixed $data
     *
     * @return string
     */
    private function compareALink($data): ?string
    {
        if (\in_array($data['domain'], $this->getLinkFillable(), true)) {
            return null;
        }
        return urlencode($data['url']);

//        return $this->getTransfer().'?target='.urlencode($data['url']);
    }

    private function getImageFillable(): array
    {
        return [];
    }

    private function getLinkFillable(): array
    {
        return [];
    }

    /**
     * 长度是否超过限制.
     *
     * @param int $length
     *
     * @return bool
     */
    private function overLength(int $length): bool
    {
        return $length > self::DEFAULT_LENGTH;
    }

    /**
     * 获取中转链接.
     *
     * @return string
     */
    private function getTransfer(): string
    {
        return 'www.pt.com/transfer';
    }

    /**
     * 判断是否为一个有效的链接.
     *
     * @param mixed $url
     *
     * @return array
     */
    private function getExternalLink($url): ?array
    {
        $data = @get_headers($this->sideAgreement($url), 1);
        if (false !== $data && false !== strpos($data[0], '200')) {
            return $data;
        }

        return null;
    }

    /**
     *  查找 <pre> 标签并替换为占位符.
     *
     * @param string $content
     *
     * @return array
     */
    private function pregPre(string $content): array
    {
        // 查找 <pre> 标签并替换为占位符
        $oldContent = [];
        $content = preg_replace_callback('/<(pre|PRE)>(.*?)<\/\\1>/si', function ($matches) use (&$oldContent) {
            $placeholder = '___PRE__PLACEHOLDER__'.\count($oldContent).'___';
            $oldContent[$placeholder] = $matches[0]; // 存储原始的 <pre> 标签

            return $placeholder; // 返回占位符
        }, $content);

        return ['oldContent' => $oldContent, 'content' => $content];
    }
}
