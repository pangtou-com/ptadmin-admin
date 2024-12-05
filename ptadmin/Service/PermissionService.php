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
use Illuminate\Support\Str;
use PTAdmin\Admin\Enum\MenuTypeEnum;
use PTAdmin\Admin\Enum\StatusEnum;
use PTAdmin\Admin\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\Permission;
use PTAdmin\Admin\Models\System;
use PTAdmin\Html\Html;

class PermissionService
{
    /**
     * 保存数据.
     *
     * @param $data
     */
    public function store($data): void
    {
        $data['group_name'] = $data['group_name'] ?? config('auth.app_guard_name');
        if (isset($data['parent_name']) && Permission::TOP_PERMISSION_NAME !== (string) $data['parent_name']) {
            /** @var Permission $parent */
            $parent = Permission::query()->where('name', $data['parent_name'])->firstOrFail();
            $data['paths'] = array_merge($parent->paths ?? [], [$parent->name]);
        }
        Permission::create($data);
    }

    /**
     * 更新数据.
     *
     * @param $data
     * @param $id
     */
    public function edit($data, $id): void
    {
        /** @var Permission $perm */
        $perm = Permission::query()->findOrFail($id);
        if ($data['parent_name'] === $perm->name) {
            throw new BackgroundException('父级菜单不能为自身');
        }
        if (isset($data['parent_name']) && Permission::TOP_PERMISSION_NAME !== (string) $data['parent_name'] && $perm->parent_name !== $data['parent_name']) {
            /** @var Permission $parent */
            $parent = Permission::query()->where('name', $data['parent_name'])->firstOrFail();
            $data['paths'] = array_merge($parent->paths ?? [], [$parent->name]);
        }
        $perm->update($data);
        // 需要如果存在子集的情况需要同步更新子集路径
        Permission::renewChildrenPaths($perm->name, $perm->paths ?? []);
    }

    /**
     * 获取我的权限.按树形结构返回.
     *
     * @param $member
     *
     * @return array
     */
    public function myPermission($member): array
    {
        $results = $this->bySystemIdPermission($member->id);

        return infinite_tree($results, Permission::TOP_PERMISSION_NAME, 'parent_name', 'name');
    }

    /**
     * 通过管理员ID 获取管理员所使用的权限.
     *
     * @param $systemId
     *
     * @return array
     */
    public function bySystemIdPermission($systemId): array
    {
        /** @var System $system */
        $system = System::query()->findOrFail($systemId);
        // 创始人获取全部数据
        if (1 === $system->is_founder) {
            return Permission::getAllData(['status' => StatusEnum::ENABLE]);
        }
        $results = [];
        $full_paths = [];
        // 根据管理员角色获取权限
        foreach ($system->roles()->get() as $item) {
            $permissions = $item->permissions()
                ->where('status', StatusEnum::ENABLE)
                ->whereNull('deleted_at')
                ->orderBy('weight', 'desc')
                ->orderBy('id')
                ->get()->toArray();

            foreach ($permissions as $value) {
                if (isset($results[$value['id']])) {
                    continue;
                }
                $results[$value['id']] = $value;
                $full_paths = array_merge($full_paths, $value['paths'] ?? []);
            }
        }
        $full_paths = array_unique($full_paths);
        $full_results = Permission::query()->whereIn('name', $full_paths)->get()->toArray();

        return array_merge($results, $full_results);
    }

