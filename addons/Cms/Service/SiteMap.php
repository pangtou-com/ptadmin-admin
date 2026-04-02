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

namespace Addon\Cms\Service;

class SiteMap
{
    private $file;

    private $type;

    private function __construct($type)
    {
        $this->type = $type;
        $this->file = fopen(storage_path('/app/public/sitemap.'.$type), 'w');
        $this->init();
    }

    public function __destruct()
    {
        if (null !== $this->file) {
            fclose($this->file);
        }
    }

    /**
     * @param string $type
     * @param array  $data
     *
     * @return SiteMap
     */
    public static function make(string $type, array $data = []): self
    {
        $in = new self($type);
        if (\count($data) > 0) {
            $in->generate($data);
        }

        return $in;
    }

    public function generate($data): void
    {
        $func = 'write'.ucfirst($this->type);
        $str = '';
        if (!method_exists($this, $func)) {
            return;
        }
        $this->{$func}($data);
        /*foreach ($data as $key => $page) {
            $this->{$func}($page);
        }*/
    }

    public function end(): void
    {
        if ('xml' === $this->type) {
            fwrite($this->file, '</urlset>');
        }
        if ('html' === $this->type) {
            fwrite($this->file, '</body>\n</html>');
        }
    }

    private function writeXML($data): void
    {
        $text = [];
        foreach ($data as $k => $val) {
            $text[] = "<{$k}>{$val}</{$k}>";
        }
        $text = implode("\n", $text);
        $temp = "<url>\n{$text}\n</url>\n";

        fwrite($this->file, $temp);
    }

    private function writeTXT($data): void
    {
        // todo  待完成
        /*$text = [];
        foreach ($data as $k => $val) {
            $text[] = "{$k}: {$val}";
        }
        $temp = implode("\n", $text);*/
        $temp = $data."\n";
        fwrite($this->file, $temp);
    }

    private function writeHTML($data): void
    {
        // todo  待完成
        if (!\is_array($data)) {
            $temp = "<h1><a href='http://pt.com/'>胖头</a></h1>\n";
            fwrite($this->file, $temp);

            return;
        }
        foreach ($data as $key => $value) {
            foreach ($value as $k => $v) {
                if ('category_title' === $k) {
                    $temp = '<h2>'.$v."</h2>\n";
                    fwrite($this->file, $temp);

                    continue;
                }
                foreach ($v as $item) {
                    $temp = "<p>\n<a href='".$item['url']."'>".$item['title']."</a>\n</p>\n";
                    fwrite($this->file, $temp);
                }
            }
        }
    }

    private function init(): void
    {
        if ('xml' === $this->type) {
            fwrite($this->file, "<?xml version='1.0' encoding='UTF-8'?>\n");
            fwrite($this->file, "<urlset xmlns='https://www.sitemaps.org/schemas/sitemap/0.9' xmlns:mobile='https://www.google.com/schemas/sitemap-mobile/1.0'>\n");
        }
        if ('html' === $this->type) {
            fwrite($this->file, "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n");
            fwrite($this->file, "<head>\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />\n<title>sitemapHTML</title>\n</head>\n");
            fwrite($this->file, "<body>\n");
        }
    }
}
