<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Auth;

use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Contracts\Auth\AdminResourceServiceInterface;
use PTAdmin\Support\Enums\Ability;
use PTAdmin\Support\Enums\ResourceType;

class AdminResourceService implements AdminResourceServiceInterface
{
    public function create(array $data)
    {
        $resource = new AdminResource();
        $resource->fill($this->normalizePayload($data));
        $resource->save();

        return $this->refreshHierarchy($resource);
    }

    public function update(int $id, array $data)
    {
        /** @var AdminResource $resource */
        $resource = AdminResource::query()->whereNull('deleted_at')->findOrFail($id);
        $payload = $this->normalizePayload($data, false, $resource);

        if (isset($payload['parent_id'])) {
            $this->assertParentValid($id, (int) $payload['parent_id']);
        }

        $resource->fill($payload);
        $resource->save();

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
    }

    public function find(int $id)
    {
        return AdminResource::query()->whereNull('deleted_at')->findOrFail($id);
    }

    public function register(array $definition)
    {
        $definition = $this->normalizeDefinition($definition);
        $resource = AdminResource::query()->where('code', $definition['code'])->first();
        if (null === $resource) {
            $resource = new AdminResource();
        }

        $resource->fill($definition);
        $resource->setAttribute('deleted_at', null);
        $resource->save();

        return $this->refreshHierarchy($resource);
    }

    public function registerBatch(array $definitions): void
    {
        foreach ($definitions as $definition) {
            $this->register($definition);
        }
    }

    public function findByCode(string $code)
    {
        return AdminResource::findByCode($code);
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
            $codes[] = $resource->code;
        }

