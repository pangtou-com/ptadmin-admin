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

namespace PTAdmin\Admin\Service;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Enum\StatusEnum;
use PTAdmin\Admin\Exceptions\ServiceException;
use PTAdmin\Admin\Models\Setting;
use PTAdmin\Admin\Models\SettingGroup;

class SettingService
{
    /**
     * 存储配置值信息.
     *
     * @param $data
     */
    public function save($data): void
    {
        $ids = $data['ids'] ?? [];
        $configure = Setting::query()->whereIn('setting_group_id', $ids)->get()->toArray();
        if (0 === \count($configure)) {
            return;
        }
        $parent = SettingGroup::query()->select(['id', 'name'])->whereIn('id', $ids)->get()->toArray();
        $parent = array_to_map($parent, 'id', 'name');

        foreach ($configure as $item) {
            $name = ($parent[$item['setting_group_id']] ?? '').'_'.$item['name'];
            Setting::query()
                ->where('id', $item['id'])
                ->where('name', $item['name'])->update(['value' => $data[$name] ?? '']);
        }
        // 更新缓存信息
        self::updateSettingCache();
    }

    public function page($search = []): array
    {
        $filterMap = Setting::query();

        $filterMap->orderBy('setting_group_id');
        $filterMap->orderBy('weight', 'desc');
        $filterMap->with('category');

        return $filterMap->paginate()->toArray();
    }

    /**
     * 通过分组名称获取分组数据.
     *
     * @param $name
     * @param null|mixed $default
     *
     * @return array|mixed
     */
    public static function byGroupingName($name, $default = null)
    {
        $cate = SettingGroup::query()->with(['children', 'children.setting'])->where('name', $name)->first();
        if (!$cate) {
            return $default;
        }
        $cate = $cate->toArray();
        $children = $cate['children'];
        $data = [];
        foreach ($children as $child) {
            $temp = [];
            if ($child['setting'] && \count($child['setting']) > 0) {
                foreach ($child['setting'] as $item) {
                    $temp[$item['name']] = $item['value'];
                }
            }
            $data[$child['name']] = $temp;
        }

        return $data;
    }

    public function store(array $data): void
    {
        $filter = new Setting();
        DB::beginTransaction();

        try {
            $filter->fill($data)->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
        self::updateSettingCache();
    }

    /**
     * 编辑配置信息.
     *
     * @param $id
     * @param array $data
     */
    public function edit($id, array $data): void
    {
        $filter = Setting::query()->findOrFail($id);

        DB::beginTransaction();

        try {
            $filter->fill($data)->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
        self::updateSettingCache();
    }

    /**
     * 获取系统配置信息.
     *
     * @param $key
     * @param $default
     *
     * @return null|array|mixed
     */
    public static function getSetting($key, $default = null)
    {
        if (\is_string($key)) {
            return self::setting($key, $default);
        }
        if (\is_array($key)) {
            $return = [];
            foreach ($key as $k => $item) {
                $return[$item] = self::setting(\is_int($k) ? $item : $k, $default);
            }

            return $return;
        }

        return $default;
    }

    /**
     * 根据key值获取指定系统配置信息.
     *
     * @param string $key
     * @param null   $default
     *
     * @return array|mixed
     */
    public static function setting(string $key, $default = null)
    {
        $data = self::getSettingCache();
        if (null === $data) {
            return $default;
        }
        $keyArr = explode('.', $key);
        if (3 === \count($keyArr)) {
            return data_get($data, $key, $default);
        }
        $first = reset($keyArr);
        $group = $data['__group_names__'][$first] ?? null;
        if (null === $group) {
            return $default;
        }
        if ($group !== $first) {
            return data_get($data, "{$group}.{$key}", $default);
        }

        return data_get($data, $key, $default);
    }

    /**
     * 获取系统配置缓存.
     *
     * @return null|array|mixed
     */
    public static function getSettingCache()
    {
        if (Cache::has('systemSetting')) {
            $data = Cache::get('systemSetting');
            if (null !== $data) {
                return $data;
            }
        }

        return self::updateSettingCache();
    }

    /**
     * 更新系统配置缓存.
     *
     * @return null|array
     */
    public static function updateSettingCache(): ?array
    {
        $settingGroups = SettingGroup::query()
            ->where('status', StatusEnum::ENABLE)
            ->with('setting')->orderBy('parent_id')->get()->toArray();
        if (0 === \count($settingGroups)) {
            return null;
        }
        $maps = array_to_map($settingGroups, 'id', 'name');
        $keys = $data = [];
        foreach ($settingGroups as $settingGroup) {
            $keys[$settingGroup['name']] = $maps[$settingGroup['parent_id']] ?? $settingGroup['name'];
            if (null !== $settingGroup['setting'] && 0 !== $settingGroup['parent_id']) {
                $parent = $maps[$settingGroup['parent_id']];
                $settings = array_to_map($settingGroup['setting'], 'name', 'value');
                $data[$parent][$settingGroup['name']] = $settings;
            }
        }

        $data['__group_names__'] = $keys;

        Cache::forever('systemSetting', $data);

        return $data;
    }
}
