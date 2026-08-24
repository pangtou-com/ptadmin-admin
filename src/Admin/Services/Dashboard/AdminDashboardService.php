<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use Illuminate\Support\Facades\Cache;
use PTAdmin\Contracts\AdminDashboardWidgetActionHandlerInterface;
use PTAdmin\Contracts\AdminDashboardWidgetHandlerInterface;
use PTAdmin\Contracts\Dashboard\DashboardWidget;
use PTAdmin\Contracts\Dashboard\DashboardWidgetContext;
use PTAdmin\Contracts\Dashboard\DashboardWidgetQuery;
use PTAdmin\Foundation\Exceptions\BackgroundException;

class AdminDashboardService
{
    private DashboardWidgetRegistryService $registry;
    private DashboardComposerService $composer;

    public function __construct(DashboardWidgetRegistryService $registry, DashboardComposerService $composer)
    {
        $this->registry = $registry;
        $this->composer = $composer;
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
        $definitions = $this->registry->visiblePublicFor($user);
        $group = trim((string) ($search['group'] ?? ''));

        if ('' !== $group) {
            $definitions = array_values(array_filter($definitions, static function (array $definition) use ($group): bool {
                return $group === (string) ($definition['group'] ?? '');
            }));
        }

        usort($definitions, static function (array $left, array $right): int {
            $sort = ((int) ($right['sort'] ?? 0)) <=> ((int) ($left['sort'] ?? 0));
            if (0 !== $sort) {
                return $sort;
            }

            return strcmp((string) ($left['code'] ?? ''), (string) ($right['code'] ?? ''));
        });

        return $definitions;
    }

    /**
     * 查询指定仪表盘组件数据。
     *
     * @param mixed                $user
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function queryWidget($user, string $code, array $query = array(), ?int $tenantId = null): array
    {
        return $this->queryWidgetWithCache($user, $code, $query, $tenantId, new DashboardWidgetQueryCache(), false);
    }

    /**
     * 查询一批仪表盘组件。
     *
     * 每个组件仍然独立执行，单个组件失败不会丢弃同一批次的其他结果。
     * 同一批次使用共享的查询缓存，插件可以复用一次生成的业务快照。
     *
     * @param mixed                $user
     * @param array<int, mixed>   $requests
     *
     * @return array<int, array<string, mixed>>
     */
    public function queryWidgets($user, array $requests = array(), ?int $tenantId = null): array
    {
        $queryCache = new DashboardWidgetQueryCache();
        $results = array();

        foreach ($requests as $request) {
            if (!\is_array($request)) {
                continue;
            }

            $code = trim((string) ($request['code'] ?? ''));
            if ('' === $code) {
                continue;
            }

            try {
                $result = $this->queryWidgetWithCache(
                    $user,
                    $code,
                    (array) ($request['query'] ?? array()),
                    $tenantId,
                    $queryCache,
                    true
                );
                $results[] = array_merge(array('code' => $code), $result, array('error' => null));
            } catch (\Throwable $throwable) {
                $results[] = array(
                    'code' => $code,
                    'widget' => null,
                    'data' => null,
                    'queried_at' => time(),
                    'error' => array(
                        'code' => 'dashboard_widget_query_failed',
                        'message' => $throwable->getMessage(),
                    ),
                );
            }
        }

        return $results;
    }