        AdminResource::query()
            ->where('addon_code', $addonCode)
            ->when(\count($codes) > 0, function ($query) use ($codes): void {
                $query->whereNotIn('code', $codes);
            })
            ->update([
                'status' => 0,
                'updated_at' => time(),
            ]);
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
            ->values()
            ->all();

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
    }

    private function normalizeDefinition(array $definition): array
    {
        $parent = $definition['parent'] ?? null;
        $parentId = (int) ($definition['parent_id'] ?? 0);
        if (!$parentId && \is_string($parent) && '' !== $parent) {
            $parentId = (int) AdminResource::query()->where('code', $parent)->value('id');
        }

        $type = $this->mapResourceType((string) ($definition['type'] ?? ResourceType::PAGE));
        $abilities = array_values(array_unique((array) ($definition['abilities'] ?? $definition['ability_hint_json'] ?? array())));
        if ([] === $abilities) {
            $abilities = $this->resolveAbilities($type);
        }

        $payload = [
            'code' => (string) ($definition['code'] ?? ''),
            'name' => (string) ($definition['name'] ?? ''),
            'type' => $type,
            'module' => $this->normalizeModuleValue($definition['module'] ?? $this->resolveModule((string) ($definition['code'] ?? ''))),
            'page_key' => $this->normalizePageKeyValue($definition['page_key'] ?? null, $type),
            'addon_code' => $definition['addon_code'] ?? null,
            'parent_id' => $parentId,
            'level' => (int) ($definition['level'] ?? 0),
            'path' => $definition['path'] ?? null,
            'route' => $this->normalizeRouteValue($definition['route'] ?? null),
            'icon' => $definition['icon'] ?? null,
            'ability_hint_json' => $abilities,
            'meta_json' => $this->normalizeMetaJson((array) ($definition['meta'] ?? $definition['meta_json'] ?? array())),
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

        if ($withDefaults || array_key_exists('code', $data)) {
            $payload['code'] = (string) ($data['code'] ?? '');
        }

        if ($withDefaults || array_key_exists('name', $data)) {
            $payload['name'] = (string) ($data['name'] ?? '');
        }

        $type = null;
        if ($withDefaults || array_key_exists('type', $data)) {
            $type = $this->mapResourceType((string) ($data['type'] ?? ResourceType::PAGE));
            $payload['type'] = $type;
        }

        if (array_key_exists('module', $data)) {
            $payload['module'] = $this->normalizeModuleValue($data['module']);
        } elseif ($withDefaults || isset($payload['code'])) {
            $resourceCode = null !== $resource ? $resource->code : '';
            $payload['module'] = $this->resolveModule((string) ($payload['code'] ?? $resourceCode ?? ''));
        }

        if ($withDefaults || array_key_exists('page_key', $data)) {
            $resolvedType = null !== $type ? $type : (null !== $resource ? (string) $resource->type : ResourceType::PAGE);
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
            $payload['route'] = $this->normalizeRouteValue($data['route'] ?? null);
        }

        if ($withDefaults || array_key_exists('icon', $data)) {
            $payload['icon'] = $data['icon'] ?? null;
        }

        if (array_key_exists('ability_hint_json', $data) || array_key_exists('abilities', $data)) {
            $payload['ability_hint_json'] = array_values(array_unique((array) ($data['abilities'] ?? $data['ability_hint_json'] ?? [])));
        } elseif (null !== $type) {
            $payload['ability_hint_json'] = $this->resolveAbilities($type);
        } elseif ($withDefaults) {
            $payload['ability_hint_json'] = $this->resolveAbilities(ResourceType::PAGE);
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
                    'type' => null !== $resource ? (string) $resource->type : ResourceType::PAGE,
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
                ->where('code', $data['parent'])
                ->value('id');
        }

        if ($withDefaults) {
            return 0;
        }

        return $resource ? (int) $resource->parent_id : null;
    }

    private function resolveMetaJson(array $data, ?string $type, ?AdminResource $resource, bool $withDefaults): ?array
    {
        if (array_key_exists('meta_json', $data)) {
            return (array) $data['meta_json'];
        }

        if (
            !$withDefaults
            && !array_key_exists('note', $data)
            && !array_key_exists('controller', $data)
            && !array_key_exists('redirect', $data)
            && !array_key_exists('hidden', $data)
            && !array_key_exists('keep_alive', $data)
            && null === $type
        ) {
            return null;
        }

        $metaJson = $resource ? (array) ($resource->meta_json ?? []) : [];

        if ($withDefaults || array_key_exists('note', $data)) {
            $metaJson['note'] = $data['note'] ?? '';
        }

        if ($withDefaults || array_key_exists('controller', $data)) {
            $metaJson['controller'] = $data['controller'] ?? '';
        }

        if ($withDefaults || array_key_exists('redirect', $data)) {
            $metaJson['redirect'] = $this->normalizeRouteValue($data['redirect'] ?? null);
        }

        if ($withDefaults || array_key_exists('hidden', $data)) {
            $metaJson['hidden'] = isset($data['hidden']) ? (int) $data['hidden'] : 0;
        }

        if ($withDefaults || array_key_exists('keep_alive', $data)) {
            $metaJson['keep_alive'] = isset($data['keep_alive']) ? (int) $data['keep_alive'] : 0;
        }

        return $this->normalizeMetaJson($metaJson);
    }

    private function mapResourceType(string $type): string
    {
        switch ($type) {
            case 'dir':
            case ResourceType::MENU:
                return ResourceType::MENU;
            case 'btn':
            case ResourceType::BUTTON:
                return ResourceType::BUTTON;
            case ResourceType::FIELD:
                return ResourceType::FIELD;
            case 'link':
            case ResourceType::ROUTE:
                return ResourceType::ROUTE;
            case 'nav':
            case ResourceType::PAGE:
            default:
                return ResourceType::PAGE;
        }
    }

    private function resolveAbilities(string $type): array
    {
        switch ($type) {
            case ResourceType::BUTTON:
                return [Ability::EXECUTE];
            case ResourceType::FIELD:
                return [Ability::VIEW];
            default:
                return [Ability::ACCESS];
        }
    }

    private function resolveModule(string $code): string
    {
        $segments = explode('.', $code);

        return $segments[0] ?? 'system';
    }

    private function normalizeModuleValue($module): string
    {
        return trim((string) $module);
    }

    private function normalizePageKeyValue($pageKey, string $type): ?string
    {
        if (!\in_array($type, [ResourceType::PAGE, ResourceType::ROUTE], true)) {
            return null;
        }

        $pageKey = trim((string) $pageKey);

        return '' === $pageKey ? null : $pageKey;
    }

    private function normalizeRouteValue($route): ?string
    {
        $route = trim((string) $route);

        return '' === $route ? null : $route;
    }

    /**
     * @param array<string, mixed> $metaJson
     *
     * @return array<string, mixed>
     */
    private function normalizeMetaJson(array $metaJson): array
    {
        if (array_key_exists('redirect', $metaJson)) {
            $metaJson['redirect'] = $this->normalizeRouteValue($metaJson['redirect']);
        }

        if (array_key_exists('hidden', $metaJson)) {
            $metaJson['hidden'] = (int) $metaJson['hidden'];
        }

        if (array_key_exists('keep_alive', $metaJson)) {
            $metaJson['keep_alive'] = (int) $metaJson['keep_alive'];
        }

        return $metaJson;
    }

    /**
     * 统一校验资源树协议的最小约束，避免前端需要兜底推断。
     *
     * @param array<string, mixed> $payload
     */
    private function assertResourceProtocol(array $payload): void
    {
        $type = (string) ($payload['type'] ?? '');
        $code = trim((string) ($payload['code'] ?? ''));
        $title = trim((string) ($payload['name'] ?? ''));
        $module = trim((string) ($payload['module'] ?? ''));
        $pageKey = isset($payload['page_key']) ? trim((string) $payload['page_key']) : '';
        $route = isset($payload['route']) && null !== $payload['route'] ? trim((string) $payload['route']) : '';

        if ('' === $code) {
            throw new BackgroundException(__('ptadmin::background.resource_code_required'));
        }

        if ('' === $title) {
            throw new BackgroundException(__('ptadmin::background.resource_title_required'));
        }

        if (\in_array($type, [ResourceType::PAGE, ResourceType::ROUTE], true)) {
            if ('' === $module || '' === $pageKey || '' === $route) {
                throw new BackgroundException(__('ptadmin::background.resource_nav_invalid'));
            }
        }

        if (\in_array($type, [ResourceType::BUTTON, ResourceType::FIELD], true) && '' === $module) {
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
        $codes = [];
        while ($parentId > 0) {
            /** @var null|AdminResource $parent */
            $parent = AdminResource::query()->whereNull('deleted_at')->find($parentId);
            if (null === $parent) {
                break;
            }

            array_unshift($codes, (string) $parent->code);
            $parentId = (int) $parent->parent_id;
        }

        return [\count($codes), [] === $codes ? null : implode('.', $codes)];
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
                $childrenMap[(int) $resource->parent_id][] = (int) $resource->id;
            });

        $ids = [];
        $stack = $childrenMap[$id] ?? [];
        while ([] !== $stack) {
            $childId = (int) array_pop($stack);
            $ids[] = $childId;
            foreach ($childrenMap[$childId] ?? [] as $grandChildId) {
                $stack[] = (int) $grandChildId;
            }
        }

        return array_values(array_unique($ids));
    }
}
