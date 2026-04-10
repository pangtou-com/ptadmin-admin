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
use PTAdmin\Support\Enums\MenuTypeEnum;
use PTAdmin\Support\Enums\StatusEnum;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Models\System;
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
        foreach (array_values(array_unique(array_map('intval', $ids))) as $id) {
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

    public function getSystemResourceAssignment(int $systemId): array
    {
        /** @var System $system */
        $system = System::query()->findOrFail($systemId);
        $resourceIds = $this->resolveResourceIdsFromResourceCodes(
            array_column($this->adminGrantService->getUserDirectGrants((int) $system->id), 'resource_code')
        );

        return [
            'results' => $this->resourceTree(),
            'detail' => [
                'id' => $system->id,
                'title' => $system->nickname,
                'origin_id' => $system->origin_id,
                'department_id' => $system->department_id,
                'scope' => $system->scope,
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

    public function syncSystemResourceAssignment(int $systemId, array $resourceIds): void
    {
        DB::transaction(function () use ($systemId, $resourceIds): void {
            /** @var System $system */
            $system = System::query()->findOrFail($systemId);
            $this->adminGrantService->syncUserGrants((int) $system->id, $this->buildGrantPayloadsFromResourceIds($resourceIds));
        });
    }

    public function myResources($member): array
    {
        $results = $this->bySystemIdResources($member->id);

        return infinite_tree($results);
    }

    public function bySystemIdResources($systemId): array
    {
        /** @var System $system */
        $system = System::query()->findOrFail($systemId);
        if (1 === $system->is_founder) {
            return $this->resourceRows(['status' => StatusEnum::ENABLE]);
        }

        $resources = $this->resourceRows(['status' => StatusEnum::ENABLE]);
        $resourceMap = [];
        foreach ($resources as $resource) {
            $resourceMap[(string) $resource['name']] = $resource;
        }

        $visibleCodes = app(AuthorizationServiceInterface::class)->visibleResources(
            $system,
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

    public function adminResourceNav($data, int $parent = 0): string
    {
        $html = [];
        foreach ($data as $key => $datum) {
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
                $children = $this->adminResourceNav($datum['children'], $datum['id']);
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
        $res = [['label' => '顶级栏目', 'value' => self::TOP_RESOURCE_NAME]];
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
        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        $resources = $query
            ->orderByDesc('sort')
            ->orderBy('id')
            ->get()
        ;

        $resourceMapById = [];
        $resourceCodeById = [];
        foreach ($resources as $resource) {
            $resourceMapById[(int) $resource->id] = $resource;
            $resourceCodeById[(int) $resource->id] = (string) $resource->code;
        }

        return $resources->map(function (AdminResource $resource) use ($resourceMapById, $resourceCodeById): array {
            return [
                'id' => (int) $resource->id,
                'parent_id' => (int) $resource->parent_id,
                'parent_name' => isset($resourceCodeById[(int) $resource->parent_id])
                    ? $resourceCodeById[(int) $resource->parent_id]
                    : self::TOP_RESOURCE_NAME,
                'parent_ids' => $this->resolveAncestorIds((int) $resource->parent_id, $resourceMapById),
                'title' => (string) $resource->name,
                'name' => (string) $resource->code,
                'status' => (int) $resource->status,
                'route' => $resource->route,
                'component' => $resource->component,
                'weight' => (int) $resource->sort,
                'type' => $this->mapMenuType((string) $resource->type),
                'is_nav' => (int) $resource->is_nav,
                'icon' => $resource->icon,
            ];
        })->values()->all();
    }

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
        foreach ($data as $datum) {
            if (app(AuthorizationServiceInterface::class)->allows($system, Ability::ACCESS, (string) $datum['name'])) {
                $results[] = $datum;
            }
        }

        return $results;
    }

    public function getDefaultQuickNav($systemId)
    {
        $results = $this->bySystemIdResources($systemId);
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

    public function setQuickNav($systemId, $data): void
    {
        $key = 'quick_nav_'.$systemId;
        if (0 === \count($data)) {
            Cache::forget($key);

            return;
        }
        $resourceMap = AdminResource::query()
            ->whereIn('id', $data)
            ->where('status', StatusEnum::ENABLE)
            ->whereNull('deleted_at')
            ->where('type', ResourceType::PAGE)
            ->orderByDesc('sort')
            ->orderBy('id')
            ->get()
        ;

        $resources = AdminResource::query()
            ->whereNull('deleted_at')
            ->get()
        ;

        $resourceMapById = [];
        $resourceCodeById = [];
        foreach ($resources as $resource) {
            $resourceMapById[(int) $resource->id] = $resource;
            $resourceCodeById[(int) $resource->id] = (string) $resource->code;
        }

        $results = $resourceMap->map(function (AdminResource $resource) use ($resourceMapById, $resourceCodeById): array {
            return [
                'id' => (int) $resource->id,
                'parent_name' => isset($resourceCodeById[(int) $resource->parent_id])
                    ? $resourceCodeById[(int) $resource->parent_id]
                    : self::TOP_RESOURCE_NAME,
                'name' => (string) $resource->code,
                'title' => (string) $resource->name,
                'route' => $resource->route,
                'component' => $resource->component,
                'icon' => $resource->icon,
                'type' => $this->mapMenuType((string) $resource->type),
                'status' => (int) $resource->status,
                'is_nav' => (int) $resource->is_nav,
                'parent_ids' => $this->resolveAncestorIds((int) $resource->parent_id, $resourceMapById),
            ];
        })->values()->all();

        Cache::put($key, @json_encode($results));
    }

    public static function addonInstallMenu($addonInfo, $menu, $parentName = null): void
    {
        $parentId = 0;
        if (null !== $parentName) {
            $parent = app(AdminResourceServiceInterface::class)->findByCode((string) $parentName);
            if (null !== $parent) {
                $parentId = (int) $parent->id;
            }
        }
        $instance = app(self::class);
        if ($parentId <= 0) {
            $parent = $instance->installParentMenu($addonInfo);
            $parentId = (int) $parent->id;
        }
        $instance->installChildMenu($addonInfo, $menu, $parentId);
    }

    public function installChildMenu($addonInfo, $menu, $parentId): void
    {
        foreach ($menu as $item) {
            $resource = $this->adminResourceService->create([
                'code' => $addonInfo['code'].'.'.$item['name'],
                'name' => $item['title'],
                'addon_code' => $addonInfo['code'],
                'route' => $item['route'] ?? '',
                'component' => $item['component'] ?? null,
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
        $parent = $this->adminResourceService->findByCode((string) $addonInfo['code']);
        $code = $addonInfo['code'];
        if (null !== $parent) {
            $code = $addonInfo['code'].'_'.Str::random(6);
        }

        return $this->adminResourceService->create([
            'code' => $code,
            'name' => $addonInfo['title'],
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

    public function buildGrantPayloadsFromResourceIds(array $resourceIds): array
    {
        $resourceIds = array_values(array_unique(array_map('intval', $resourceIds)));
        if ([] === $resourceIds) {
            return [];
        }

        $codes = AdminResource::query()
            ->whereIn('id', $resourceIds)
            ->pluck('code')
            ->filter()
            ->values()
            ->all()
        ;

        return array_map(static function (string $code): GrantPayload {
            return new GrantPayload($code, GrantEffect::ALLOW, [Ability::ACCESS]);
        }, $codes);
    }

    public function resolveResourceIdsFromResourceCodes(array $resourceCodes): array
    {
        $resourceCodes = array_values(array_unique(array_filter(array_map('strval', $resourceCodes))));
        if ([] === $resourceCodes) {
            return [];
        }

        return AdminResource::query()
            ->whereIn('code', $resourceCodes)
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
            throw new \InvalidArgumentException('字段资源不能作为字段权限父节点');
        }

        $baseCode = $this->resolveFieldBaseCode((string) $pageResource->code);
        $existingFieldResources = AdminResource::query()
            ->whereNull('deleted_at')
            ->where('parent_id', $pageResourceId)
            ->where('type', ResourceType::FIELD)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->keyBy(static function (AdminResource $resource): string {
                return (string) $resource->code;
            })
        ;

        $syncedIds = [];
        foreach ($this->normalizeFieldDefinitions($fields, $baseCode, $pageResource) as $definition) {
            /** @var null|AdminResource $existing */
            $existing = $existingFieldResources->get($definition['code']);
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
                    'code' => (string) $resource->code,
                    'name' => (string) $resource->name,
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
            array_unshift($ancestorCodes, (string) $resourceMapById[$parentId]->code);
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
                'code' => $baseCode.'.field.'.$fieldName,
                'name' => isset($field['title']) && '' !== trim((string) $field['title']) ? trim((string) $field['title']) : $fieldName,
                'type' => ResourceType::FIELD,
                'module' => (string) $pageResource->module,
                'addon_code' => $pageResource->addon_code,
                'parent_id' => $pageResource->id,
                'ability_hint_json' => array_values(array_unique((array) ($field['abilities'] ?? [Ability::VIEW]))),
                'meta_json' => [
                    'field_name' => $fieldName,
                    'page_resource_id' => $pageResource->id,
                    'page_resource_code' => (string) $pageResource->code,
                    'note' => isset($field['note']) ? (string) $field['note'] : '',
                ],
                'is_nav' => 0,
                'status' => isset($field['status']) ? (int) $field['status'] : 1,
                'sort' => isset($field['sort']) ? (int) $field['sort'] : (isset($field['weight']) ? (int) $field['weight'] : 0),
            ];
        }

        $uniqueDefinitions = [];
        foreach ($definitions as $definition) {
            $uniqueDefinitions[$definition['code']] = $definition;
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
            $payload['code'] = (string) ($data['name'] ?? '');
        }

        if ($withDefaults || \array_key_exists('title', $data)) {
            $payload['name'] = (string) ($data['title'] ?? '');
        }

        if ($withDefaults || \array_key_exists('type', $data)) {
            $payload['type'] = (string) ($data['type'] ?? MenuTypeEnum::NAV);
        }

        if ($withDefaults || \array_key_exists('route', $data)) {
            $payload['route'] = $data['route'] ?? null;
        }

        if ($withDefaults || \array_key_exists('component', $data)) {
            $payload['component'] = $data['component'] ?? null;
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
        if (!$withDefaults && !\array_key_exists('note', $data) && !\array_key_exists('controller', $data)) {
            return null;
        }

        return [
            'note' => $data['note'] ?? '',
            'controller' => $data['controller'] ?? '',
        ];
    }

    private function mapResourceDetail(AdminResource $resource): array
    {
        $resources = AdminResource::query()
            ->whereNull('deleted_at')
            ->get()
        ;

        $resourceMapById = [];
        $resourceCodeById = [];
        foreach ($resources as $item) {
            $resourceMapById[(int) $item->id] = $item;
            $resourceCodeById[(int) $item->id] = (string) $item->code;
        }

        $metaJson = (array) ($resource->meta_json ?? []);

        return [
            'id' => $resource->id,
            'parent_id' => (int) $resource->parent_id,
            'parent_name' => $resource->parent_id > 0 && isset($resourceCodeById[(int) $resource->parent_id])
                ? $resourceCodeById[(int) $resource->parent_id]
                : self::TOP_RESOURCE_NAME,
            'parent_ids' => $this->resolveAncestorIds((int) $resource->parent_id, $resourceMapById),
            'paths' => $this->resolveAncestorCodes((int) $resource->parent_id, $resourceMapById),
            'title' => (string) $resource->name,
            'name' => (string) $resource->code,
            'route' => $resource->route,
            'component' => $resource->component,
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
                return MenuTypeEnum::BTN;

            case ResourceType::ROUTE:
                return MenuTypeEnum::LINK;

            case ResourceType::PAGE:
            default:
                return MenuTypeEnum::NAV;
        }
    }
}