    /**
     * 生成后台菜单.
     *
     * @param $data
     * @param int $parent
     *
     * @return string
     */
    public function adminPermNav($data, int $parent = 0): string
    {
        $html = [];
        foreach ($data as $key => $datum) {
            // 导航不显示或为菜单类型时不显示
            if (!$datum['is_nav'] || MenuTypeEnum::BTN === $datum['type']) {
                continue;
            }
            $layuiThis = '';
            if (0 === $parent && 0 === $key) {
                $layuiThis = 'layui-this';
            }
            $str = 0 === $parent ? '<li class="layui-nav-item '.$layuiThis.'">' : '<dd>';
            if ($datum['route']) {
                if (Str::startsWith($datum['route'], 'http') && MenuTypeEnum::LINK === $datum['type']) {
                    $str .= '<a href="'.$datum['route'].'" target="_blank">';
                } else {
                    $str .= '<a href="javascript:;" ptadmin-href="'.admin_route($datum['route']).'" ptadmin-id="'.$datum['id'].'">';
                }
            } else {
                $str .= '<a href="javascript:;">';
            }
            if ($datum['icon']) {
                $str .= '<i class="'.$datum['icon'].'" data-icon="'.$datum['icon'].'"> </i>';
            }
            $str .= '<cite>'.$datum['title'].'</cite>';
            $str .= '</a>';
            if ($datum['children'] && \count($datum['children']) > 0) {
                $children = $this->adminPermNav($datum['children'], $datum['id']);
                if ('' !== $children) {
                    $str .= '<dl class="layui-nav-child">';
                    $str .= $children;
                    $str .= '</dl>';
                }
            }
            $str .= 0 === $parent ? '</li>' : '</dd>';
            $html[] = $str;
        }

        return implode('', $html);
    }

    /**
     * 获取权限列表.
     *
     * @param $results
     * @param int $parentId
     *
     * @return string
     */
    public function getRolePermissionHtml($results, int $parentId = 0): string
    {
        if (!$results) {
            return '';
        }

        $html = [];
        foreach ($results as $result) {
            $str = Html::checkbox($result['id'], '', $result['checked'] ?? false, [
                'name' => 'ids[]',
                'value' => $result['id'],
                'id' => 'idx_'.$result['id'],
                'lay-filter' => 'perm',
                'title' => $result['title'],
                'data-id' => $result['id'],
                'data-parent-id' => $parentId,
            ]);
            if (0 !== $parentId) {
                $str = Html::tag('div', $str, ['class' => 'box']);
            }
            if (isset($result['children']) && $result['children']) {
                $children = [];
                $children[] = '<div class="ptadmin-perm-card">';
                $children[] = '    <div class="header">';
                $children[] = $str;
                $children[] = '</div>';
                $children[] = '<div class="children" data-parent-id="'.$result['id'].'">';
                $children[] = $this->getRolePermissionHtml($result['children'], $result['id']);
                $children[] = '</div>';
                $children[] = '</div>';

                $html[] = implode('', $children);

                continue;
            }

            $html[] = $str;
        }

        return implode('', $html);
    }

    /**
     * 返回下拉菜单选项格式数据.
     *
     * @return array[]
     */
    public function getOption(): array
    {
        $data = Permission::getLevels();
        $res = [['label' => '顶级栏目', 'value' => Permission::TOP_PERMISSION_NAME]];
        foreach ($data as $datum) {
            $line = '';
            if ($datum['lv'] > 0) {
                $line = '| '.str_repeat('--', $datum['lv']);
            }
            $res[] = [
                'label' => $line.' '.$datum['title'],
                'value' => $datum['name'],
            ];
        }

        return $res;
    }

    /**
     * 获取管理用户设置的快捷导航.
     *
     * @param $systemId
     *
     * @return array[]
     */
    public function getQuickNav($systemId): array
    {
        $key = 'quick_nav_'.$systemId;
        if (!Cache::has($key)) {
            return [];
        }
        $data = @json_decode(Cache::get($key), true);
        $results = [];

        /** @var System $system */
        $system = System::query()->findOrFail($systemId);
        if (1 === $system->is_founder) {
            return $data;
        }
        // 校验是否有权限
        foreach ($data as $datum) {
            if ($system->can($datum['name'])) {
                $results[] = $datum;
            }
        }

        return $results;
    }

