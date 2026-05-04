<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services\Dashboard;

use PTAdmin\Addon\Addon;
use PTAdmin\Contracts\Auth\AuthorizationServiceInterface;
use PTAdmin\Foundation\Exceptions\BackgroundException;

class DashboardWidgetRegistryService
{
    private AuthorizationServiceInterface $authorizationService;

    public function __construct(AuthorizationServiceInterface $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
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

                $results[(string) $normalized['code']] = $normalized;
            }
        }

        return array_values($results);
    }

    /**
     * @param mixed $user
     *
     * @return array<int, array<string, mixed>>
     */
    public function visibleFor($user): array
    {
        return array_values(array_filter($this->all(), function (array $definition) use ($user): bool {
            return $this->canViewWidget($user, $definition);
        }));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allPublic(): array
    {
        return array_map(function (array $definition): array {
            return $this->toPublicDefinition($definition);
        }, $this->all());
    }

    /**
     * @param mixed $user
     *
     * @return array<int, array<string, mixed>>
     */
    public function visiblePublicFor($user): array
    {
        return array_map(function (array $definition): array {
            return $this->toPublicDefinition($definition);
        }, $this->visibleFor($user));
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $code): array
    {
        foreach ($this->all() as $definition) {
            if ((string) ($definition['code'] ?? '') === $code) {
                return $definition;
            }
        }

        throw new BackgroundException(__('ptadmin::background.dashboard_widget_not_exists'));
    }

    /**
     * @param array<string, mixed> $definition
     *
     * @return array<string, mixed>
     */
    public function toPublicDefinition(array $definition): array
    {
        return array(
            'code' => (string) ($definition['code'] ?? ''),
            'title' => (string) ($definition['title'] ?? ''),
            'type' => (string) ($definition['type'] ?? 'stat'),
            'group' => (string) ($definition['group'] ?? 'default'),
            'addon_code' => (string) ($definition['addon_code'] ?? ''),
            'icon' => (string) ($definition['icon'] ?? ''),
            'sort' => (int) ($definition['sort'] ?? 0),
            'resource_code' => (string) ($definition['resource_code'] ?? ''),
            'description' => (string) ($definition['description'] ?? ''),
            'default_enabled' => (bool) ($definition['default_enabled'] ?? true),
            'default_query' => (array) ($definition['default_query'] ?? array()),
            'default_layout' => (array) ($definition['default_layout'] ?? array()),
            'settings_schema' => (array) ($definition['settings_schema'] ?? array()),
            'actions' => array_map(function (array $actionDefinition): array {
                return $this->toPublicActionDefinition($actionDefinition);
            }, array_values((array) ($definition['actions'] ?? array()))),
            'capabilities' => (array) ($definition['capabilities'] ?? array()),
        );
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
            'default_enabled' => (bool) ($definition['default_enabled'] ?? true),
            'default_query' => (array) ($definition['default_query'] ?? array()),
            'default_layout' => $this->normalizeLayout((array) ($definition['default_layout'] ?? array())),
            'settings_schema' => array_values(array_filter((array) ($definition['settings_schema'] ?? array()), static function ($item): bool {
                return \is_array($item);
            })),
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

    /**
     * @param array<string, mixed> $layout
     *
     * @return array<string, int>
     */
    private function normalizeLayout(array $layout): array
    {
        $results = array();

        foreach (['x', 'y', 'w', 'h', 'min_w', 'min_h', 'max_w', 'max_h'] as $field) {
            if (!array_key_exists($field, $layout)) {
                continue;
            }

            $results[$field] = (int) $layout[$field];
        }

        return $results;
    }
}
