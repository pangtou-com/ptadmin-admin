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

namespace PTAdmin\Admin\Models\Traits;

use Illuminate\Support\Str;

trait SearchTrait
{
    /**
     * 允许的搜索字段.
     * $search_fields = [
     *    'field', 直接使用表字段
     *    'field1' => 'field',  // field1 为获取数据的字段，field为数据表字段.
     *    'field1' => [
     *          'field' => 'field',
     *          'op' => '=', // 搜索条件
     *          'map' => [], // 数据值映射
     *          'filter' => '' // 参数过滤器，可以对数据进行处理
     *    ]
     * ].
     *
     * @var array
     */
    protected $search_fields = [];

    /**
     * 基于场景设置的搜索条件.
     *
     * @var array
     */
    protected $search_scene = [];

    /**
     * 忽略的处理搜索字段. 字段不会内容过滤处理.
     *
     * @var array
     */
    protected $search_ignore = [];

    /**
     * 搜索条件.
     *
     * @param $query
     * @param array $fields
     * @param array $data   搜索条件
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, array $fields = [], array $data = []): \Illuminate\Database\Eloquent\Builder
    {
        $fields = \count($fields) > 0 ? $fields : $this->search_fields;
        $data = $this->buildSearchData($fields, $data);

        foreach ($data as $field => $value) {
            $this->buildWhere($query, $field, $value);
        }

        return $query;
    }

    /**
     * 基于场景的搜索条件.
     *
     * @param $query
     * @param string $scene
     * @param array  $data
     *
     * @return mixed
     */
    public function scopeSearchScene($query, string $scene, array $data = [])
    {
        if (!isset($this->search_scene[$scene])) {
            return $query;
        }
        $data = $this->buildSearchData($this->search_scene[$scene], $data);
        foreach ($data as $field => $value) {
            $this->buildWhere($query, $field, $value);
        }

        return $query;
    }

    /**
     * 基于场景的搜索条件构建.
     *
     * @param string $scene 场景名称 例如：'list', 'detail' 需要在模型中定义
     * @param array  $data
     *
     * @return mixed
     */
    public static function searchScene(string $scene, array $data = [])
    {
        return self::query()->searchScene($scene, $data);
    }

    /**
     * 搜索条件构建.
     *
     * @param array $fields 需要查询的字段，如果不设置则通过表字段进行查询
     * @param array $data   搜索条件
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function search(array $fields = [], array $data = []): \Illuminate\Database\Eloquent\Builder
    {
        return self::query()->search($fields, $data);
    }

    /**
     * 构建查询条件数据.
     *
     * @param array $fields
     * @param array $data
     *
     * @return array
     */
    protected function buildSearchData(array $fields = [], array $data = []): array
    {
        // 没有设置搜索字段，则通过表字段进行搜索
        if (0 === \count($fields)) {
            return [];
        }
        // 当没有传入数据时，通过表字段通过request获取数据
        if (0 === \count($data)) {
            $data = request()->all();
        }
        $results = [];
        foreach ($fields as $key => $field) {
            // 当key为数字时，表示字段为表字段
            $table_field = $data_key = $key;
            if (is_numeric($key)) {
                $data_key = \is_string($field) ? $field : $field['field'] ?? null;
                if (null === $data_key) {
                    continue;
                }
                $table_field = $data_key;
            } elseif (\is_array($field)) {
                $table_field = $field['field'] ?? $table_field;
            }

            if (!isset($data[$data_key]) || '' === $data[$data_key]) {
                continue;
            }

            $results[$table_field] = $this->parserSearchData($table_field, $data[$data_key], $field, $data_key);
        }

        return $results;
    }

    /**
     * 获取搜索字段.
     *
     * @return array
     */
    protected function getSearchTableFields(): array
    {
        if (method_exists($this, 'getTableFields')) {
            return $this->getTableFields();
        }

        return [];
    }

    /**
     * 解析搜索数据.
     *
     * @param $field
     * @param $value
     * @param $allow
     * @param $data_key
     *
     * @return array
     */
    protected function parserSearchData($field, $value, $allow, $data_key): array
    {
        $results = ['field' => $field, 'value' => $value, 'op' => '=', 'data_key' => $data_key];
        if (\is_string($allow)) {
            $results['field'] = $allow;
        } elseif (\is_array($allow) && isset($allow['field'])) {
            $results['field'] = $allow['field'];
        }

        // 当定义了字读映射时处理映射
        if (isset($allow['map']) && \is_array($allow['map'])) {
            $results['value'] = $allow['map'][$value] ?? $value;
        }

        if (isset($allow['op'])) {
            $opMap = ['NE' => '!=', 'LE' => '<=', 'GE' => '>=', 'LT' => '<', 'GT' => '>', 'LIKE' => 'like'];
            $key = strtoupper($allow['op']);
            $results['op'] = $opMap[$key] ?? $key;
        }

        // 当定义了过滤器时
        if (isset($allow['filter'])) {
            if (\is_callable($allow['filter'])) {
                $results['value'] = $allow['filter']($value);
            } else {
                $results['value'] = method_exists($this, $allow['filter']) ? $this->{$allow['filter']}($value) : $value;
            }
        } else {
            // 默认过滤器处理
            $results = $this->filter($results);
        }

        if ('like' === $results['op']) {
            $results['value'] = '%'.$results['value'].'%';
        }

        return $results;
    }

    protected function filter($results)
    {
        // 设置忽略处理的字段
        if (\in_array($results['value'], $this->search_ignore, true)) {
            return $results;
        }
        if (Str::endsWith('_id', $results['data_key'])) {
            if (false !== strpos(',', $results['value'])) {
                $results['value'] = explode(',', $results['value']);
                $results['op'] = 'IN';
            }

            return $results;
        }

        if (Str::endsWith('_range', $results['data_key'])) {
            if (false !== strpos(',', $results['value'])) {
                $value = explode(',', $results['value']);
                $results['value'] = [reset($value), end($value)];
                $results['op'] = 'BETWEEN';
            }

            return $results;
        }

        return $results;
    }

    /**
     * 构建查询条件.
     *
     * @param $query
     * @param $field
     * @param $value
     *
     * @return mixed
     */
    protected function buildWhere($query, $field, $value)
    {
        $op = $value['op'];
        $func = 'where';
        if ('IN' === $op) {
            $query->whereIn($field, $value['value']);

            return $query;
        }
        if ('NOT_IN' === $op) {
            $query->whereNotIn($field, $value['value']);

            return $query;
        }
        if ('BETWEEN' === $op) {
            $query->whereBetween($field, $value['value']);

            return $query;
        }
        $query->{$func}($field, $op, $value['value']);

        return $query;
    }
}
