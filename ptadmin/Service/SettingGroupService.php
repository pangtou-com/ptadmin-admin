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

use PTAdmin\Admin\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\Setting;
use PTAdmin\Admin\Models\SettingGroup;
use PTAdmin\Build\Layui;

class SettingGroupService
{
    public function del($id): void
    {
        $dao = SettingGroup::query()->findOrFail($id);
        if (SettingGroup::query()->where('parent_id', $id)->exists()) {
            throw new BackgroundException('请先删除子级配置');
        }
        if (Setting::query()->where('setting_group_id', $id)->exists()) {
            throw new BackgroundException('请删除配置项后再删除分类');
        }
        $dao->delete();
    }

    /**
     * 获取全部分组的配置信息.
     *
     * @return array
     */
    public function getGroupAndSettingAll(): array
    {
        $results = SettingGroup::query()
            ->select(['id', 'parent_id', 'title', 'name', 'weight', 'intro', 'status'])
            ->with(['setting' => function ($query): void {
                $query->orderBy('weight', 'desc')->orderBy('id');
            }])
            ->orderBy('parent_id')
            ->orderBy('weight', 'desc')->orderBy('id')->get()->toArray();

        $group = $data = [];
        foreach ($results as $result) {
            if (0 === $result['parent_id']) {
                $group[$result['id']] = $result;

                continue;
            }
            $result['setting'] = $result['setting'] ?? [];
            $result['view'] = '';
            if ($result['setting']) {
                $result['view'] = $this->formView($result['setting'], $result);
            }

            $data[$group[$result['parent_id']]['name']][] = $result;
        }

        return [
            'group' => $group,
            'data' => $data,
        ];
    }

    /**
     * 根据分组ID获取当前ID下的配置项目信息.
     *
     * @param $id
     *
     * @return array
     */
    public function getRootConfigureCategoryId($id): array
    {
        $results = SettingGroup::query()
            ->select(['id', 'parent_id', 'title', 'name', 'weight', 'intro', 'status'])
            ->where('parent_id', $id)
            ->with(['setting' => function ($query): void {
                $query->orderBy('weight', 'desc')->orderBy('id');
            }])
            ->orderBy('weight', 'desc')->orderBy('id')->get()->toArray();

        $data = [];
        foreach ($results as $result) {
            $configure = $result['setting'];
            unset($result['setting']);
            $result['children'] = $configure;
            $data[] = $result;
        }

        return $data;
    }

    /**
     * 根据父级ID获取当前ID下的配置项目信息.
     *
     * @param $id
     *
     * @return array
     */
    public function byParentId($id): array
    {
        $results = SettingGroup::query()
            ->select(['id', 'parent_id', 'title', 'name', 'weight', 'remark', 'status'])
            ->where('parent_id', $id)
            ->orWhere('id', $id)
            ->with('setting')
            ->orderBy('weight', 'desc')->get()->toArray();

        $data = [];
        foreach ($results as $result) {
            $configure = $result['setting'];
            unset($result['setting']);
            // 当父级没有设置字段信息时 则没有展示的必要
            if ($id === $result['id'] && !$configure) {
                continue;
            }
            $data[] = [
                'category' => $result,
                'setting' => $configure,
            ];
        }

        return $data;
    }

    /**
     * 配置表单页面渲染.
     * TODO.
     *
     * @param $data
     * @param $parent
     *
     * @return string
     */
    public function formView($data, $parent): string
    {
        $html = [];
        foreach ($data as $key => $item) {
            $name = "{$parent['name']}_{$item['name']}";
            $view = Layui::{$item['type']}($name, $item['title'], $item['value']);
            if (null !== $item['default_val']) {
                $view->default($item['default_val']);
            }
            if ($item['intro']) {
                $view->hint($item['intro']);
            }
            if ($item['extra']) {
                if (isset($item['extra']['options'])) {
                    $view->options($item['extra']['options']);
                }
            }

            $html[] = $view->render();
        }

        return implode('', $html);
    }

    /**
     * 安装时数据初始化.
     *
     * @param array $data
     * @param mixed $parentId
     */
    public static function installInitialize(array $data, $parentId = 0): void
    {
        foreach ($data as $item) {
            $item['parent_id'] = $parentId;
            $model = SettingGroup::query()->updateOrCreate(['name' => $item['name']], $item);
            if (isset($item['children']) && \count($item['children']) > 0) {
                self::installInitialize($item['children'], $model->id);

                continue;
            }
            if (isset($item['fields']) && \count($item['fields']) > 0) {
                foreach ($item['fields'] as $field) {
                    $field['setting_group_id'] = $model->id;
                    (new Setting())->fill($field)->save();
                }
            }
        }
    }
}
