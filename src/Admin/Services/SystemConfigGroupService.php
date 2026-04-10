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

namespace PTAdmin\Admin\Services;

use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Models\SystemConfig;
use PTAdmin\Admin\Models\SystemConfigGroup;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Foundation\Exceptions\ServiceException;

class SystemConfigGroupService
{
    /**
     * 返回系统配置导航树。
     *
     * 这里只返回“一级分组 -> 二级分组”结构，给前端做左侧导航或页签切换使用，
     * 不再混入旧版 HTML 表单内容。
     *
     * @return array<int, array<string, mixed>>
     */
    public function navigation(): array
    {
        $groups = SystemConfigGroup::query()
            ->select(['id', 'parent_id', 'title', 'name', 'weight', 'intro', 'status'])
            ->where('status', 1)
            ->orderBy('parent_id')
            ->orderBy('weight', 'desc')
            ->orderBy('id')
            ->get()
            ->toArray();

        $roots = [];
        foreach ($groups as $group) {
            $group['children'] = [];
            if (0 === (int) $group['parent_id']) {
                $roots[$group['id']] = $group;
            }
        }

        foreach ($groups as $group) {
            if (0 === (int) $group['parent_id']) {
                continue;
            }
            if (!isset($roots[$group['parent_id']])) {
                continue;
            }

            $roots[$group['parent_id']]['children'][] = $group;
        }

        return array_values($roots);
    }

    public function tree(): array
    {
        $rows = SystemConfigGroup::query()
            ->select(['id', 'parent_id', 'title', 'name', 'weight', 'status'])
            ->orderBy('weight', 'desc')
            ->with('configs')
            ->get()
            ->map(static function (SystemConfigGroup $group): array {
                $row = $group->toArray();
                $row['id'] = (int) ($row['id'] ?? 0);
                $row['parent_id'] = (int) ($row['parent_id'] ?? 0);

                return $row;
            })
            ->all();

        return infinite_tree($rows);
    }

    /**
     * 新增系统配置分组。
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): SystemConfigGroup
    {
        try {
            return DB::transaction(function () use ($data): SystemConfigGroup {
                /** @var SystemConfigGroup $group */
                $group = new SystemConfigGroup();
                $group->fill($data)->save();

                return $group->refresh();
            });
        } catch (\Throwable $e) {
            throw new ServiceException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * 编辑系统配置分组。
     *
     * @param array<string, mixed> $data
     */
    public function edit(int $id, array $data): SystemConfigGroup
    {
        try {
            return DB::transaction(function () use ($id, $data): SystemConfigGroup {
                /** @var SystemConfigGroup $group */
                $group = SystemConfigGroup::query()->findOrFail($id);
                $group->fill($data)->save();

                return $group->refresh();
            });
        } catch (\Throwable $e) {
            throw new ServiceException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function del($id): void
    {
        $dao = SystemConfigGroup::query()->findOrFail($id);
        if (SystemConfigGroup::query()->where('parent_id', $id)->exists()) {
            throw new BackgroundException('请先删除子级配置');
        }
        if (SystemConfig::query()->where('system_config_group_id', $id)->exists()) {
            throw new BackgroundException('请删除配置项后再删除分类');
        }
        $dao->delete();
    }

    /**
     * 根据分组 ID 获取当前节点下的系统配置项信息。
     *
     * @param $id
     *
     * @return array
     */
    public function getRootConfigureCategoryId($id): array
    {
        $results = SystemConfigGroup::query()
            ->select(['id', 'parent_id', 'title', 'name', 'weight', 'intro', 'status'])
            ->where('parent_id', $id)
            ->with(['configs' => function ($query): void {
                $query->orderBy('weight', 'desc')->orderBy('id');
            }])
            ->orderBy('weight', 'desc')->orderBy('id')->get()->toArray();

        $data = [];
        foreach ($results as $result) {
            $configure = $result['configs'];
            unset($result['configs']);
            $result['children'] = $configure;
            $data[] = $result;
        }

        return $data;
    }

    /**
     * 根据父级 ID 获取当前节点下的系统配置项信息。
     *
     * @param $id
     *
     * @return array
     */
    public function byParentId($id): array
    {
        $id = (int) $id;
        $results = SystemConfigGroup::query()
            ->select(['id', 'parent_id', 'title', 'name', 'weight', 'intro', 'status'])
            ->where('parent_id', $id)
            ->orWhere('id', $id)
            ->with('configs')
            ->orderBy('weight', 'desc')->get()->toArray();

        $data = [];
        foreach ($results as $result) {
            $configure = $result['configs'];
            unset($result['configs']);
            // 当父级没有设置字段信息时 则没有展示的必要
            if ($id === $result['id'] && !$configure) {
                continue;
            }
            $data[] = [
                'category' => $result,
                'configs' => $configure,
            ];
        }

        return $data;
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
            /** @var SystemConfigGroup $model */
            $model = SystemConfigGroup::query()->updateOrCreate(['name' => $item['name']], $item);
            if (isset($item['children']) && \count($item['children']) > 0) {
                self::installInitialize($item['children'], $model->id);

                continue;
            }
            if (isset($item['fields']) && \count($item['fields']) > 0) {
                foreach ($item['fields'] as $field) {
                    $field['system_config_group_id'] = $model->id;
                    (new SystemConfig())->fill($field)->save();
                }
            }
        }
    }
}
