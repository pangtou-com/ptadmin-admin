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

namespace PTAdmin\Admin\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PTAdmin\Addon\Addon;
use PTAdmin\Admin\Support\Query\BuilderQueryApplier;
use PTAdmin\Support\Enums\MenuTypeEnum;
use PTAdmin\Support\Enums\StatusEnum;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Contracts\Auth\AdminGrantServiceInterface;
use PTAdmin\Contracts\Auth\AdminResourceServiceInterface;
use PTAdmin\Contracts\Auth\AuthorizationServiceInterface;
use PTAdmin\Support\Enums\Ability;
use PTAdmin\Support\Enums\GrantEffect;
use PTAdmin\Support\ValueObjects\GrantPayload;

class AdminResourceService
{
    private const TOP_RESOURCE_NAME = '0';

    private AdminResourceServiceInterface $adminResourceService;
    private AdminGrantServiceInterface $adminGrantService;

    public function __construct(AdminResourceServiceInterface $adminResourceService, AdminGrantServiceInterface $adminGrantService)
    {
        $this->adminResourceService = $adminResourceService;
        $this->adminGrantService = $adminGrantService;
    }

    public function store($data): void
    {
        $this->adminResourceService->create($this->normalizeResourcePayload((array) $data));
    }

    public function detail($id)
    {
        /** @var AdminResource $resource */
        $resource = $this->adminResourceService->find((int) $id);

        return $this->mapResourceDetail($resource);
    }

    public function edit($data, $id): void
    {
        $this->adminResourceService->update((int) $id, $this->normalizeResourcePayload((array) $data, false));
    }

    public function deleteResourceIds(array $ids): void
    {
        foreach (array_unique(array_map('intval', $ids)) as $id) {
            $this->adminResourceService->delete($id);
        }
    }

    public function getRoleResourceAssignment(int $roleId): array
    {
        /** @var AdminRole $role */
        $role = AdminRole::query()->findOrFail($roleId);
        $resourceIds = $this->resolveResourceIdsFromResourceCodes(
            array_column($this->adminGrantService->getRoleGrants((int) $role->id), 'resource_code')
        );

        return [
            'results' => $this->resourceTree(),
            'detail' => [
                'id' => $role->id,
                'title' => $role->name,
                'origin_id' => 0,
                'department_id' => 0,
                'scope' => 0,
            ],
            'resource_ids' => $resourceIds,
        ];
    }

    public function getRoleResourceSelection(int $roleId): array
    {
        /** @var AdminRole $role */
        $role = AdminRole::query()->findOrFail($roleId);
        $checked = $this->resolveResourceIdsFromResourceCodes(
            array_column($this->adminGrantService->getRoleGrants((int) $role->id), 'resource_code')
        );
        $checkedMap = array_fill_keys($checked, true);

        return [
            'checked' => array_values(array_unique(array_map('intval', $checked))),
            'results' => $this->markCheckedResources($this->resourceTree(), $checkedMap, $checked),
        ];
    }

    public function getAdminResourceAssignment(int $adminId): array
    {
        /** @var Admin $admin */
        $admin = Admin::query()->findOrFail($adminId);
        $resourceIds = $this->resolveResourceIdsFromResourceCodes(
            array_column($this->adminGrantService->getUserDirectGrants((int) $admin->id), 'resource_code')
        );

        return [
            'results' => $this->resourceTree(),
            'detail' => [
                'id' => $admin->id,
                'title' => $admin->nickname,
                'origin_id' => $admin->origin_id,
                'department_id' => $admin->department_id,
                'scope' => $admin->scope,
            ],
            'resource_ids' => $resourceIds,
        ];
    }

    public function syncRoleResourceAssignment(int $roleId, array $resourceIds): void
    {
        DB::transaction(function () use ($roleId, $resourceIds): void {
            AdminRole::query()->findOrFail($roleId);
            $this->adminGrantService->syncRoleGrants($roleId, $this->buildGrantPayloadsFromResourceIds($resourceIds));
        });
    }

    public function syncRoleResourceSelection(int $roleId, array $resourceIds): void
    {
        $this->syncRoleResourceAssignment($roleId, $resourceIds);
    }

