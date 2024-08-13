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
        if (!$configure) {
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
        $this->updateCache();
    }

    /**
     * 根据名称获取配置信息.
     *
     * @param $key
     * @param $default
     *
     * @return null|\Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
    public static function byNameValue($key, $default = null)
    {
        $keys = explode('.', $key);
        if (\count($keys) > 1) {
            /** @var SettingGroup $cate */
            $cate = SettingGroup::query()->select(['id'])
                ->where('name', reset($keys))->first();
            if (!$cate) {
                return $default;
            }
            $configure = Setting::query()
                ->where('setting_group_id', $cate->id)
                ->where('name', array_pop($keys))->first();
        } else {
            $configure = Setting::query()->where('name', $key)->first();
        }
        if (!$configure) {
            return $default;
        }

        return $configure->value;
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

    /**
     * 更新缓存内容.
     */
    public function updateCache(): void
    {
        $cate = SettingGroup::query()
            ->where('parent_id', '!=', 0)
            ->with('setting')
            ->get()->toArray();
        foreach ($cate as $item) {
            foreach ($item['setting'] as $value) {
                Cache::put($item['name'].'.'.$value['name'], $value['value']);
            }
        }
    }
}
