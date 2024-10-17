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

use Illuminate\Support\Carbon;

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
     * @var array 需要值为数组的运算符号
     */
    protected $operator_array = ['in', 'not in', 'between', 'not between'];

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
        if (0 === \count($fields)) {
            return $query;
        }
        $data = $this->buildSearchData($fields, $data);

        foreach ($data as $field => $value) {
            if (isset($value['field']) && \is_array($value['field'])) {
                $this->buildOrWhere($query, $value);

                continue;
            }
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
            if (isset($value['field']) && \is_array($value['field'])) {
                $this->buildOrWhere($query, $value);

                continue;
            }
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
     * @param array $fields 允许查询的字段信息
     * @param array $data   搜索条件
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
            $table_field = $this->getSearchTableField($key, $field);
            if (null === $table_field) {
                continue;
            }

            /**
             * 设置获取请求参数可支持类型为：
             * $data = [
             *      "请求参数名称" => ["field" => "数据表字段名称"]， 方式1
             *      ["field" => "数据表字段名称", "query_field" => "请求参数名称"]，方式2
             *      "field" => "=" // 方式3
             * ];.
             */
            $val = $this->buildSearchParams(is_numeric($key) ? $table_field : $key, $field, $data);
            if (null === $val) {
                continue;
            }
            // 在配置时可能会出现一个字段可查询多个字段数据的情况，可通过配置 fields 的方式实现
            $val['field'] = isset($field['fields']) && \is_array($field['fields']) ? $field['fields'] : $table_field;
            $results[$table_field] = $val;
        }

        return $results;
    }

    /**
     * 获取查询数据表字段信息呢.
     *
     * @param $key
     * @param $field
     *
     * @return string
     */
    protected function getSearchTableField($key, $field): ?string
    {
        if (\is_array($field) && isset($field['field']) && \is_string($field['field'])) {
            return $field['field'];
        }
        if (\is_string($field) && is_numeric($key)) {
            return $field;
        }
        if (\is_string($key)) {
            return $key;
        }

        return null;
    }

    /**
     * 获取请求参数.
     *
     * @param $tableField
     * @param $field
     *
     * @return mixed
     */
    protected function getQueryField($tableField, $field)
    {
        if (\is_array($field) && isset($field['query_field'])) {
            return $field['query_field'];
        }

        return $tableField;
    }

    /**
     * 构建搜索所需的参数数组信息.
     *
     * @param array $data
     * @param mixed $field
     * @param mixed $key
     *
     * @return null|array
     */
    protected function buildSearchParams($key, $field, array $data): ?array
    {
        $query_field = $this->getQueryField($key, $field);
        $value = $data[$query_field] ?? null;
        if (blank($value)) {
            return null;
        }

        list($val, $op) = $this->getFilterValue($value, $field);
        if (null === $val) {
            return null;
        }

        $results['value'] = $val;
        $results['op'] = $op;

        return $results;
    }

    /**
     * 获取过滤后的值
     *
     * @param mixed $value 请求参数值
     * @param mixed $field 配置字段信息
     *
     * @return null|array
     */
    protected function getFilterValue($value, $field): ?array
    {
        $val = $this->getValue($value);

        if (blank($val)) {
            return null;
        }

        $op = '=';
        if (\is_array($value)) {
            $op = $value['op'] ?? $field['op'] ?? '=';
            $op = $this->getSearchOperator($val, $op);
        }
        $op = strtolower($op);
        // 当值不为数组但符号需要数值类型时需要重置为数组类型，默认情况下按照 `,` 分隔
        if (\in_array($op, $this->operator_array, true) && !\is_array($val)) {
            $val = explode(',', $val);
        }
        // 参数过滤或转换处理
        $val = $this->actionFilter($val, $field['filter'] ?? null);

        return [$val, $op];
    }

    /**
     * 执行过滤器处理.
     *
     * @param $val
     * @param $filter
     *
     * @return mixed
     */
    protected function actionFilter($val, $filter)
    {
        if (null === $filter) {
            return $val;
        }
        if (\function_exists($filter)) {
            return $filter($val);
        }
        if (\is_string($filter) && method_exists($this, $filter)) {
            return $this->{$filter}($val);
        }

        return $val;
    }

    /**
     * 将参数转换为数字类型.
     *
     * @param $val
     *
     * @return array|int
     */
    protected function toInt($val)
    {
        if (\is_array($val)) {
            return array_map(static function ($v) {
                return (int) $v;
            }, $val);
        }

        return (int) $val;
    }

    /**
     * 将参数转换为浮点类型.
     *
     * @param $val
     *
     * @return array|float
     */
    protected function toFloat($val)
    {
        if (\is_array($val)) {
            return array_map(static function ($v) {
                return (float) $v;
            }, $val);
        }

        return (float) $val;
    }

    /**
     * 将参数转换为时间戳.
     *
     * @param $val
     *
     * @return array|int|int[]
     */
    protected function toTime($val)
    {
        if (\is_array($val)) {
            return array_map(static function ($v) {
                return Carbon::make($v)->getTimestamp();
            }, $val);
        }

        return Carbon::make($val)->getTimestamp();
    }

    protected function getSearchOperator($val, $op): string
    {
        // 在根据数据类型重新对符号进行处理
        // 当值为数组但符号不在in,not in,between中，则需要重置符号
        if (!\is_array($val) || \in_array($op, $this->operator_array, true)) {
            return $op;
        }
        // 如果为两个元素的数组，则默认为between
        if (2 === \count($val)) {
            return 'between';
        }
        if (1 === \count($val)) {
            if (isset($val['min'])) {
                return '>=';
            }
            if (isset($val['max'])) {
                return '<=';
            }
        }

        return 'in';
    }

    /**
     * 获取请求值
     *
     * @param $value
     *
     * @return array|mixed
     */
    protected function getValue($value)
    {
        if (!\is_array($value)) {
            return $value;
        }
        if (!\array_key_exists('value', $value)
            && !\array_key_exists('min', $value)
            && !\array_key_exists('max', $value)) {
            return $value;
        }
        if (isset($value['value'])) {
            return $value['value'];
        }
        $temp = [];
        if (isset($value['min']) && !blank($value['min'])) {
            $temp['min'] = $value['min'];
        }
        if (isset($value['max']) && !blank($value['max'])) {
            $temp['max'] = $value['max'];
        }
        if (2 === \count($temp)) {
            sort($temp);

            return $temp;
        }

        return $temp;
    }

    /**
     * 构建查询条件.
     *
     * @param $query
     * @param $field
     * @param $value
     * @param mixed $boolean
     *
     * @return mixed
     */
    protected function buildWhere($query, $field, $value, $boolean = 'and')
    {
        switch ($value['op']) {
            case 'in':
                $query->whereIn($field, $value['value'], $boolean);

                break;

            case 'not in':
                $query->whereNotIn($field, $value['value'], $boolean);

                break;

            case 'between':
                $query->whereBetween($field, $value['value'], $boolean);

                break;

            case 'not between':
                $query->whereNotBetween($field, $value['value'], $boolean);

                break;

            case 'like':
                $query->where($field, 'like', '%'.$value['value'].'%', $boolean);

                break;

            default:
                $query->where($field, $value['op'], $value['value'], $boolean);
        }

        return $query;
    }

    protected function buildOrWhere($query, $value)
    {
        $query->where(function ($q) use ($value): void {
            foreach ($value['fields'] as $item) {
                $this->buildWhere($q, $item, $value, 'or');
            }
        });

        return $query;
    }
}