    /**
     * 通过用户ID获取默认的快捷导航.默认情况下取4个.
     *
     * @param $systemId
     *
     * @return array|mixed
     */
    public function getDefaultQuickNav($systemId)
    {
        $results = $this->bySystemIdPermission($systemId);
        $rules = [];
        foreach ($results as $result) {
            if (
                blank($result['route'])
                || !$result['is_nav']
                || MenuTypeEnum::DIR === $result['type']
                || MenuTypeEnum::BTN === $result['type']
            ) {
                continue;
            }
            $rules[] = $result;
        }
        $rules = array_chunk($rules, 4);

        return $rules[0] ?? [];
    }

    /**
     * 设置用户快捷导航.
     *
     * @param $systemId
     * @param $data
     */
    public function setQuickNav($systemId, $data): void
    {
        $key = 'quick_nav_'.$systemId;
        if (0 === \count($data)) {
            Cache::forget($key);

            return;
        }
        $results = Permission::query()
            ->select(['id', 'parent_name', 'name', 'title', 'route', 'component', 'icon', 'type', 'status', 'is_nav'])
            ->whereIn('id', $data)
            ->where('status', StatusEnum::ENABLE)
            ->whereNull('deleted_at')
            ->where('type', MenuTypeEnum::NAV)
            ->orderBy('weight', 'desc')
            ->orderBy('id')
            ->get()->toArray();

        Cache::put($key, @json_encode($results));
    }

    /**
     * 安装插件时的菜单处理.
     *
     * @param $addonInfo
     * @param $menu
     * @param null|mixed $parentName
     */
    public static function addonInstallMenu($addonInfo, $menu, $parentName = null): void
    {
        $parentId = null;
        if (null !== $parentName) {
            $parent = Permission::query()->where('name', $parentName)->first();
            if (null !== $parent) {
                $parentId = $parent->name;
            }
        }
        $instance = new self();
        if (null === $parentId) {
            $parent = $instance->installParentMenu($addonInfo);
            $parentId = $parent->name;
        }
        $instance->installChildMenu($addonInfo, $menu, $parentId);
    }

    /**
     * 创建下级菜单.
     *
     * @param $addonInfo
     * @param $menu
     * @param $parentId
     */
    public function installChildMenu($addonInfo, $menu, $parentId): void
    {
        foreach ($menu as $item) {
            $permission = new Permission();
            $permission->name = $addonInfo['code'].'.'.$item['name'];
            $permission->addon_code = $addonInfo['code'];
            $permission->title = $item['title'];
            $permission->note = $item['note'] ?? '';
            $permission->route = $item['route'] ?? '';
            $permission->parent_name = $parentId;
            $permission->type = $item['type'];
            $permission->is_nav = $item['is_nav'] ?? 1;
            $permission->icon = $item['icon'] ?? '';
            $permission->guard_name = config('auth.app_guard_name');
            $permission->save();

            if (isset($item['children']) && $item['children']) {
                $this->installChildMenu($addonInfo, $item['children'], $permission->id);
            }
        }
    }

    /**
     * 新增插件父菜单.
     *
     * @param $addonInfo
     *
     * @return Permission
     */
    public function installParentMenu($addonInfo): Permission
    {
        $parent = Permission::query()->where('name', $addonInfo['code'])->first();
        // 当已经存在菜单的情况下时需要将权限名称调整一下
        $code = $addonInfo['code'];
        if (null !== $parent) {
            $code = $addonInfo['code'].'_'.Str::random(6);
        }

        $parent = new Permission();
        $parent->name = $code;
        $parent->addon_code = $addonInfo['code'];
        $parent->title = $addonInfo['title'];
        $parent->note = $addonInfo['description'];
        $parent->type = MenuTypeEnum::DIR;
        $parent->save();

        return $parent;
    }

    /**
     * 卸载插件时的菜单处理.
     *
     * @param $addonName
     */
    public static function addonUninstallMenu($addonName): void
    {
        Permission::query()->where('addon_code', $addonName)->delete();
    }
}
