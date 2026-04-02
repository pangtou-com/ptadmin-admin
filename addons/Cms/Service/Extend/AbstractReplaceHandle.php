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

use Illuminate\Support\Facades\Cache;

abstract class AbstractReplaceHandle
{
    /** @var array 关键词替换数组. */
    protected $replaces;

    /** @var int 单关键词替换上限. */
    protected $wordLimit = 2;

    /** @var int 总关键词替换上限. */
    protected $allLimit = 10;
    protected $data;

    /**
     * 管道调用方式.
     *
     * @param $data
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($data, \Closure $next)
    {
        $data = $this->run($data);

        return $next($data);
    }

    public function action($data): array
    {
        // 获取数据源
        $search = $this->getReplaces(true);

        // 替换并返回
        return $this->replaceContent($search, $data);
    }

    public function run($data)
    {
        $this->data = $data;
        if (method_exists($this, 'beforeRun')) {
            $data = $this->beforeRun($data);
        }
        $data = $this->action($data);
        if (method_exists($this, 'afterRun')) {
            $data = $this->afterRun($data);
        }

        return $data;
    }

    public static function boot($data)
    {
        return (new static())->run($data);
    }

    /**
     * 测试用.
     *
     * @param $data
     *
     * @return array
     */
    public function search($data): array
    {
        $replaces = $this->getReplaces();

        return $this->replaceContent($replaces, $data);
    }

    // 获取数据源（查询库或文件之前先查询缓存，缓存没有再查库，查完以后写入缓存）
    // 查找并替换关键词（需要设置单个关键词的最大替换数以及替换的总数）
    // 1、getCache($flag)
    // 2、getReplaces()
    // 3、saveCache($flag)
    // 4、strReplace($replaces, $content)
    // pr $wordLimit, pr $allLimit
    // get $wordLimit, $allLimit //配置 、参数

    /**
     * 获取替换数组.
     *
     * @param bool $force
     *
     * @return null|array
     */
    public function getReplaces(bool $force = false): ?array
    {
        if (Cache::has($this->getCacheKey()) && !$force) {
            $data = Cache::get($this->getCacheKey());
            if (null !== $data) {
                return $data;
            }
        }
        $replace = $this->getQueryReplaces();
        Cache::put($this->getCacheKey(), $replace);

        return $replace;
    }

    /**
     * 获取缓存key.
     *
     * @return string
     */
    protected function getCacheKey(): string
    {
        return static::class;
    }

    /**
     * 替换内容.
     *
     * @param $replaces
     * @param $data
     *
     * @return array
     */
    abstract protected function replaceContent($replaces, $data): array;

    /**
     * 获取单个关键词替换上限.
     *
     * @return int
     */
    protected function getWordLimit(): int
    {
        if (isset($this->data['word_limit']) && $this->data['word_limit'] > 0) {
            return $this->data['word_limit'];
        }
        // 读取环境配置

        return $this->wordLimit;
    }

    /**
     * 获取总关键词替换上限.
     *
     * @return int
     */
    protected function getAllLimit(): int
    {
        if (isset($this->data['all_limit']) && $this->data['all_limit'] > 0) {
            return $this->data['all_limit'];
        }
        // 读取环境配置
        return $this->allLimit;
    }

    /**
     * 获取替换数组.
     *
     * @return array
     */
    abstract protected function getQueryReplaces(): array;

    protected function beforeRun($data)
    {
        return $data;
    }

    protected function afterRun($data)
    {
        return $data;
    }
}
