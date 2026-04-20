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
use PTAdmin\Support\Enums\ResourceType;
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

        return infinite_tree($results);
    }

    public function byAdminIdResources($adminId): array
    {
        /** @var Admin $admin */
        $admin = Admin::query()->findOrFail($adminId);
        if (1 === $admin->is_founder) {
            return $this->resourceRows(['status' => StatusEnum::ENABLE]);
        }

        $resources = $this->resourceRows(['status' => StatusEnum::ENABLE]);
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

        return array_values($results);
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
        return infinite_tree(
            $this->resourceRows($filters),
            self::TOP_RESOURCE_NAME,
            'parent_name',
            'name'
        );
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

        $resources = $query
            ->get()
        ;

        $resourceMapById = [];
        $resourceNameById = [];
        foreach ($resources as $resource) {
            $resourceMapById[(int) $resource->id] = $resource;
            $resourceNameById[(int) $resource->id] = (string) $resource->name;
        }

        return $resources->map(function (AdminResource $resource) use ($resourceMapById, $resourceNameById): array {
            return [
                'id' => (int) $resource->id,
                'parent_id' => (int) $resource->parent_id,
                'parent_name' => $resourceNameById[(int)$resource->parent_id] ?? self::TOP_RESOURCE_NAME,
                'parent_ids' => $this->resolveAncestorIds((int) $resource->parent_id, $resourceMapById),
                'title' => (string) $resource->title,
                'name' => (string) $resource->name,
                'status' => (int) $resource->status,
                'weight' => (int) $resource->sort,
                'module' => '' === (string) $resource->module ? null : (string) $resource->module,
                'page_key' => null === $resource->page_key ? null : (string) $resource->page_key,
                'route' => $resource->route,
                'type' => $this->mapMenuType((string) $resource->type),
                'is_nav' => (int) $resource->is_nav,
                'icon' => $resource->icon,
                'redirect' => $this->resolveMetaString($resource, 'redirect'),
                'hidden' => $this->resolveMetaInt($resource, 'hidden', 0),
                'keep_alive' => $this->resolveKeepAlive($resource),
                'component' => null,
            ];
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

    public function getQuickNav($adminId): array
    {
        $key = 'quick_nav_'.$adminId;
        if (!Cache::has($key)) {
            return [];
        }
        $data = @json_decode(Cache::get($key), true);
        $results = [];

        /** @var Admin $admin */
        $admin = Admin::query()->findOrFail($adminId);
        if (1 === $admin->is_founder) {
            return $data;
        }
        foreach ($data as $datum) {
            if (app(AuthorizationServiceInterface::class)->allows($admin, Ability::ACCESS, (string) $datum['name'])) {
                $results[] = $datum;
            }
        }

        return $results;
    }

    public function getDefaultQuickNav($adminId)
    {
        $results = $this->byAdminIdResources($adminId);
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

    public function setQuickNav($adminId, $data): void
    {
        $key = 'quick_nav_'.$adminId;
        if (0 === \count($data)) {
            Cache::forget($key);

            return;
        }
        $resourceMap = AdminResource::query()
            ->whereIn('id', $data)
            ->where('status', StatusEnum::ENABLE)
            ->whereNull('deleted_at')
            ->where('type', ResourceType::PAGE)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
        ;

        $resources = AdminResource::query()
            ->whereNull('deleted_at')
            ->get()
        ;

        $resourceMapById = [];
        $resourceNameById = [];
        foreach ($resources as $resource) {
            $resourceMapById[(int) $resource->id] = $resource;
            $resourceNameById[(int) $resource->id] = (string) $resource->name;
        }

        $results = $resourceMap->map(function (AdminResource $resource) use ($resourceMapById, $resourceNameById): array {
            return [
                'id' => (int) $resource->id,
                'parent_name' => isset($resourceNameById[(int) $resource->parent_id])
                    ? $resourceNameById[(int) $resource->parent_id]
                    : self::TOP_RESOURCE_NAME,
                'name' => (string) $resource->name,
                'title' => (string) $resource->title,
                'module' => '' === (string) $resource->module ? null : (string) $resource->module,
                'page_key' => null === $resource->page_key ? null : (string) $resource->page_key,
                'route' => $resource->route,
                'icon' => $resource->icon,
                'type' => $this->mapMenuType((string) $resource->type),
                'status' => (int) $resource->status,
                'is_nav' => (int) $resource->is_nav,
                'parent_ids' => $this->resolveAncestorIds((int) $resource->parent_id, $resourceMapById),
                'redirect' => $this->resolveMetaString($resource, 'redirect'),
                'hidden' => $this->resolveMetaInt($resource, 'hidden', 0),
                'keep_alive' => $this->resolveKeepAlive($resource),
                'component' => null,
            ];
        })->values()->all();

        Cache::put($key, @json_encode($results));
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
                'page_key' => $this->resolvePageKeyFromMenuItem((array) $item),
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
                'page_key' => self::resolveStaticPageKeyFromMenuItem($item),
                'addon_code' => $addonCode,
                'parent' => $parentCode,
                'path' => $item['path'] ?? null,
                'route' => $item['route'] ?? null,
                'icon' => $item['icon'] ?? null,
                'is_nav' => isset($item['is_nav']) ? (int) $item['is_nav'] : 1,
                'status' => isset($item['status']) ? (int) $item['status'] : 1,
                'sort' => isset($item['weight']) ? (int) $item['weight'] : 0,
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
            ->values()
            ->all()
        ;

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
            ->values()
            ->all()
        ;
    }

    public function syncFieldResources(int $pageResourceId, array $fields): array
    {
        /** @var AdminResource $pageResource */
        $pageResource = $this->adminResourceService->find($pageResourceId);
        if (ResourceType::FIELD === (string) $pageResource->type) {
            throw new \InvalidArgumentException(__('ptadmin::background.field_parent_invalid'));
        }

        $baseCode = $this->resolveFieldBaseCode((string) $pageResource->name);
        $existingFieldResources = AdminResource::query()
            ->whereNull('deleted_at')
            ->where('parent_id', $pageResourceId)
            ->where('type', ResourceType::FIELD)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->keyBy(static function (AdminResource $resource): string {
                return (string) $resource->name;
            })
        ;

        $syncedIds = [];
        foreach ($this->normalizeFieldDefinitions($fields, $baseCode, $pageResource) as $definition) {
            /** @var null|AdminResource $existing */
            $existing = $existingFieldResources->get($definition['name']);
            if (null !== $existing) {
                $resource = $this->adminResourceService->update((int) $existing->id, $definition);
            } else {
                $resource = $this->adminResourceService->create($definition);
            }

            $syncedIds[] = (int) $resource->id;
        }

        $existingFieldResources
            ->filter(static function (AdminResource $resource) use ($syncedIds): bool {
                return !\in_array((int) $resource->id, $syncedIds, true);
            })
            ->each(function (AdminResource $resource): void {
                $this->adminResourceService->delete((int) $resource->id);
            })
        ;

        return AdminResource::query()
            ->whereNull('deleted_at')
            ->where('parent_id', $pageResourceId)
            ->where('type', ResourceType::FIELD)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(static function (AdminResource $resource): array {
                $metaJson = (array) ($resource->meta_json ?? []);

                return [
                    'id' => (int) $resource->id,
                    'name' => (string) $resource->name,
                    'title' => (string) $resource->title,
                    'field_name' => (string) ($metaJson['field_name'] ?? ''),
                    'status' => (int) $resource->status,
                    'sort' => (int) $resource->sort,
                    'parent_id' => (int) $resource->parent_id,
                    'type' => (string) $resource->type,
                ];
            })
            ->values()
            ->all()
        ;
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

    private function normalizeFieldDefinitions(array $fields, string $baseCode, AdminResource $pageResource): array
    {
        $definitions = [];

        foreach ($fields as $field) {
            $field = \is_array($field) ? $field : [];
            $fieldName = isset($field['name']) ? trim((string) $field['name']) : '';
            if ('' === $fieldName) {
                continue;
            }

            $definitions[] = [
                'name' => $baseCode.'.field.'.$fieldName,
                'title' => isset($field['title']) && '' !== trim((string) $field['title']) ? trim((string) $field['title']) : $fieldName,
                'type' => ResourceType::FIELD,
                'module' => (string) $pageResource->module,
                'addon_code' => $pageResource->addon_code,
                'parent_id' => $pageResource->id,
                'ability_hint_json' => array_values(array_unique((array) ($field['abilities'] ?? [Ability::VIEW]))),
                'meta_json' => [
                    'field_name' => $fieldName,
                    'page_resource_id' => $pageResource->id,
                    'page_resource_code' => (string) $pageResource->name,
                    'note' => isset($field['note']) ? (string) $field['note'] : '',
                ],
                'is_nav' => 0,
                'status' => isset($field['status']) ? (int) $field['status'] : 1,
                'sort' => isset($field['sort']) ? (int) $field['sort'] : (isset($field['weight']) ? (int) $field['weight'] : 0),
            ];
        }

        $uniqueDefinitions = [];
        foreach ($definitions as $definition) {
            $uniqueDefinitions[$definition['name']] = $definition;
        }

        return array_values($uniqueDefinitions);
    }

    private function resolveFieldBaseCode(string $pageResourceCode): string
    {
        return Str::endsWith($pageResourceCode, '.page')
            ? substr($pageResourceCode, 0, -5)
            : $pageResourceCode;
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
            $payload['type'] = (string) ($data['type'] ?? MenuTypeEnum::NAV);
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

        if ($withDefaults || \array_key_exists('weight', $data)) {
            $payload['sort'] = isset($data['weight']) ? (int) $data['weight'] : 0;
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

        $metaJson = $this->buildResourceMetaPayload($data, $withDefaults);
        if (null !== $metaJson) {
            $payload['meta_json'] = $metaJson;
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
        if (
            !$withDefaults
            && !\array_key_exists('note', $data)
            && !\array_key_exists('controller', $data)
            && !\array_key_exists('redirect', $data)
            && !\array_key_exists('hidden', $data)
            && !\array_key_exists('keep_alive', $data)
        ) {
            return null;
        }

        return array_filter([
            'note' => $data['note'] ?? '',
            'controller' => $data['controller'] ?? '',
            'redirect' => isset($data['redirect']) && '' !== trim((string) $data['redirect']) ? trim((string) $data['redirect']) : null,
            'hidden' => isset($data['hidden']) ? (int) $data['hidden'] : ($withDefaults ? 0 : null),
            'keep_alive' => isset($data['keep_alive'])
                ? (int) $data['keep_alive']
                : ($withDefaults ? $this->defaultKeepAliveForMenuType((string) ($data['type'] ?? MenuTypeEnum::NAV)) : null),
        ], static function ($value): bool {
            return null !== $value;
        });
    }

    private function mapResourceDetail(AdminResource $resource): array
    {
        $resources = AdminResource::query()
            ->whereNull('deleted_at')
            ->get()
        ;

        $resourceMapById = [];
        $resourceNameById = [];
        foreach ($resources as $item) {
            $resourceMapById[(int) $item->id] = $item;
            $resourceNameById[(int) $item->id] = (string) $item->name;
        }

        $metaJson = (array) ($resource->meta_json ?? []);

        return [
            'id' => $resource->id,
            'parent_id' => (int) $resource->parent_id,
            'parent_name' => $resource->parent_id > 0 && isset($resourceNameById[(int) $resource->parent_id])
                ? $resourceNameById[(int) $resource->parent_id]
                : self::TOP_RESOURCE_NAME,
            'parent_ids' => $this->resolveAncestorIds((int) $resource->parent_id, $resourceMapById),
            'paths' => $this->resolveAncestorCodes((int) $resource->parent_id, $resourceMapById),
            'title' => (string) $resource->title,
            'name' => (string) $resource->name,
            'module' => '' === (string) $resource->module ? null : (string) $resource->module,
            'page_key' => null === $resource->page_key ? null : (string) $resource->page_key,
            'route' => $resource->route,
            'redirect' => $this->resolveMetaString($resource, 'redirect'),
            'hidden' => $this->resolveMetaInt($resource, 'hidden', 0),
            'keep_alive' => $this->resolveKeepAlive($resource),
            'component' => null,
            'icon' => $resource->icon,
            'weight' => (int) $resource->sort,
            'note' => (string) ($metaJson['note'] ?? ''),
            'type' => $this->mapMenuType((string) $resource->type),
            'status' => (int) $resource->status,
            'is_nav' => (int) $resource->is_nav,
            'controller' => (string) ($metaJson['controller'] ?? ''),
            'guard_name' => AdminAuth::getGuard(),
            'created_at' => $resource->created_at,
            'updated_at' => $resource->updated_at,
        ];
    }

    private function mapMenuType(string $resourceType): string
    {
        switch ($resourceType) {
            case ResourceType::MENU:
                return MenuTypeEnum::DIR;

            case ResourceType::BUTTON:
            case ResourceType::FIELD:
                return MenuTypeEnum::BTN;

            case ResourceType::ROUTE:
            case ResourceType::PAGE:
            default:
                return MenuTypeEnum::NAV;
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function resolveStaticPageKeyFromMenuItem(array $item): ?string
    {
        $type = (string) ($item['type'] ?? MenuTypeEnum::NAV);
        if (!\in_array($type, [MenuTypeEnum::NAV, MenuTypeEnum::LINK], true)) {
            return null;
        }

        $pageKey = trim((string) ($item['page_key'] ?? $item['name'] ?? ''));

        return '' === $pageKey ? null : $pageKey;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolvePageKeyFromMenuItem(array $item): ?string
    {
        return self::resolveStaticPageKeyFromMenuItem($item);
    }

    private function resolveMetaString(AdminResource $resource, string $key): ?string
    {
        $metaJson = (array) ($resource->meta_json ?? []);
        $value = trim((string) ($metaJson[$key] ?? ''));

        return '' === $value ? null : $value;
    }

    private function resolveMetaInt(AdminResource $resource, string $key, int $default = 0): int
    {
        $metaJson = (array) ($resource->meta_json ?? []);

        return isset($metaJson[$key]) ? (int) $metaJson[$key] : $default;
    }

    private function resolveKeepAlive(AdminResource $resource): int
    {
        $metaJson = (array) ($resource->meta_json ?? []);
        if (isset($metaJson['keep_alive'])) {
            return (int) $metaJson['keep_alive'];
        }

        return $this->defaultKeepAliveForMenuType($this->mapMenuType((string) $resource->type));
    }

    private function defaultKeepAliveForMenuType(string $type): int
    {
        return \in_array($type, [MenuTypeEnum::NAV, MenuTypeEnum::LINK], true) ? 1 : 0;
    }
}
