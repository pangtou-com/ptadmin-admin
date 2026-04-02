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

use Addon\Cms\Enum\CacheHandlerTypeEnum;
use Addon\Cms\Enum\SEOEnum;
use Addon\Cms\Enum\SitemapTypeEnum;
use Addon\Cms\Exceptions\CMSException;
use Addon\Cms\Models\Archive;
use Addon\Cms\Models\Category;
use Addon\Cms\Models\Seo;
use Addon\Cms\Models\SeoSitemap;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Exceptions\ServiceException;

class SeoService
{
    protected const CACHE_KEY = '__cms:seo:config__';

    protected $seoUrlService;

    public function __construct(SeoUrlService $seoUrlService)
    {
        $this->seoUrlService = $seoUrlService;
    }

    /**
     * 保存url配置.
     *
     * @param array $data
     */
    public function save(array $data): void
    {
        $seo = Seo::query()->first();
        if (blank($seo)) {
            $seo = new Seo();
        }
        $save = [];
        $save['access_type'] = $data['access_type'];
        $save['cache_processing'] = isset($data['cache_processing']) ? CacheHandlerTypeEnum::getSummaryValue($data['cache_processing']) : 0;
        $save['config'] = $data['config'];
        $this->checkCommonRules($save['config']);
        DB::beginTransaction();

        try {
            $seo->fill($save)->save();
            Cache::put(self::CACHE_KEY, $seo->config);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
        $this->seoUrlService->getAllPhp();
    }

    /**
     * 保存sitemap配置.
     *
     * @param mixed $data
     */
    public function saveSitemapConfig($data): void
    {
        $sitemap = SeoSitemap::query()->first();
        if (blank($sitemap)) {
            $sitemap = new SeoSitemap();
        }
        $data['sitemap_type'] = isset($data['sitemap_type']) ? SitemapTypeEnum::getSummaryValue($data['sitemap_type']) : 0;
        $data['filter_rule'] = isset($data['filter_rule']) ? SitemapTypeEnum::getSummaryValue($data['filter_rule']) : 0;
        DB::beginTransaction();

        try {
            $sitemap->fill($data)->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    /**
     * 获取url配置信息.
     *
     * @param bool $force
     *
     * @return array
     */
    public function getConfig(bool $force = false): array
    {
        if (Cache::has(self::CACHE_KEY) && !$force) {
            $config = Cache::get(self::CACHE_KEY);
            if (null !== $config) {
                return $config;
            }
        }
        /** @var null|Seo $seo */
        $seo = Seo::query()->first();
        if (null === $seo) {
            // 返回默认配置
            $default = [];
            $config = SEOEnum::getSupportParams();

            foreach ($config as $key => $item) {
                $current = [];
                $current['pre_route'] = $item['prefix'];
                $current['url'] = $item['default_url'];
                $current['title'] = $item['default_title'];
                $default[$key] = $current;
            }

            return $default;
        }
        Cache::put(self::CACHE_KEY, $seo->config);

        return $seo->config;
    }

    /**
     * 根据类型生成URL.
     *
     * @param int $type
     * @param array|mixed $params 参数可以是一个数组和模型
     * @param string|null $prefix
     * @return string
     */
    public function url(int $type, $params, ?string $prefix = null): string
    {
        // 获取配置信息
        $config = $this->getConfig();
        $id = $this->pre_verify_url($params, \in_array($type, [SEOEnum::LIST, SEOEnum::CHANNEL], true));
        if (0 === $id) {
            throw new ServiceException('参数无效');
        }
        $rule = $this->parserParams($config[$type]['url']);
        $data = $this->splicing_url($id, $rule, $type);

        $url = $config[$type]['url'];
        foreach ($rule as $item) {
            $url = str_replace("{{$item}}", $data[$item], $url);
        }

        $path = $prefix.'/'.$config[$type]['pre_route'].'/'.$url;

        return url($path);
    }



    /**
     * 返回url中需要替换参数的值.
     *
     * @param $id
     * @param $rule
     * @param $type
     *
     * @return array
     */
    public function splicing_url($id, $rule, $type): array
    {
        $config = $this->getConfig();
        if (!\in_array($type, [SEOEnum::TAG, SEOEnum::TOPIC], true)) {
            // detail类型返回
            if (SEOEnum::DETAIL === $type) {
                $detail = Archive::query()->with('category')->findOrFail($id);

                return [
                    'category_id' => $detail->category_id,
                    'category' => $detail->category->dir_name,
                    'mod' => $detail->mod->name ?? '',
                    'mod_id' => $detail->mod_id,
                    'id' => $detail->id,
                ];
            }
            // 栏目、频道、单页返回
            // 优先处理只存在id的情况
            if (1 === \count($rule) && 'category_id' === $rule[0]) {
                return ['category_id' => $id];
            }

            $category = Category::query()->where('id', $id);
            if (\in_array('mod', $rule, true)) {
                $category->with('mod');
            }

            /** @var Category $filterMap */
            $filterMap = $category->firstOrFail();
            $data = [
                'category_id' => $filterMap->id,
                'category' => $filterMap->dir_name,
                'mod' => $filterMap->mod->name ?? '',
                'mod_id' => $filterMap->mod_id,
            ];
        } else {
            // 标签、专题返回
            $data = [
                SEOEnum::getLowerKey($type).'_id' => $id,
            ];
        }
        $data['page'] = 1;

        return $data;
    }

    /**
     * 预处理url参数.
     *
     * @param $params
     * @param bool $categoryId
     *
     * @return int|mixed
     */
    public function pre_verify_url($params, bool $categoryId = false)
    {
        if (\is_object($params)) {
            $params = collect($params)->toArray();
        }
        if (\is_array($params)) {
            if ($categoryId && isset($params['category_id']) && $params['category_id'] > 0) {
                return $params['category_id'];
            }

            return $params['id'] ?? 0;
        }

        return (int) $params;
    }

    /**
     * 解析配置规则.
     * 返回url中所有{}中的参数.
     *
     * @param $str
     *
     * @return array
     */
    public function parserParams($str): array
    {
        if ('' === $str) {
            return [];
        }
        preg_match_all('/{([\w_]+)}/', $str, $matches);

        return $matches[1];
    }

    /**
     * 解析请求参数.
     *
     * @param int   $type   解析类型
     * @param array $params 请求参数
     */
    public function parserRequestParams(int $type, array $params): array
    {
        $config = $this->byTypeConfig($type);
        $url = $this->parserParams($config['url']);
        $seo = $this->parserParams($config['title']);
        if (\count($url) !== \count($params)) {
            throw new CMSException('请求规则与seo规则不一致，请确认seo规则是否正确');
        }
        $results = [];
        foreach ($params as $k => $param) {
            $results[$url[$k]] = $param;
        }

        return [
            $results,
            $seo,
        ];
    }

    /**
     * 根据type获取SEO配置信息.
     *
     * @param $type
     *
     * @return mixed
     */
    protected function byTypeConfig($type)
    {
        $config = $this->getConfig();
        foreach ($config as $key => $val) {
            if ($key === $type) {
                return $val;
            }
        }

        throw new CMSException('参数无效');
    }

    /**
     * 存储缓存信息.
     *
     * @param $val
     * @param $type
     */
    private function saveCache($val, $type): void
    {
        Cache::put('cms_route_and_controller_'.SEOEnum::getLowerKey($type), $val, 3600 * 24);
    }

    /**
     * seo规则校验.
     *
     * @param $val
     */
    private function checkCommonRules($val): void
    {
        foreach ($val as $key => $item) {
            $params = SEOEnum::getSupportParams($key);
            $url = $this->parserParams($item['url']);
            $title = $this->parserParams($item['title']);
            $fieldName = SEOEnum::getDescription($key);

            if (\count($url) !== \count(array_unique($url))) {
                throw new ServiceException("【{$fieldName}】路由参数中【访问地址】的支持参数仅允许存在一次");
            }
            $this->checkUrlRules($item['url'], $fieldName);

            //检测参数规则是否匹配
            if (\count(array_diff($url, array_keys($params['params']))) > 0) {
                throw new ServiceException("【{$fieldName}】路由参数与规则不匹配");
            }
            if (\count(array_diff($title, array_keys($params['title_params']))) > 0) {
                throw new ServiceException("【{$fieldName}】标题参数与规则不匹配");
            }

            // 检测是否缺少必要参数
            if (isset($params['required']) && \count($params['required']) > 0) {
                $required = array_diff($params['required'], $url);
                if (\count($required) >= \count($params['required'])) {
                    $required = implode(',', $required);

                    throw new ServiceException("【{$fieldName}】路由参数缺少必填参数【{$required}】");
                }
            }
        }
    }

    /**
     * 检测url中的字符格式是否正确.
     *
     * @param $url
     * @param $fieldName
     */
    private function checkUrlRules($url, $fieldName): void
    {
        preg_match_all('/{([\w_]+)}/', $url, $matches);
        $connector = str_replace($matches[0], 'xx', $url);
        // 支持的参数不能在 "." 之后
        if (preg_match('/\.(?=.*[{}])/', $url)) {
            throw new ServiceException("【{$fieldName}】路由参数中【访问地址】支持的参数不可在\" . \"之后");
        }
        // 分别匹配
        // 1、检测url是否以特殊字符开头
        if (preg_match('/^[.\/-]/', $connector)) {
            throw new ServiceException("【{$fieldName}】路由参数中【访问地址】不能以特殊字符开头");
        }
        // 2、检测url是否以特殊字符开头
        if (preg_match('/[.\/-]$/', $connector)) {
            throw new ServiceException("【{$fieldName}】路由参数中【访问地址】不能以特殊字符结尾");
        }
        // 3、检测url中的字符规则是否匹配
        if (!preg_match('/^[a-z0-9\/\-.]+$/', $connector)) {
            throw new ServiceException("【{$fieldName}】路由参数中【访问地址】的参数仅支持【小写英文字符、数字、\" - \"、\" / \"】");
        }
        // 4、检测url中的特殊字符是否匹配
        if (!preg_match('/^(?!.*[\/\-\.]{2})([^.]*\.[^.]*|[^.]+)$/', $connector)) {
            throw new ServiceException("【{$fieldName}】路由参数中【访问地址】中的特殊字符【\" / \"、\" - \"、\" . \"】不能连续使用，且【 \" . \" 只存在一个用于连接后缀】");
        }
        // 5、检测url中的后缀是否匹配
        if (!preg_match('/^(.*\.[a-z0-9]+|[^.]+)?$/', $connector)) {
            throw new ServiceException("【{$fieldName}】路由参数中【访问地址】中的【\" . \"前后必须都存在字符】，且\" . \"后仅作为后缀且仅支持【小写英文字符、数字】");
        }
    }
}
