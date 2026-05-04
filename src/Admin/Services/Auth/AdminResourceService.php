<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Contracts\Auth\AdminResourceServiceInterface;
use PTAdmin\Support\Enums\MenuTypeEnum;

class AdminResourceService implements AdminResourceServiceInterface
{
    public function create(array $data): AdminResource
    {
        $resource = new AdminResource();
        $payload = $this->normalizePayload($data);
        $resource->fill($payload);
        if (array_key_exists('meta_json', $payload) && null === $payload['meta_json']) {
            $resource->setAttribute('meta_json', null);
        }
        $resource->save();
        $this->flushResourceCaches();

        return $this->refreshHierarchy($resource);
    }

    public function update(int $id, array $data): AdminResource
    {
        /** @var AdminResource $resource */
        $resource = AdminResource::query()->whereNull('deleted_at')->findOrFail($id);
        $payload = $this->normalizePayload($data, false, $resource);
        
        if (isset($payload['parent_id'])) {
            $this->assertParentValid($id, (int) $payload['parent_id']);
        }

        $resource->fill($payload);
        if (array_key_exists('meta_json', $payload) && null === $payload['meta_json']) {
            $resource->setAttribute('meta_json', null);
        }
        $resource->save();
        $this->flushResourceCaches();

        return $this->refreshHierarchy($resource);
    }

