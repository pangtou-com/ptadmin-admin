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

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait ModelField
{
    /**
     * 自定义默认值参数
     * 格式为 ['field' => value, 'field2' => value].
     *
     * @var array
     */
    public $table_default_value = [];

    /** @var array 数据表字段 */
    public static $table_fields = [];

    /**
     * 模型内的字段多语言 todo: 这里还需要完善 强制使用本地默认语言包的情况.
     *
     * @param $field
     *
     * @return string
     */
    public function __($field): ?string
    {
        return $this->getFieldLang()[$field] ?? null;
    }

    /**
     * 默认值填充.
     */
    public function fillDefaultValue(): void
    {
        if ($this->exists) {
            return;
        }
        $result = $this->getTableDefaultValue();
        if (!$result) {
            return;
        }
        foreach ($result as $key => $val) {
            if (self::CREATED_AT === $key || self::UPDATED_AT === $key || null === $val) {
                continue;
            }
            if (null === $this->{$key}) {
                $this->{$key} = $val;
            }
        }
    }

    /**
     * 获取表字段.
     *
     * @return array
     */
    public function getTableFields(): array
    {
        $results = $this->getCacheTableFields();
        // 去除主键
        if (isset($results[$this->getKeyName()])) {
            unset($results[$this->getKeyName()]);
        }

        return array_keys($results);
    }

    /**
     * 模型中定义的语言包
     * 定义格式为：['field' => "", 'field1' => ""].
     *
     * @return array
     */
    public function getFieldLang(): ?array
    {
        return null;
    }

    /**
     * 根据模型字段将字段名称翻译
     * 1、查找模型中是否有定义字段翻译包
     * 2、在查找是否在数据库中做定义 TODO: 这里未完成需要处理
     * 3、查找本地语言包文件.
     *
     * @param $field
     *
     * @return string
     */
    public function getFieldTranslation($field): string
    {
        if (null !== $this->__($field)) {
            return $this->__($field);
        }
        $table = table_to_prefix_empty(lcfirst($this->getTable()));

        return __("table.{$table}.{$field}");
    }

    /**
     * 将数据表字段初始化到文件中. todo 待处理.
     */
    public static function initTableFields(): void
    {
        self::$table_fields = (new static())->getTableFields();
    }

    /**
     * 设置默认值缓存debug模式下不存储缓存数据.
     */
    protected function getCacheTableFields(): array
    {
        $results = Cache::get($this->getCacheTableFieldsKey());
        if (null !== $results && true !== config('app.debug')) {
            return unserialize($results);
        }
        $results = $this->getTableAllFields();
        Cache::put($this->getCacheTableFieldsKey(), serialize($results));

        return $results;
    }

    /**
     * 获取数据表所有字段.
     *
     * @return array
     */
    protected function getTableAllFields(): array
    {
        $table = $this->getTable();
        $database = $this->getConnection()->getDatabaseName();
        $sql = "select column_name, column_default from information_schema.columns where table_name='{$table}' and table_schema='{$database}'";
        $obj = DB::select($sql);
        $results = [];
        if (null !== $obj && \count($obj) > 0) {
            foreach ($obj as $val) {
                $results[$val->column_name] = $val->column_default;
            }
        }

        return $results;
    }

    /**
     * 获取默认值
     *
     * @return array|mixed
     */
    protected function getTableDefaultValue(): array
    {
        if ($this->table_default_value) {
            return $this->table_default_value;
        }

        return $this->getCacheTableFields();
    }

    /**
     * 获取缓存key.
     *
     * @return string
     */
    protected function getCacheTableFieldsKey(): string
    {
        return "table_default_{$this->getTable()}";
    }
}