    public function syncAdminResourceAssignment(int $adminId, array $resourceIds): void
    {
        DB::transaction(function () use ($adminId, $resourceIds): void {
            /** @var Admin $admin */
            $admin = Admin::query()->findOrFail($adminId);
            $this->adminGrantService->syncUserGrants((int) $admin->id, $this->buildGrantPayloadsFromResourceIds($resourceIds));
        });
    }

    public function myResources($member): array
    {
        $results = $this->byAdminIdResources($member->id);

        return infinite_tree($results, self::TOP_RESOURCE_NAME, 'parent_name', 'name');
    }

    public function byAdminIdResources($adminId): array
    {
        /** @var Admin $admin */
        $admin = Admin::query()->findOrFail($adminId);
        $resources = $this->resourceRows(['status' => StatusEnum::ENABLE]);
        
        if (1 === (int) $admin->is_founder) {
            return $this->mergeDevelopAddonResources($resources, true);
        }

        $resourceMap = [];
        foreach ($resources as $resource) {
            $resourceMap[(string) $resource['name']] = $resource;
        }

        $visibleCodes = app(AuthorizationServiceInterface::class)->visibleResources(
            $admin,
            array_keys($resourceMap)
        );

        $results = [];
        $fullPaths = [];
        foreach ($visibleCodes as $resourceCode) {
            if (!isset($resourceMap[$resourceCode])) {
                continue;
            }

            $resource = $resourceMap[$resourceCode];
            $results[(int) $resource['id']] = $resource;
            $fullPaths = array_merge($fullPaths, $resource['parent_ids'] ?? []);
        }

        $fullPaths = array_values(array_unique(array_map('intval', $fullPaths)));
        if ([] !== $fullPaths) {
            foreach ($resources as $resource) {
                if (\in_array((int) $resource['id'], $fullPaths, true)) {
                    $results[(int) $resource['id']] = $resource;
                }
            }
        }

        return $this->mergeDevelopAddonResources(array_values($results), false);
    }


