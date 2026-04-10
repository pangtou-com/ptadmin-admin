<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use Illuminate\Support\Facades\Cache;
use PTAdmin\Addon\Addon;
use PTAdmin\Contracts\AdminDashboardWidgetActionHandlerInterface;
use PTAdmin\Contracts\AdminDashboardWidgetHandlerInterface;
use PTAdmin\Contracts\Auth\AuthorizationServiceInterface;
use PTAdmin\Foundation\Exceptions\BackgroundException;

class AdminDashboardService
{
    private AuthorizationServiceInterface $authorizationService;

    public function __construct(AuthorizationServiceInterface $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    /**
     * 返回当前用户可见的仪表盘组件定义。
     *
     * @param mixed                $user
     * @param array<string, mixed> $search
     *
     * @return array<int, array<string, mixed>>
     */
    public function widgets($user, array $search = array()): array
    {
        $definitions = $this->collectDefinitions();
        $group = trim((string) ($search['group'] ?? ''));

        if ('' !== $group) {
            $definitions = array_values(array_filter($definitions, static function (array $definition) use ($group): bool {
                return $group === (string) ($definition['group'] ?? '');
            }));
        }

        $definitions = array_values(array_filter($definitions, function (array $definition) use ($user): bool {
            return $this->canViewWidget($user, $definition);
        }));

        usort($definitions, static function (array $left, array $right): int {
            $sort = ((int) ($right['sort'] ?? 0)) <=> ((int) ($left['sort'] ?? 0));
            if (0 !== $sort) {
                return $sort;
            }

            return strcmp((string) ($left['code'] ?? ''), (string) ($right['code'] ?? ''));
        });

        return array_map(function (array $definition): array {
            return $this->toPublicDefinition($definition);
        }, $definitions);
    }

    /**
     * 查询指定仪表盘组件数据。
     *
     * @param mixed                $user
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function queryWidget($user, string $code, array $query = array()): array
    {
        $definition = $this->findDefinition($code);

        if (!$this->canViewWidget($user, $definition)) {
            throw new BackgroundException('暂无权限访问该仪表盘组件');
        }

        $payload = array_merge((array) ($definition['default_query'] ?? array()), $query);
        $context = $this->buildContext($user, $definition);

        return array(
            'widget' => $this->toPublicDefinition($definition),
            'data' => $this->executeQuery($definition, $payload, $context),
            'queried_at' => time(),
        );
    }

    /**
     * 执行指定仪表盘组件动作。
     *
     * @param mixed                $user
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function executeWidgetAction($user, string $code, string $actionCode, array $payload = array()): array
    {
        $definition = $this->findDefinition($code);

        if (!$this->canViewWidget($user, $definition)) {
            throw new BackgroundException('暂无权限访问该仪表盘组件');
        }

        $actionDefinition = $this->findActionDefinition($definition, $actionCode);
        $context = $this->buildContext($user, $definition);

        return array(
            'widget' => $this->toPublicDefinition($definition),
            'action' => $this->toPublicActionDefinition($actionDefinition),
            'data' => $this->invokeActionHandler($definition, $actionDefinition, $payload, $context),
            'executed_at' => time(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectDefinitions(): array
    {
        $results = array();

        foreach (array_keys(Addon::getAddons()) as $addonCode) {
            $bootstrap = Addon::getAddonBootstrap($addonCode);
            if (null === $bootstrap || !method_exists($bootstrap, 'getAdminDashboardWidgetDefinitions')) {
                continue;
            }

            $addonInfo = Addon::getAddon($addonCode)->getAddons();
            $definitions = $bootstrap->getAdminDashboardWidgetDefinitions($addonCode, $addonInfo);
            if (!\is_array($definitions)) {
                continue;
            }

            foreach ($definitions as $definition) {
                if (!\is_array($definition)) {
                    continue;
                }

                $normalized = $this->normalizeDefinition($addonCode, $definition);
                if ([] === $normalized) {
                    continue;
                }

                $results[] = $normalized;
            }
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $definition
     *
     * @return array<string, mixed>
     */
    private function normalizeDefinition(string $addonCode, array $definition): array
    {
        $code = trim((string) ($definition['code'] ?? ''));
        $title = trim((string) ($definition['title'] ?? ''));
        $handler = trim((string) ($definition['query_handler'] ?? ''));

        if ('' === $code || '' === $title || '' === $handler) {
            return array();
        }

        $capabilities = (array) ($definition['capabilities'] ?? array());

        return array(
            'code' => $code,
            'title' => $title,
            'type' => trim((string) ($definition['type'] ?? 'stat')),
            'group' => trim((string) ($definition['group'] ?? 'default')),
            'addon_code' => $addonCode,
            'icon' => trim((string) ($definition['icon'] ?? '')),
            'sort' => (int) ($definition['sort'] ?? 0),
            'resource_code' => trim((string) ($definition['resource_code'] ?? '')),
            'description' => trim((string) ($definition['description'] ?? '')),
            'default_query' => (array) ($definition['default_query'] ?? array()),
            'actions' => $this->normalizeActionDefinitions((array) ($definition['actions'] ?? array())),
            'capabilities' => array(
                'refresh' => (bool) ($capabilities['refresh'] ?? true),
                'range' => (bool) ($capabilities['range'] ?? false),
                'filters' => (bool) ($capabilities['filters'] ?? false),
                'drilldown' => (bool) ($capabilities['drilldown'] ?? false),
            ),
            'query_handler' => $handler,
            'action_handler' => trim((string) ($definition['action_handler'] ?? '')),
            'cache_ttl' => max(0, (int) ($definition['cache_ttl'] ?? 0)),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $actions
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeActionDefinitions(array $actions): array
    {
        $results = array();

        foreach ($actions as $action) {
            if (!\is_array($action)) {
                continue;
            }

            $code = trim((string) ($action['code'] ?? ''));
            $label = trim((string) ($action['label'] ?? ''));
            if ('' === $code || '' === $label) {
                continue;
            }

            $results[] = array(
                'code' => $code,
                'label' => $label,
                'type' => trim((string) ($action['type'] ?? 'request')),
                'target' => trim((string) ($action['target'] ?? '')),
                'confirm_text' => trim((string) ($action['confirm_text'] ?? '')),
                'payload_schema' => (array) ($action['payload_schema'] ?? array()),
                'meta' => (array) ($action['meta'] ?? array()),
            );
        }

        return array_values($results);
    }

    /**
     * @param mixed                $user
     * @param array<string, mixed> $definition
     */
    private function canViewWidget($user, array $definition): bool
    {
        $resourceCode = trim((string) ($definition['resource_code'] ?? ''));
        if ('' === $resourceCode) {
            return true;
        }

        if ($this->isFounder($user)) {
            return true;
        }

        return $this->authorizationService->allows($user, 'access', $resourceCode);
    }

    /**
     * @param mixed                $user
     * @param array<string, mixed> $definition
     */
    private function isFounder($user): bool
    {
        return 1 === (int) data_get($user, 'is_founder', 0);
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function executeQuery(array $definition, array $payload, array $context): array
    {
        $handlerClass = (string) $definition['query_handler'];
        $cacheTtl = (int) ($definition['cache_ttl'] ?? 0);
        $shouldRefresh = (int) ($payload['refresh'] ?? 0) === 1;

        if ($cacheTtl > 0 && !$shouldRefresh) {
            $cacheKey = $this->buildCacheKey($definition, $payload, $context);

            return Cache::remember($cacheKey, $cacheTtl, function () use ($handlerClass, $payload, $definition, $context): array {
                return $this->invokeHandler($handlerClass, $payload, $definition, $context);
            });
        }

        return $this->invokeHandler($handlerClass, $payload, $definition, $context);
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $actionDefinition
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function invokeActionHandler(array $definition, array $actionDefinition, array $payload, array $context): array
    {
        $handlerClass = trim((string) ($definition['action_handler'] ?? ''));
        if ('' === $handlerClass) {
            $handlerClass = (string) ($definition['query_handler'] ?? '');
        }

        $handler = app($handlerClass);

        if (!$handler instanceof AdminDashboardWidgetActionHandlerInterface) {
            throw new BackgroundException('仪表盘组件未实现动作处理接口');
        }

        return $handler->executeAction(
            (string) ($actionDefinition['code'] ?? ''),
            $payload,
            $definition,
            $context,
            $actionDefinition
        );
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     */
    private function buildCacheKey(array $definition, array $payload, array $context): string
    {
        return 'ptadmin:dashboard:widget:'.md5(json_encode(array(
            'code' => (string) $definition['code'],
            'payload' => $payload,
            'user_id' => (int) ($context['user_id'] ?? 0),
            'resource_code' => (string) ($context['resource_code'] ?? ''),
        )));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function invokeHandler(string $handlerClass, array $payload, array $definition, array $context): array
    {
        $handler = app($handlerClass);

        if (!$handler instanceof AdminDashboardWidgetHandlerInterface) {
            throw new BackgroundException('仪表盘组件处理器未实现标准接口');
        }

        return $handler->query($payload, $definition, $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function findDefinition(string $code): array
    {
        foreach ($this->collectDefinitions() as $definition) {
            if ((string) ($definition['code'] ?? '') === $code) {
                return $definition;
            }
        }

        throw new BackgroundException('仪表盘组件不存在');
    }

    /**
     * @param mixed                $user
     * @param array<string, mixed> $definition
     *
     * @return array<string, mixed>
     */
    private function buildContext($user, array $definition): array
    {
        return array(
            'user_id' => (int) data_get($user, 'id', 0),
            'is_founder' => $this->isFounder($user),
            'resource_code' => (string) ($definition['resource_code'] ?? ''),
            'addon_code' => (string) ($definition['addon_code'] ?? ''),
        );
    }

    /**
     * @param array<string, mixed> $definition
     *
     * @return array<string, mixed>
     */
    private function findActionDefinition(array $definition, string $actionCode): array
    {
        foreach ((array) ($definition['actions'] ?? array()) as $actionDefinition) {
            if ((string) ($actionDefinition['code'] ?? '') === $actionCode) {
                return $actionDefinition;
            }
        }

        throw new BackgroundException('仪表盘动作不存在');
    }

    /**
     * @param array<string, mixed> $definition
     *
     * @return array<string, mixed>
     */
    private function toPublicDefinition(array $definition): array
    {
        return array(
            'code' => (string) $definition['code'],
            'title' => (string) $definition['title'],
            'type' => (string) $definition['type'],
            'group' => (string) $definition['group'],
            'addon_code' => (string) $definition['addon_code'],
            'icon' => (string) $definition['icon'],
            'sort' => (int) $definition['sort'],
            'resource_code' => (string) $definition['resource_code'],
            'description' => (string) $definition['description'],
            'default_query' => (array) $definition['default_query'],
            'actions' => array_map(function (array $actionDefinition): array {
                return $this->toPublicActionDefinition($actionDefinition);
            }, array_values((array) $definition['actions'])),
            'capabilities' => (array) $definition['capabilities'],
        );
    }

    /**
     * @param array<string, mixed> $actionDefinition
     *
     * @return array<string, mixed>
     */
    private function toPublicActionDefinition(array $actionDefinition): array
    {
        return array(
            'code' => (string) ($actionDefinition['code'] ?? ''),
            'label' => (string) ($actionDefinition['label'] ?? ''),
            'type' => (string) ($actionDefinition['type'] ?? 'request'),
            'target' => (string) ($actionDefinition['target'] ?? ''),
            'confirm_text' => (string) ($actionDefinition['confirm_text'] ?? ''),
            'payload_schema' => (array) ($actionDefinition['payload_schema'] ?? array()),
            'meta' => (array) ($actionDefinition['meta'] ?? array()),
        );
    }
}