    /**
     * @param mixed                $user
     * @param array<string, mixed> $query
     */
    private function queryWidgetWithCache(
        $user,
        string $code,
        array $query,
        ?int $tenantId,
        DashboardWidgetQueryCache $queryCache,
        bool $exposeQueryCache
    ): array {
        $definition = $this->registry->find($code);
        $widget = $this->resolveAssignedWidget($user, $code, $tenantId);

        $payload = array_merge((array) ($widget['config'] ?? array()), $query);
        $context = $this->buildContext(
            $user,
            $definition,
            $widget,
            $tenantId,
            $exposeQueryCache ? $queryCache : null
        );

        return array(
            'widget' => $widget,
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
    public function executeWidgetAction($user, string $code, string $actionCode, array $payload = array(), ?int $tenantId = null): array
    {
        $definition = $this->registry->find($code);
        $widget = $this->resolveAssignedWidget($user, $code, $tenantId);

        $actionDefinition = $this->findActionDefinition($definition, $actionCode);
        $context = $this->buildContext($user, $definition, $widget, $tenantId);

        return array(
            'widget' => $widget,
            'action' => $this->toPublicActionDefinition($actionDefinition),
            'data' => $this->invokeActionHandler($definition, $actionDefinition, $payload, $context),
            'executed_at' => time(),
        );
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
            throw new BackgroundException(__('ptadmin::background.dashboard_action_interface_missing'));
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
            'tenant_id' => (int) ($context['tenant_id'] ?? 0),
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
        $widget = $definition['widget_instance'] ?? null;
        if ($widget instanceof DashboardWidget) {
            $queryCache = $context['query_cache'] ?? new DashboardWidgetQueryCache();
            $typedContext = new DashboardWidgetContext(
                (int) ($context['user_id'] ?? 0),
                isset($context['tenant_id']) ? ($context['tenant_id'] !== null ? (int) $context['tenant_id'] : null) : null,
                (bool) ($context['is_founder'] ?? false),
                (string) ($context['addon_code'] ?? ''),
                (string) ($context['widget_code'] ?? ''),
                (string) ($context['resource_code'] ?? ''),
                $queryCache instanceof DashboardWidgetQueryCache ? $queryCache : new DashboardWidgetQueryCache()
            );

            $result = $widget->query(new DashboardWidgetQuery($payload), $typedContext);
            if (!is_object($result) || !method_exists($result, 'toArray')) {
                throw new BackgroundException(__('ptadmin::background.dashboard_handler_interface_missing'));
            }

            return $result->toArray();
        }

        $handler = app($handlerClass);

        if (!$handler instanceof AdminDashboardWidgetHandlerInterface) {
            throw new BackgroundException(__('ptadmin::background.dashboard_handler_interface_missing'));
        }
        if (!method_exists($handler, "query")) {
            throw new BackgroundException(__('ptadmin::background.dashboard_handler_interface_missing'));
        }

        return $handler->query($payload, $definition, $context);
    }

    /**
     * @param mixed                $user
     * @param array<string, mixed> $definition
     *
     * @return array<string, mixed>
     */
    private function buildContext(
        $user,
        array $definition,
        array $widget,
        ?int $tenantId = null,
        ?DashboardWidgetQueryCache $queryCache = null
    ): array
    {
        $context = array(
            'user_id' => (int) data_get($user, 'id', 0),
            'tenant_id' => $tenantId,
            'is_founder' => $this->isFounder($user),
            'resource_code' => (string) ($definition['resource_code'] ?? ''),
            'addon_code' => (string) ($definition['addon_code'] ?? ''),
            'widget_code' => (string) ($definition['code'] ?? ''),
            'widget_type' => (string) ($definition['type'] ?? ''),
            'widget_config' => (array) ($widget['config'] ?? array()),
            'widget_layout' => (array) ($widget['layout'] ?? array()),
            'widget_source' => (array) ($widget['source'] ?? array()),
        );

        if ($queryCache instanceof DashboardWidgetQueryCache) {
            $context['query_cache'] = $queryCache;
        }

        return $context;
    }

    /**
     * @param mixed $user
     *
     * @return array<string, mixed>
     */
    private function resolveAssignedWidget($user, string $widgetCode, ?int $tenantId = null): array
    {
        foreach ($this->composer->widgetsForUser($user, $tenantId) as $widget) {
            if ((string) ($widget['code'] ?? '') === $widgetCode) {
                return $widget;
            }
        }

        throw new BackgroundException(__('ptadmin::background.dashboard_forbidden'));
    }

    /**
     * @param mixed $user
     */
    private function isFounder($user): bool
    {
        return 1 === (int) data_get($user, 'is_founder', 0);
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

        throw new BackgroundException(__('ptadmin::background.dashboard_action_not_exists'));
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
