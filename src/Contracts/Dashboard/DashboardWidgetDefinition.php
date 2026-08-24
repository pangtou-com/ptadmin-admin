<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Dashboard;

final class DashboardWidgetDefinition
{
    private string $code;
    private string $title;
    private string $type = 'stats';
    private string $group = 'default';
    private string $icon = '';
    private int $sort = 0;
    private string $resourceCode = '';
    private string $description = '';
    private bool $defaultEnabled = true;
    /** @var array<string, mixed> */
    private array $defaultQuery = array();
    /** @var array<string, int> */
    private array $defaultLayout = array();
    /** @var array<int, array<string, mixed>> */
    private array $settingsSchema = array();
    /** @var array<int, array<string, mixed>> */
    private array $actions = array();
    /** @var array<string, bool> */
    private array $capabilities = array(
        'refresh' => true,
        'range' => false,
        'filters' => false,
        'drilldown' => false,
    );
    private int $cacheTtl = 0;

    public function __construct(string $code, string $title)
    {
        $this->code = $code;
        $this->title = $title;
    }

    public function type(string $type): self { $this->type = $type; return $this; }
    public function group(string $group): self { $this->group = $group; return $this; }
    public function icon(string $icon): self { $this->icon = $icon; return $this; }
    public function sort(int $sort): self { $this->sort = $sort; return $this; }
    public function resource(string $resourceCode): self { $this->resourceCode = $resourceCode; return $this; }
    public function description(string $description): self { $this->description = $description; return $this; }
    public function defaultEnabled(bool $enabled): self { $this->defaultEnabled = $enabled; return $this; }
    /** @param array<string, mixed> $query */
    public function defaultQuery(array $query): self { $this->defaultQuery = $query; return $this; }
    /** @param array<string, int> $layout */
    public function defaultLayout(array $layout): self { $this->defaultLayout = $layout; return $this; }
    /** @param array<int, array<string, mixed>> $schema */
    public function settings(array $schema): self { $this->settingsSchema = $schema; return $this; }
    public function action(DashboardWidgetActionDefinition $action): self { $this->actions[] = $action->toArray(); return $this; }
    public function capability(string $name, bool $enabled = true): self { $this->capabilities[$name] = $enabled; return $this; }
    public function cacheFor(int $seconds): self { $this->cacheTtl = max(0, $seconds); return $this; }

    /** @return array<string, mixed> */
    public function toArray(string $addonCode, string $handlerClass): array
    {
        return array(
            'code' => $this->code,
            'title' => $this->title,
            'type' => $this->type,
            'group' => $this->group,
            'addon_code' => $addonCode,
            'icon' => $this->icon,
            'sort' => $this->sort,
            'resource_code' => $this->resourceCode,
            'description' => $this->description,
            'default_enabled' => $this->defaultEnabled,
            'default_query' => $this->defaultQuery,
            'default_layout' => $this->defaultLayout,
            'settings_schema' => $this->settingsSchema,
            'actions' => $this->actions,
            'capabilities' => $this->capabilities,
            'query_handler' => $handlerClass,
            'action_handler' => '',
            'cache_ttl' => $this->cacheTtl,
        );
    }
}