    public function delete(int $id): void
    {
        AdminResource::query()->whereNull('deleted_at')->findOrFail($id);

        $ids = array_merge([$id], $this->collectDescendantIds($id));
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $timestamp = time();

        AdminResource::query()
            ->whereIn('id', $ids)
            ->update([
                'status' => 0,
                'deleted_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

        AdminGrant::query()->whereIn('resource_id', $ids)->delete();
        $this->flushResourceCaches();
    }

    public function find(int $id)
    {
        return AdminResource::query()->whereNull('deleted_at')->findOrFail($id);
    }

    public function register(array $definition): AdminResource
    {
        $definition = $this->normalizeDefinition($definition);
        $resource = AdminResource::query()->where('name', $definition['name'])->first();
        if (null === $resource) {
            $resource = new AdminResource();
        }

        $resource->fill($definition);
        $resource->setAttribute('deleted_at', null);
        $resource->save();
        $this->flushResourceCaches();

        return $this->refreshHierarchy($resource);
    }

    public function registerBatch(array $definitions): void
    {
        foreach ($definitions as $definition) {
            $this->register($definition);
        }
    }

    public function findByName(string $name)
    {
        return AdminResource::findByName($name);
    }

    public function tree(array $filters = []): array
    {
        $query = AdminResource::query()->whereNull('deleted_at');
        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        $rows = $query
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->toArray();

        return infinite_tree($rows);
    }

    public function syncAddonResources(string $addonCode, array $definitions): void
    {
        $codes = [];
        foreach ($definitions as $definition) {
            $definition['addon_code'] = $addonCode;
            $resource = $this->register($definition);
            $codes[] = $resource->name;
        }

        AdminResource::query()
            ->where('addon_code', $addonCode)
            ->when(\count($codes) > 0, function ($query) use ($codes): void {
                $query->whereNotIn('name', $codes);
            })->update([
                'status' => 0,
                'updated_at' => time(),
            ]);
        
        $this->flushResourceCaches();
    }

    public function disableAddonResources(string $addonCode): void
    {
        AdminResource::query()
            ->where('addon_code', $addonCode)
            ->whereNull('deleted_at')
            ->update([
                'status' => 0,
                'updated_at' => time(),
            ]);
        $this->flushResourceCaches();
    }

    public function deleteByAddonCode(string $addonCode): void
    {
        $ids = AdminResource::query()
            ->where('addon_code', $addonCode)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(static function ($id): int {
                return (int) $id;
            })
            ->values()->all();

        if ([] === $ids) {
            return;
        }

        $timestamp = time();
        AdminResource::query()
            ->whereIn('id', $ids)
            ->update([
                'status' => 0,
                'deleted_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

        AdminGrant::query()->whereIn('resource_id', $ids)->delete();
        $this->flushResourceCaches();
    }

    private function normalizeDefinition(array $definition): array
    {
        $name = trim((string) ($definition['name'] ?? ''));
        $title = trim((string) ($definition['title'] ?? ''));
        $parent = $definition['parent'] ?? null;
        $parentId = (int) ($definition['parent_id'] ?? 0);
        if (!$parentId && \is_string($parent) && '' !== $parent) {
            $parentId = (int) AdminResource::query()->where('name', $parent)->value('id');
        }

        $type = $definition['type'];

        $payload = [
            'name' => $name,
            'title' => $title,
            'type' => $type,
            'module' => $this->normalizeModuleValue($definition['module'] ?? null, $type, $name),
            'page_key' => $this->normalizePageKeyValue($definition['page_key'] ?? null, $type),
            'addon_code' => $definition['addon_code'] ?? null,
            'parent_id' => $parentId,
            'level' => (int) ($definition['level'] ?? 0),
            'path' => $definition['path'] ?? null,
            'route' => $this->normalizeRouteValue($definition['route'] ?? null, $parentId),
            'icon' => $definition['icon'] ?? null,
            'meta_json' => $this->normalizeMetaJson((array) ($definition['meta'] ?? $definition['meta_json'] ?? array()), $type),
            'is_nav' => (int) ($definition['is_nav'] ?? 0),
            'status' => (int) ($definition['status'] ?? 1),
            'sort' => (int) ($definition['sort'] ?? 0),
        ];

        $this->assertResourceProtocol($payload);

        return $payload;
    }

    private function normalizePayload(array $data, bool $withDefaults = true, ?AdminResource $resource = null): array
    {
        $payload = [];

        $resourceName = null !== $resource ? (string) $resource->name : '';

        if ($withDefaults || array_key_exists('name', $data)) {
            $payload['name'] = (string) ($data['name'] ?? '');
        }

        if ($withDefaults || array_key_exists('title', $data)) {
            $payload['title'] = (string) ($data['title'] ?? '');
        }

        $type = null;
        if ($withDefaults || array_key_exists('type', $data)) {
            $payload['type'] = $data['type'];
        }

        if (array_key_exists('module', $data)) {
            $resolvedType = null !== $type ? $type : (null !== $resource ? (string) $resource->type : MenuTypeEnum::NAV);
            $payload['module'] = $this->normalizeModuleValue(
                $data['module'],
                $resolvedType,
                (string) ($payload['name'] ?? $resourceName)
            );
        } elseif ($withDefaults || isset($payload['name'])) {
            $resolvedType = null !== $type ? $type : (null !== $resource ? (string) $resource->type : MenuTypeEnum::NAV);
            $payload['module'] = $this->normalizeModuleValue(
                null,
                $resolvedType,
                (string) ($payload['name'] ?? $resourceName)
            );
        }

        if ($withDefaults || array_key_exists('page_key', $data)) {
            $resolvedType = null !== $type ? $type : (null !== $resource ? (string) $resource->type : MenuTypeEnum::NAV);
            $payload['page_key'] = $this->normalizePageKeyValue($data['page_key'] ?? null, $resolvedType);
        }

        if ($withDefaults || array_key_exists('addon_code', $data)) {
            $payload['addon_code'] = $data['addon_code'] ?? null;
        }

        $parentId = $this->resolveParentId($data, $resource, $withDefaults);
        if (null !== $parentId) {
            $payload['parent_id'] = $parentId;
        }

        if ($withDefaults || array_key_exists('route', $data)) {
            $payload['route'] = $this->normalizeRouteValue($data['route'] ?? null, $parentId ?? (null !== $resource ? (int) $resource->parent_id : 0));
        }

        if ($withDefaults || array_key_exists('icon', $data)) {
            $payload['icon'] = $data['icon'] ?? null;
        }

        $metaJson = $this->resolveMetaJson($data, $type, $resource, $withDefaults);
        if (null !== $metaJson) {
            $payload['meta_json'] = $metaJson;
        }

        if ($withDefaults || array_key_exists('is_nav', $data)) {
            $payload['is_nav'] = isset($data['is_nav']) ? (int) $data['is_nav'] : 0;
        }

        if ($withDefaults || array_key_exists('status', $data)) {
            $payload['status'] = isset($data['status']) ? (int) $data['status'] : 1;
        }

        if ($withDefaults || array_key_exists('sort', $data)) {
            $payload['sort'] = isset($data['sort']) ? (int) $data['sort'] : 0;
        }

        if (!$withDefaults) {
            $payload = array_filter($payload, static function ($value): bool {
                return null !== $value;
            });

            $this->assertResourceProtocol(array_merge(
                [
                    'type' => null !== $resource ? (string) $resource->type : MenuTypeEnum::NAV,
                    'title' => null !== $resource ? (string) $resource->title : '',
                    'module' => null !== $resource ? (string) $resource->module : '',
                    'page_key' => null !== $resource ? $resource->page_key : null,
                    'route' => null !== $resource ? $resource->route : null,
                ],
                $payload
            ));

            return $payload;
        }

        $this->assertResourceProtocol($payload);

        return $payload;
    }

    private function resolveParentId(array $data, ?AdminResource $resource, bool $withDefaults): ?int
    {
        if (array_key_exists('parent_id', $data)) {
            return (int) $data['parent_id'];
        }

        if (array_key_exists('parent_ids', $data) && \is_array($data['parent_ids'])) {
            return (int) (end($data['parent_ids']) ?: 0);
        }

        if (array_key_exists('parent', $data) && \is_string($data['parent']) && '' !== $data['parent']) {
            return (int) AdminResource::query()
                ->whereNull('deleted_at')
                ->where('name', $data['parent'])
                ->value('id');
        }

        if ($withDefaults) {
            return 0;
        }

        return $resource ? (int) $resource->parent_id : null;
    }

    private function resolveMetaJson(array $data, ?string $type, ?AdminResource $resource, bool $withDefaults): ?array
    {
        $resolvedType = null !== $type ? $type : (null !== $resource ? (string) $resource->type : MenuTypeEnum::NAV);

        if (array_key_exists('meta_json', $data)) {
            if (null === $data['meta_json']) {
                return null;
            }

            return $this->normalizeMetaJson((array) $data['meta_json'], $resolvedType);
        }

        if (!array_key_exists('hidden', $data) && !array_key_exists('keep_alive', $data)) {
            return null;
        }

        $metaJson = $resource ? (array) ($resource->meta_json ?? []) : [];

        if (array_key_exists('hidden', $data)) {
            $metaJson['hidden'] = isset($data['hidden']) ? (int) $data['hidden'] : 0;
        }

        if (array_key_exists('keep_alive', $data)) {
            $metaJson['keep_alive'] = isset($data['keep_alive'])
                ? (int) $data['keep_alive']
                : 0;
        }

        return $this->normalizeMetaJson($metaJson, $resolvedType);
    }

    private function resolveModule(string $name): string
    {
        $segments = explode('.', $name);

        return $segments[0] ?? 'system';
    }

    private function normalizeModuleValue($module, string $type, string $name): string
    {
        if (MenuTypeEnum::DIR === $type) {
            return '';
        }

        $module = trim((string) $module);
        if ('' !== $module) {
            return $module;
        }

        if (\in_array($type, [MenuTypeEnum::NAV, MenuTypeEnum::LINK, MenuTypeEnum::BTN], true)) {
            return $this->resolveModule($name);
        }

        return '';
    }

    private function normalizePageKeyValue($pageKey, string $type): ?string
    {
        if (!\in_array($type, [MenuTypeEnum::NAV, MenuTypeEnum::LINK], true)) {
            return null;
        }

        $pageKey = trim((string) $pageKey);

        return '' === $pageKey ? null : $pageKey;
    }

    private function normalizeRouteValue($route, ?int $parentId = null): ?string
    {
        $route = trim((string) $route);
        if ('' === $route) {
            return null;
        }

        if (Str::startsWith($route, ['http://', 'https://'])) {
            return $route;
        }

        $route = preg_replace('#/+#', '/', $route) ?: $route;
        if (0 === (int) $parentId && !Str::startsWith($route, '/')) {
            return '/'.$route;
        }

        return $route;
    }

    /**
     * @param array<string, mixed> $metaJson
     *
     * @return array<string, mixed>
     */
    private function normalizeMetaJson(array $metaJson, string $type): array
    {
        if (array_key_exists('hidden', $metaJson)) {
            $metaJson['hidden'] = (int) $metaJson['hidden'];
        }

        if (array_key_exists('keep_alive', $metaJson)) {
            $metaJson['keep_alive'] = (int) $metaJson['keep_alive'];
        }

        return $metaJson;
    }

    private function resolveDefaultKeepAlive(string $type): int
    {
        return \in_array($type, [MenuTypeEnum::NAV, MenuTypeEnum::LINK], true) ? 1 : 0;
    }

    /**
     * 统一校验资源树协议的最小约束，避免前端需要兜底推断。
     *
     * @param array<string, mixed> $payload
     */
    private function assertResourceProtocol(array $payload): void
    {
        $type = (string) ($payload['type'] ?? '');
        $name = trim((string) ($payload['name'] ?? ''));
        $title = trim((string) ($payload['title'] ?? ''));
        $module = trim((string) ($payload['module'] ?? ''));
        $pageKey = isset($payload['page_key']) ? trim((string) $payload['page_key']) : '';
        $route = isset($payload['route']) ? trim((string) $payload['route']) : '';

        if ('' === $name) {
            throw new BackgroundException(__('ptadmin::background.resource_code_required'));
        }

        if ('' === $title) {
            throw new BackgroundException(__('ptadmin::background.resource_title_required'));
        }

        if (!\in_array($type, [MenuTypeEnum::DIR, MenuTypeEnum::NAV, MenuTypeEnum::BTN, MenuTypeEnum::LINK], true)) {
            throw new BackgroundException(__('ptadmin::background.resource_type_invalid'));
        }

        if ($type === MenuTypeEnum::NAV && ('' === $module || '' === $pageKey || '' === $route)) {
            throw new BackgroundException(__('ptadmin::background.resource_nav_invalid'));
        }

        if (MenuTypeEnum::BTN === $type && '' === $module) {
            throw new BackgroundException(__('ptadmin::background.resource_button_invalid'));
        }
    }

    private function refreshHierarchy(AdminResource $resource): AdminResource
    {
        $this->syncHierarchy($resource);
        $this->refreshChildrenHierarchy((int) $resource->id);

        return $resource->refresh();
    }

    private function syncHierarchy(AdminResource $resource): void
    {
        [$level, $path] = $this->resolveHierarchy((int) $resource->parent_id);
        $resource->setAttribute('level', $level);
        $resource->setAttribute('path', $path);
        $resource->save();
    }

    private function resolveHierarchy(int $parentId): array
    {
        $names = [];
        while ($parentId > 0) {
            /** @var null|AdminResource $parent */
            $parent = AdminResource::query()->whereNull('deleted_at')->find($parentId);
            if (null === $parent) {
                break;
            }

            array_unshift($names, (string) $parent->name);
            $parentId = (int) $parent->parent_id;
        }

        return [\count($names), [] === $names ? null : implode('.', $names)];
    }

    private function refreshChildrenHierarchy(int $parentId): void
    {
        AdminResource::query()
            ->whereNull('deleted_at')
            ->where('parent_id', $parentId)
            ->get()
            ->each(function (AdminResource $resource): void {
                $this->syncHierarchy($resource);
                $this->refreshChildrenHierarchy((int) $resource->id);
            });
    }

    private function assertParentValid(int $id, int $parentId): void
    {
        if ($parentId === $id) {
            throw new BackgroundException(__('ptadmin::background.parent_menu_self'));
        }

        if ($parentId <= 0) {
            return;
        }

        AdminResource::query()->whereNull('deleted_at')->findOrFail($parentId);

        if (\in_array($parentId, $this->collectDescendantIds($id), true)) {
            throw new BackgroundException(__('ptadmin::background.parent_menu_child'));
        }
    }

    private function collectDescendantIds(int $id): array
    {
        $childrenMap = [];
        AdminResource::query()
            ->whereNull('deleted_at')
            ->get(['id', 'parent_id'])
            ->each(function (AdminResource $resource) use (&$childrenMap): void {
                $childrenMap[$resource->parent_id][] = $resource->id;
            });
        $ids = [];
        $stack = $childrenMap[$id] ?? [];
        while ([] !== $stack) {
            $childId = array_pop($stack);
            $ids[] = $childId;
            foreach ($childrenMap[$childId] ?? [] as $grandChildId) {
                $stack[] =  $grandChildId;
            }
        }

        return array_values(array_unique($ids));
    }

    private function flushResourceCaches(): void
    {
        Cache::forget(AdminResource::AUDIT_META_CACHE_KEY);
    }
}
