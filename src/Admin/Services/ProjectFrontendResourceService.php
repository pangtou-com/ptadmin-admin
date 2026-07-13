<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use PTAdmin\Contracts\Auth\AdminResourceServiceInterface;
use PTAdmin\Support\Enums\MenuTypeEnum;

class ProjectFrontendResourceService
{
    private const DEFAULT_PROJECT_CODE = '__app__';

    private AdminResourceServiceInterface $adminResourceService;

    public function __construct(AdminResourceServiceInterface $adminResourceService)
    {
        $this->adminResourceService = $adminResourceService;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        $path = $this->manifestPath();
        if (!is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if (false === $content) {
            throw new \RuntimeException(sprintf('无法读取项目资源清单：%s', $path));
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(sprintf('项目资源清单不是有效 JSON：%s', $path), 0, $exception);
        }

        if (!\is_array($payload)) {
            throw new \RuntimeException(sprintf('项目资源清单必须是数组或包含 resources 数组：%s', $path));
        }

        $definitions = array_key_exists('resources', $payload) ? $payload['resources'] : $payload;
        if (!\is_array($definitions) || !$this->isList($definitions)) {
            throw new \RuntimeException(sprintf('项目资源清单的 resources 必须是列表：%s', $path));
        }

        $results = [];
        $names = [];
        foreach ($definitions as $index => $definition) {
            if (!\is_array($definition)) {
                throw $this->invalidDefinition((int) $index, '资源定义必须是对象');
            }

            $normalized = $this->normalizeDefinition($definition, (int) $index);
            if (isset($names[$normalized['name']])) {
                throw $this->invalidDefinition((int) $index, sprintf('资源编码 %s 重复', $normalized['name']));
            }

            $names[$normalized['name']] = true;
            $results[] = $normalized;
        }

        return $this->sortByParent($results);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function developmentDefinitions(): array
    {
        return (bool) config('app.debug', false) ? $this->definitions() : [];
    }

    public function sync(): int
    {
        $path = $this->manifestPath();
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('项目资源清单不存在：%s', $path));
        }

        $definitions = $this->definitions();
        // 数据表当前以 addon_code 记录资源所有者，项目二开沿用保留模块编码，才能在后续同步时停用已移出清单的资源。
        $this->adminResourceService->syncAddonResources($this->projectCode(), $definitions);

        return \count($definitions);
    }

    public function manifestPath(): string
    {
        $path = trim((string) config(
            'ptadmin.project_frontend_resource_manifest',
            base_path('resources/ptadmin/frontend/resources.json')
        ));

        if ('' === $path) {
            return base_path('resources/ptadmin/frontend/resources.json');
        }

        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    public function projectCode(): string
    {
        $code = trim((string) config('ptadmin.project_frontend_code', self::DEFAULT_PROJECT_CODE));

        return '' === $code ? self::DEFAULT_PROJECT_CODE : $code;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function normalizeDefinition(array $definition, int $index): array
    {
        $name = trim((string) ($definition['name'] ?? ''));
        $title = trim((string) ($definition['title'] ?? ''));
        $type = trim((string) ($definition['type'] ?? ''));
        $module = trim((string) ($definition['module'] ?? ''));
        $pageKey = trim((string) ($definition['page_key'] ?? ''));
        $route = trim((string) ($definition['route'] ?? ''));

        if ('' === $name) {
            throw $this->invalidDefinition($index, '缺少 name');
        }
        if ('' === $title) {
            throw $this->invalidDefinition($index, sprintf('资源 %s 缺少 title', $name));
        }
        if (!\in_array($type, [MenuTypeEnum::DIR, MenuTypeEnum::NAV, MenuTypeEnum::BTN, MenuTypeEnum::LINK], true)) {
            throw $this->invalidDefinition($index, sprintf('资源 %s 的 type 无效', $name));
        }

        if (MenuTypeEnum::DIR === $type) {
            $module = '';
        } elseif ('' === $module) {
            $module = $this->projectCode();
        }

        if (MenuTypeEnum::NAV === $type && ('' === $pageKey || '' === $route)) {
            throw $this->invalidDefinition($index, sprintf('导航资源 %s 必须声明 page_key 和 route', $name));
        }

        $result = [
            'name' => $name,
            'title' => $title,
            'type' => $type,
            'module' => $module,
            'page_key' => '' === $pageKey ? null : $pageKey,
            'route' => '' === $route ? null : $route,
            'icon' => isset($definition['icon']) && '' !== trim((string) $definition['icon'])
                ? trim((string) $definition['icon'])
                : null,
            'is_nav' => isset($definition['is_nav']) ? (int) $definition['is_nav'] : 0,
            'status' => isset($definition['status']) ? (int) $definition['status'] : 1,
            'sort' => isset($definition['sort']) ? (int) $definition['sort'] : 0,
        ];

        $parent = trim((string) ($definition['parent'] ?? ''));
        if ('' !== $parent) {
            $result['parent'] = $parent;
        }

        if (isset($definition['meta']) && \is_array($definition['meta'])) {
            $result['meta'] = $definition['meta'];
        }
        if (array_key_exists('hidden', $definition)) {
            $result['hidden'] = (int) $definition['hidden'];
        }
        if (array_key_exists('keep_alive', $definition)) {
            $result['keep_alive'] = (int) $definition['keep_alive'];
        }

        return $result;
    }

    private function invalidDefinition(int $index, string $message): \RuntimeException
    {
        return new \RuntimeException(sprintf('项目资源清单第 %d 项无效：%s', $index + 1, $message));
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     * @return array<int, array<string, mixed>>
     */
    private function sortByParent(array $definitions): array
    {
        $remaining = [];
        foreach ($definitions as $definition) {
            $remaining[$definition['name']] = $definition;
        }

        $sorted = [];
        $resolved = [];
        while ([] !== $remaining) {
            $progress = false;
            foreach ($remaining as $name => $definition) {
                $parent = (string) ($definition['parent'] ?? '');
                if ('' !== $parent && isset($remaining[$parent]) && !isset($resolved[$parent])) {
                    continue;
                }

                $sorted[] = $definition;
                $resolved[$name] = true;
                unset($remaining[$name]);
                $progress = true;
            }

            if (!$progress) {
                throw new \RuntimeException('项目资源清单包含循环父级：'.implode('、', array_keys($remaining)));
            }
        }

        return $sorted;
    }

    /**
     * PHP 7.4 没有 array_is_list，项目资源清单仍需严格限制为连续列表。
     *
     * @param array<mixed> $value
     */
    private function isList(array $value): bool
    {
        if ([] === $value) {
            return true;
        }

        return array_keys($value) === range(0, \count($value) - 1);
    }

    private function isAbsolutePath(string $path): bool
    {
        return '/' === $path[0]
            || '\\' === $path[0]
            || (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
    }
}
