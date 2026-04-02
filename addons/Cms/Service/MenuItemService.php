<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2026 重庆胖头网络技术有限公司，并保留所有权利。
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

use Addon\Cms\Models\Category;
use Addon\Cms\Models\Menu;
use Addon\Cms\Models\MenuItem;
use PTAdmin\Addon\Service\DirectivesDTO;
use PTAdmin\Admin\Enum\StatusEnum;

class MenuItemService
{
    /**
     * 导航列表.
     *
     * @param mixed $menuId
     *
     * @return array
     */
    public function getMenuItemTree(int $menuId): array
    {
        $menuItem = MenuItem::query()->where('menu_id', $menuId);
        $data = $menuItem->orderBy('weight', 'desc')->get()->toArray();

        return infinite_tree($data);
    }

    /**
     * 前端导航列表.
     *
     * @param DirectivesDTO $DTO
     * @param mixed         $code
     *
     * @return array
     */
    public function index(DirectivesDTO $DTO, $code = 'default'): array
    {
        $group = Menu::query()->where('code', $code)->where('status', StatusEnum::ENABLE)->first();
        if (!$group) {
            return [];
        }
        $allow = [
            ['field' => 'id', 'op' => 'in', 'query_field' => 'in_id'],
            ['field' => 'id', 'op' => 'not in', 'query_field' => 'not_id'],
        ];
        $model = MenuItem::search($allow, $DTO->all());
        $model = $model->select(['id', 'title', 'icon', 'cover', 'url', 'target', 'navigation_group_id', 'parent_id', 'status', 'type', 'category_id']);
        $model->where('status', StatusEnum::ENABLE);
        $model->where('navigation_group_id', $group->id);
        $results = $model->orderBy('weight', 'desc')->get()->toArray();

        // 筛选出为功能的菜单信息
        $funcIds = array_filter($results, function ($item) {
            return 2 === $item['type'];
        });

        if ($funcIds) {
            $ids = data_get($funcIds, '*.category_id');
            $category = Category::query()->where('status', 1)->get()->toArray();
            foreach ($results as &$result) {
                if ($result['category_id'] && \in_array($result['category_id'], $ids, true)) {
                    $result['children'] = $this->handleCateTree($category, $result['category_id']);
                }
            }
            unset($result);
        }

        return infinite_tree($results);
    }

    /**
     * 获取上级导航.
     *
     * @param int $menu_id
     *
     * @return array
     */
    public static function getOption(int $menu_id = 0): array
    {
        $navigation = MenuItem::query()->select(['id', 'title', 'parent_id']);
        if ($menu_id) {
            $navigation->where('menu_id', $menu_id);
        }
        $results = $navigation->orderBy('weight', 'desc')->get()->toArray();

        $data = [];
        infinite_level($results, $data);
        $res = [
            ['label' => '顶级栏目', 'value' => 0],
        ];
        foreach ($data as $datum) {
            $line = '';
            if ($datum['lv'] > 0) {
                $line = '| '.str_repeat('--', $datum['lv']);
            }
            $res[] = [
                'label' => $line.' '.$datum['title'],
                'value' => $datum['id'],
            ];
        }

        return $res;
    }

    /**
     * 保存导航.
     *
     * @param $data
     */
    public function store($data): void
    {
        $dao = new MenuItem();
        $data['parent_ids'] = norm_ids($data['parent_ids'] ?? []);
        if (\count($data['parent_ids']) > 0) {
            $data['parent_id'] = (int) end($data['parent_ids']);
        }
        $dao->fill($data);
        $dao->save();
    }

    /**
     * 编辑导航.
     *
     * @param $data
     * @param mixed $id
     */
    public function edit($data, $id): void
    {
        $dao = MenuItem::query()->findOrFail($id);
        $data['parent_ids'] = norm_ids($data['parent_ids'] ?? []);
        if (\count($data['parent_ids']) > 0) {
            $data['parent_id'] = (int) end($data['parent_ids']);
        }
        $dao->fill($data);
        $dao->update();
    }

    /**
     * 删除导航.
     *
     * @param int $id
     */
    public function delete(int $id): void
    {
        $dao = MenuItem::query()->findOrFail($id);
        // 同步删除下级菜单和同步状态
        $child = MenuItem::query()->whereJsonContains('parent_ids', $id)->get();
        foreach ($child as $item) {
            $item->delete();
        }
        $dao->delete();
    }

    private function handleCateTree($data, $parentId): array
    {
        $results = [];
        foreach ($data as $datum) {
            if ($datum['parent_id'] !== $parentId) {
                continue;
            }
            $datum['url'] = "/product/{$datum['dir_name']}.html";
            $datum['children'] = $this->handleCateTree($data, $datum['id']);
            $results[] = $datum;
        }

        return $results;
    }
}