    public function getOption(): array
    {
        $data = [];
        infinite_level(
            $this->resourceRows(['status' => StatusEnum::ENABLE]),
            $data,
            'name',
            'parent_name',
            self::TOP_RESOURCE_NAME
        );
        $res = [['label' => __('ptadmin::common.resource.top_level'), 'value' => self::TOP_RESOURCE_NAME]];
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

    public function resourceTree(array $filters = []): array
    {
        return infinite_tree($this->resourceRows($filters), self::TOP_RESOURCE_NAME, 'parent_name', 'name');
    }

    public function resourceRows(array $filters = []): array
    {
        $query = AdminResource::query()->whereNull('deleted_at');
        $filters = $this->normalizeResourceListQuery($filters);

        (new BuilderQueryApplier())->apply(
            $query,
            $filters,
            [
                'allowed_filters' => ['id', 'parent_id', 'title', 'name', 'status', 'sort', 'module', 'page_key', 'route', 'type', 'is_nav'],
                'allowed_sorts' => ['id', 'parent_id', 'title', 'name', 'status', 'sort', 'module', 'type', 'is_nav'],
                'allowed_keyword_fields' => ['title', 'name', 'module', 'page_key', 'route'],
                'keyword_fields' => ['title', 'name', 'module', 'page_key', 'route'],
                'default_order' => ['sort' => 'asc', 'id' => 'asc'],
            ]
        );
        $resources = $query->get();
        $resourceMapById = [];
        $resourceNameById = [];
        foreach ($resources as $resource) {
            $resourceMapById[(int) $resource->id] = $resource;
            $resourceNameById[(int) $resource->id] = (string) $resource->name;
        }

        return $resources->map(function (AdminResource $resource) use ($resourceMapById, $resourceNameById): array {
            $data = $resource->toArray();
            $data['parent_name']  = $resourceNameById[$data['parent_id']] ?? self::TOP_RESOURCE_NAME;
            $data['parent_ids']  = $this->resolveAncestorIds((int) $data['parent_id'], $resourceMapById);
            $data['paths']  = $this->resolveAncestorCodes((int) $data['parent_id'], $resourceMapById);
            
            return $this->mergeMetaJsonIntoResourceData($data);
        })->values()->all();
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    private function normalizeResourceListQuery(array $query): array
    {
        if (isset($query['filters']) || isset($query['sorts']) || isset($query['keyword']) || isset($query['keyword_fields'])) {
            return $query;
        }

        $filters = [];
        foreach ($query as $field => $value) {
            if (!is_string($field)) {
                continue;
            }

            $filters[] = [
                'field' => $field,
                'operator' => is_array($value) ? 'in' : '=',
                'value' => $value,
            ];
        }

        return ['filters' => $filters];
    }

    /**
     * @param array<int, array<string, mixed>> $resources
     *
     * @return array<int, array<string, mixed>>
     */
    private function mergeDevelopAddonResources(array $resources, bool $allowPreview): array
    {
        $overlays = $this->developAddonResourceRows();
        if ([] === $overlays) {
            return $resources;
        }

        $resourceMap = [];
        $idByName = [];
        foreach ($resources as $resource) {
            $name = (string) ($resource['name'] ?? '');
            if ('' === $name) {
                continue;
            }

            $resourceMap[$name] = $resource;
            $idByName[$name] = (int) $resource['id'];
        }

        $nextPreviewId = -1;
        foreach ($overlays as $name => $overlay) {
            if (isset($resourceMap[$name])) {
                $resourceMap[$name] = $this->applyDevelopResourceOverlay($resourceMap[$name], $overlay, $allowPreview);

                continue;
            }

            if (!$allowPreview || (int) ($overlay['status'] ?? StatusEnum::ENABLE) !== StatusEnum::ENABLE) {
                continue;
            }

            $resourceMap[$name] = [
                'id' => $nextPreviewId,
                'parent_id' => 0,
                'parent_name' => (string) ($overlay['parent_name'] ?? self::TOP_RESOURCE_NAME),
                'parent_ids' => [],
                'title' => (string) ($overlay['title'] ?? $name),
                'name' => $name,
                'status' => (int) ($overlay['status'] ?? StatusEnum::ENABLE),
                'sort' => (int) ($overlay['sort'] ?? 0),
                'module' => $overlay['module'] ?? null,
                'page_key' => $overlay['page_key'] ?? null,
                'route' => $overlay['route'] ?? null,
                'type' => (string) ($overlay['type'] ?? MenuTypeEnum::NAV),
                'is_nav' => (int) ($overlay['is_nav'] ?? 0),
                'icon' => $overlay['icon'] ?? null,
            ];
            $idByName[$name] = $nextPreviewId;
            --$nextPreviewId;
        }

        if ($allowPreview) {
            foreach ($resourceMap as $name => &$resource) {
                $parentName = (string) ($resource['parent_name'] ?? self::TOP_RESOURCE_NAME);
                if (self::TOP_RESOURCE_NAME === $parentName || '' === $parentName || $parentName === $name || !isset($idByName[$parentName])) {
                    $resource['parent_id'] = 0;
                    $resource['parent_name'] = self::TOP_RESOURCE_NAME;

                    continue;
                }

                $resource['parent_id'] = (int) $idByName[$parentName];
                $resource['parent_name'] = $parentName;
            }
            unset($resource);

            $resourceMapById = [];
            foreach ($resourceMap as $resource) {
                $resourceMapById[(int) $resource['id']] = $resource;
            }

            foreach ($resourceMap as &$resource) {
                $resource['parent_ids'] = $this->resolveOverlayAncestorIds((int) $resource['parent_id'], $resourceMapById);
            }
            unset($resource);
        }

        $rows = array_values($resourceMap);
        usort($rows, static function (array $left, array $right): int {
            $compare = ((int) ($left['sort'] ?? 0)) <=> ((int) ($right['sort'] ?? 0));
            if (0 !== $compare) {
                return $compare;
            }

            return ((int) ($left['id'] ?? 0)) <=> ((int) ($right['id'] ?? 0));
        });

        return $rows;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function developAddonResourceRows(): array
    {
        if (!class_exists(Addon::class) || !app()->bound('addon')) {
            return [];
        }

        $rows = [];
        foreach (Addon::getAddons() as $addonCode => $addonInfo) {
            if (!\is_array($addonInfo)) {
                continue;
            }

            $addonConfig = \is_array($addonInfo['addons'] ?? null) ? (array) $addonInfo['addons'] : $addonInfo;
            if (empty($addonConfig['develop'])) {
                continue;
            }

            $bootstrap = Addon::getAddonBootstrap((string) $addonCode);
            if (null === $bootstrap || !method_exists($bootstrap, 'getAdminResourceDefinitions')) {
                continue;
            }

            $definitions = $bootstrap->getAdminResourceDefinitions((string) $addonCode, $addonConfig);
            if (!\is_array($definitions)) {
                continue;
            }

            foreach ($definitions as $definition) {
                if (!\is_array($definition)) {
                    continue;
                }

                $name = trim((string) ($definition['name'] ?? ''));
                if ('' === $name) {
                    continue;
                }

                $rows[$name] = [
                    'name' => $name,
                    'title' => (string) ($definition['title'] ?? $name),
                    'parent_name' => trim((string) ($definition['parent'] ?? self::TOP_RESOURCE_NAME)) ?: self::TOP_RESOURCE_NAME,
                    'status' => isset($definition['status']) ? (int) $definition['status'] : StatusEnum::ENABLE,
                    'sort' => isset($definition['sort']) ? (int) $definition['sort'] : 0,
                    'module' => isset($definition['module']) && '' !== (string) $definition['module'] ? (string) $definition['module'] : null,
                    'page_key' => isset($definition['page_key']) && '' !== (string) $definition['page_key'] ? (string) $definition['page_key'] : null,
                    'route' => isset($definition['route']) && '' !== (string) $definition['route'] ? (string) $definition['route'] : null,
                    'type' => $this->normalizeMenuType((string) ($definition['type'] ?? MenuTypeEnum::NAV)),
                    'is_nav' => isset($definition['is_nav']) ? (int) $definition['is_nav'] : 0,
                    'icon' => isset($definition['icon']) && '' !== (string) $definition['icon'] ? (string) $definition['icon'] : null,
                ];
            }
        }

        $parentNamesWithMenuChildren = [];
        foreach ($rows as $row) {
            $parentName = (string) ($row['parent_name'] ?? self::TOP_RESOURCE_NAME);
            if (
                self::TOP_RESOURCE_NAME !== $parentName
                && '' !== $parentName
                && MenuTypeEnum::BTN !== (string) ($row['type'] ?? MenuTypeEnum::BTN)
            ) {
                $parentNamesWithMenuChildren[$parentName] = true;
            }
        }

        foreach ($rows as $name => &$row) {
            if (isset($parentNamesWithMenuChildren[$name])) {
                $row['type'] = MenuTypeEnum::DIR;
                $row['module'] = null;
                $row['page_key'] = null;
                $row['route'] = null;
                $row['keep_alive'] = 0;
            }
        }
        unset($row);

        foreach ($rows as $name => $row) {
            if (
                MenuTypeEnum::NAV === (string) ($row['type'] ?? MenuTypeEnum::NAV)
                && null === ($row['page_key'] ?? null)
            ) {
                unset($rows[$name]);
            }
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $resource
     * @param array<string, mixed> $overlay
     *
     * @return array<string, mixed>
     */
    private function applyDevelopResourceOverlay(array $resource, array $overlay, bool $allowPreview): array
    {
        foreach (['title', 'weight', 'module', 'page_key', 'route', 'type', 'is_nav', 'icon', 'redirect', 'hidden', 'keep_alive', 'component'] as $field) {
            if (array_key_exists($field, $overlay)) {
                $resource[$field] = $overlay[$field];
            }
        }

        if ($allowPreview && array_key_exists('parent_name', $overlay)) {
            $resource['parent_name'] = (string) $overlay['parent_name'];
        }

        return $resource;
    }

    /**
     * @param array<int, array<string, mixed>> $resourceMapById
     *
     * @return array<int, int>
     */
    private function resolveOverlayAncestorIds(int $parentId, array $resourceMapById): array
    {
        $ancestorIds = [];
        $visited = [];
        while ($parentId !== 0 && isset($resourceMapById[$parentId]) && !isset($visited[$parentId])) {
            $visited[$parentId] = true;
            array_unshift($ancestorIds, $parentId);
            $parentId = (int) ($resourceMapById[$parentId]['parent_id'] ?? 0);
        }

        return $ancestorIds;
    }

    private function normalizeMenuType(string $type): string
    {
        return \in_array($type, [MenuTypeEnum::DIR, MenuTypeEnum::NAV, MenuTypeEnum::BTN, MenuTypeEnum::LINK], true)
            ? $type
            : MenuTypeEnum::NAV;
    }
    
    public static function addonInstallMenu($addonInfo, $menu, $parentName = null): void
    {
        $addonInfo = (array) $addonInfo;
        $addonCode = (string) ($addonInfo['code'] ?? '');
        if ('' === $addonCode) {
            return;
        }

        $module = isset($addonInfo['module']) && '' !== (string) $addonInfo['module']
            ? (string) $addonInfo['module']
            : $addonCode;
        $definitions = array();

        if (null === $parentName || '' === (string) $parentName) {
            $definitions[] = array(
                'name' => $addonCode,
                'title' => (string) ($addonInfo['title'] ?? $addonCode),
                'type' => MenuTypeEnum::DIR,
                'module' => $module,
                'page_key' => null,
                'addon_code' => $addonCode,
                'is_nav' => 1,
                'status' => 1,
                'sort' => 0,
                'meta_json' => array(
                    'note' => (string) ($addonInfo['description'] ?? ''),
                    'controller' => '',
                ),
            );
            $parentName = $addonCode;
        }

        $definitions = array_merge(
            $definitions,
            self::normalizeAddonMenuDefinitions($addonCode, (array) $menu, (string) $parentName, $module)
        );

        app(AdminResourceServiceInterface::class)->syncAddonResources($addonCode, $definitions);
    }

    public function installChildMenu($addonInfo, $menu, $parentId): void
    {
        foreach ($menu as $item) {
            $resource = $this->adminResourceService->create([
                'name' => $addonInfo['code'].'.'.$item['name'],
                'title' => $item['title'],
                'module' => (string) ($item['module'] ?? $addonInfo['code']),
                'page_key' => $item['page_key'] ?? null,
                'addon_code' => $addonInfo['code'],
                'route' => $item['route'] ?? '',
                'parent_id' => (int) $parentId,
                'type' => $item['type'] ?? MenuTypeEnum::NAV,
                'is_nav' => (int) ($item['is_nav'] ?? 1),
                'icon' => $item['icon'] ?? '',
                'status' => 1,
                'sort' => isset($item['weight']) ? (int) $item['weight'] : 0,
                'note' => $item['note'] ?? '',
                'controller' => $item['controller'] ?? '',
            ]);

            if (isset($item['children']) && $item['children']) {
                $this->installChildMenu($addonInfo, $item['children'], $resource->id);
            }
        }
    }

    public function installParentMenu($addonInfo): AdminResource
    {
        $parent = $this->adminResourceService->findByName((string) $addonInfo['code']);
        $code = $addonInfo['code'];
        if (null !== $parent) {
            $code = $addonInfo['code'].'_'.Str::random(6);
        }

        return $this->adminResourceService->create([
            'name' => $code,
            'title' => $addonInfo['title'],
            'module' => (string) ($addonInfo['module'] ?? $addonInfo['code']),
            'addon_code' => $addonInfo['code'],
            'type' => MenuTypeEnum::DIR,
            'is_nav' => 1,
            'status' => 1,
            'sort' => 0,
            'note' => $addonInfo['description'] ?? '',
        ]);
    }

    public static function addonUninstallMenu($addonName): void
    {
        app(AdminResourceServiceInterface::class)->deleteByAddonCode((string) $addonName);
    }

    /**
     * @param array<int, array<string, mixed>> $menu
     *
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeAddonMenuDefinitions(string $addonCode, array $menu, string $parentCode, string $module): array
    {
        $definitions = array();

        foreach ($menu as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? $item['code'] ?? ''));
            if ('' === $name) {
                continue;
            }

            $resourceName = isset($item['code']) && '' !== trim((string) $item['code'])
                ? trim((string) $item['code'])
                : $addonCode.'.'.$name;

            $definitions[] = array(
                'name' => $resourceName,
                'title' => (string) ($item['title'] ?? $name),
                'type' => (string) ($item['type'] ?? MenuTypeEnum::NAV),
                'module' => (string) ($item['module'] ?? $module),
                'page_key' => $item['page_key'] ?? null,
                'addon_code' => $addonCode,
                'parent' => $parentCode,
                'path' => $item['path'] ?? null,
                'route' => $item['route'] ?? null,
                'icon' => $item['icon'] ?? null,
                'is_nav' => isset($item['is_nav']) ? (int) $item['is_nav'] : 1,
                'status' => isset($item['status']) ? (int) $item['status'] : 1,
                'sort' => isset($item['sort']) ? (int) $item['sort'] : 0,
                'meta_json' => array(
                    'note' => (string) ($item['note'] ?? ''),
                    'controller' => (string) ($item['controller'] ?? ''),
                ),
            );

            if (isset($item['children']) && \is_array($item['children']) && [] !== $item['children']) {
                $definitions = array_merge(
                    $definitions,
                    self::normalizeAddonMenuDefinitions($addonCode, $item['children'], $resourceName, $module)
                );
            }
        }

        return $definitions;
    }

    public function buildGrantPayloadsFromResourceIds(array $resourceIds): array
    {
        $resourceIds = array_values(array_unique(array_map('intval', $resourceIds)));
        if ([] === $resourceIds) {
            return [];
        }

        $names = AdminResource::query()
            ->whereIn('id', $resourceIds)
            ->pluck('name')
            ->filter()
            ->values()->all();

        return array_map(static function (string $name): GrantPayload {
            return new GrantPayload($name, GrantEffect::ALLOW, [Ability::ACCESS]);
        }, $names);
    }

    public function resolveResourceIdsFromResourceCodes(array $resourceCodes): array
    {
        $resourceCodes = array_values(array_unique(array_filter(array_map('strval', $resourceCodes))));
        if ([] === $resourceCodes) {
            return [];
        }

        return AdminResource::query()
            ->whereIn('name', $resourceCodes)
            ->pluck('id')
            ->map(static function ($id): int {
                return (int) $id;
            })
            ->values()->all();
    }

    private function markCheckedResources(array $resources, array $checkedMap, array &$checked): array
    {
        foreach ($resources as &$resource) {
            $resource['checked'] = isset($checkedMap[(int) $resource['id']]);
            if ($resource['checked'] && !\in_array((int) $resource['id'], $checked, true)) {
                $checked[] = (int) $resource['id'];
            }
            if (!empty($resource['children'])) {
                $resource['children'] = $this->markCheckedResources((array) $resource['children'], $checkedMap, $checked);
            }
        }
        unset($resource);

        return $resources;
    }

    private function resolveAncestorIds(int $parentId, array $resourceMapById): array
    {
        $ancestorIds = [];
        while ($parentId > 0 && isset($resourceMapById[$parentId])) {
            array_unshift($ancestorIds, $parentId);
            $parentId = (int) $resourceMapById[$parentId]->parent_id;
        }

        return $ancestorIds;
    }

    private function resolveAncestorCodes(int $parentId, array $resourceMapById): array
    {
        $ancestorCodes = [];
        while ($parentId > 0 && isset($resourceMapById[$parentId])) {
            array_unshift($ancestorCodes, (string) $resourceMapById[$parentId]->name);
            $parentId = (int) $resourceMapById[$parentId]->parent_id;
        }

        return $ancestorCodes;
    }

    private function normalizeResourcePayload(array $data, bool $withDefaults = true): array
    {
        $payload = [];

        if ($withDefaults || \array_key_exists('name', $data)) {
            $payload['name'] = (string) ($data['name'] ?? '');
        }

        if ($withDefaults || \array_key_exists('title', $data)) {
            $payload['title'] = (string) ($data['title'] ?? '');
        }

        if ($withDefaults || \array_key_exists('type', $data)) {
            $payload['type'] = $this->normalizeMenuType((string) ($data['type'] ?? MenuTypeEnum::NAV));
        }

        if ($withDefaults || \array_key_exists('module', $data)) {
            $payload['module'] = isset($data['module']) ? trim((string) $data['module']) : '';
        }

        if ($withDefaults || \array_key_exists('page_key', $data)) {
            $payload['page_key'] = isset($data['page_key']) ? trim((string) $data['page_key']) : null;
        }

        if ($withDefaults || \array_key_exists('route', $data)) {
            $payload['route'] = $data['route'] ?? null;
        }

        if ($withDefaults || \array_key_exists('icon', $data)) {
            $payload['icon'] = $data['icon'] ?? null;
        }

        if ($withDefaults || \array_key_exists('sort', $data)) {
            $payload['sort'] = isset($data['sort']) ? (int) $data['sort'] : 0;
        }

        if ($withDefaults || \array_key_exists('is_nav', $data)) {
            $payload['is_nav'] = isset($data['is_nav']) ? (int) $data['is_nav'] : 0;
        }

        if ($withDefaults || \array_key_exists('status', $data)) {
            $payload['status'] = isset($data['status']) ? (int) $data['status'] : 1;
        }

        $parentId = $this->resolveRequestParentId($data, $withDefaults);
        if (null !== $parentId) {
            $payload['parent_id'] = $parentId;
        }
        if (\array_key_exists('meta_json', $data)) {
            $payload['meta_json'] = $data['meta_json'];
        }
        
        if (!$withDefaults) {
            return array_filter($payload, static function ($value): bool {
                return null !== $value;
            });
        }
        
        return $payload;
    }

    private function resolveRequestParentId(array $data, bool $withDefaults): ?int
    {
        if (\array_key_exists('parent_id', $data)) {
            return (int) $data['parent_id'];
        }

        if (\array_key_exists('parent_ids', $data) && \is_array($data['parent_ids'])) {
            return (int) (end($data['parent_ids']) ?: 0);
        }

        return $withDefaults ? 0 : null;
    }

    private function buildResourceMetaPayload(array $data, bool $withDefaults): ?array
    {
        if (!$withDefaults && !\array_key_exists('hidden', $data) && !\array_key_exists('keep_alive', $data)) {
            return null;
        }

        return array_filter([
            'hidden' => isset($data['hidden']) ? (int) $data['hidden'] : ($withDefaults ? 0 : null),
            'keep_alive' => isset($data['keep_alive'])
                ? (int) $data['keep_alive']
                : ($withDefaults ? $this->defaultKeepAliveForMenuType($this->normalizeMenuType((string) ($data['type'] ?? MenuTypeEnum::NAV))) : null),
        ], static function ($value): bool {
            return null !== $value;
        });
    }

    private function mapResourceDetail(AdminResource $resource): array
    {
        $resources = AdminResource::query()
            ->whereNull('deleted_at')->get();

        $resourceMapById = [];
        $resourceNameById = [];
        foreach ($resources as $item) {
            $resourceMapById[(int) $item->id] = $item;
            $resourceNameById[(int) $item->id] = (string) $item->name;
        }
        $data = $resource->toArray();
        $data['parent_name'] = $resource->parent_id > 0 && isset($resourceNameById[(int) $resource->parent_id])
            ? $resourceNameById[(int) $resource->parent_id]
            : self::TOP_RESOURCE_NAME;
        $data['parent_ids'] = $this->resolveAncestorIds((int) $resource->parent_id, $resourceMapById);
        $data['paths'] = $this->resolveAncestorCodes((int) $resource->parent_id, $resourceMapById);
        
        return $this->mergeMetaJsonIntoResourceData($data);
    }


    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function mergeMetaJsonIntoResourceData(array $data): array
    {
        $metaJson = $data['meta_json'] ?? null;
        if (!\is_array($metaJson)) {
            return $data;
        }

        foreach ($metaJson as $key => $value) {
            if (!\is_string($key) || array_key_exists($key, $data)) {
                continue;
            }

            $data[$key] = $value;
        }

        return $data;
    }


    private function defaultKeepAliveForMenuType(string $type): int
    {
        return (int)($type === MenuTypeEnum::NAV );
    }
}
