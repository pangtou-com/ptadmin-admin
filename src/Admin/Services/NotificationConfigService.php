<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Models\NotificationScene;
use PTAdmin\Admin\Models\NotificationSceneRoute;
use PTAdmin\Admin\Models\NotificationTemplate;
use PTAdmin\Admin\Notifications\NotificationChannel;
use PTAdmin\Admin\Notifications\NotificationChannelProviderRegistry;
use PTAdmin\Admin\Notifications\NotificationChannelTargetMode;
use PTAdmin\Admin\Notifications\NotificationDispatchMode;
use PTAdmin\Admin\Notifications\NotificationSceneRegistry;
use PTAdmin\Admin\Notifications\NotificationSceneConfigurationStatus;
use PTAdmin\Admin\Notifications\NotificationStrategy;
use PTAdmin\Foundation\Exceptions\BackgroundException;

final class NotificationConfigService
{
    /** @var NotificationSceneRegistry */
    private $registry;

    /** @var NotificationChannelProviderRegistry */
    private $providers;

    public function __construct(
        NotificationSceneRegistry $registry,
        NotificationChannelProviderRegistry $providers
    )
    {
        $this->registry = $registry;
        $this->providers = $providers;
    }

    public function scenes(array $query = []): array
    {
        $builder = NotificationScene::query()
            ->with(['templates', 'routes'])
            ->orderBy('group_title')
            ->orderBy('code');
        $keyword = trim((string) ($query['keyword'] ?? ''));
        if ('' !== $keyword) {
            $builder->where(static function ($query) use ($keyword): void {
                $query->where('code', 'like', '%'.$keyword.'%')
                    ->orWhere('title', 'like', '%'.$keyword.'%')
                    ->orWhere('description', 'like', '%'.$keyword.'%')
                    ->orWhere('source_code', 'like', '%'.$keyword.'%')
                    ->orWhere('group_title', 'like', '%'.$keyword.'%');
            });
        }
        if (isset($query['enabled']) && in_array((string) $query['enabled'], ['0', '1'], true)) {
            $builder->where('enabled', (int) $query['enabled']);
        }
        foreach (['source_type', 'source_code', 'group_code'] as $field) {
            if (isset($query[$field]) && '' !== trim((string) $query[$field])) {
                $builder->where($field, trim((string) $query[$field]));
            }
        }
        $channel = trim((string) ($query['channel'] ?? ''));
        if ('' !== $channel) {
            $channel = NotificationChannel::normalize($channel);
            $builder->whereHas('templates', static function ($templateQuery) use ($channel): void {
                $templateQuery->where('channel', $channel)->where('enabled', 1);
            });
        }

        $availableChannels = $this->availableChannels();
        $rows = $builder->get()->map(function (NotificationScene $scene) use ($availableChannels): array {
            return $this->sceneSummary($scene, $availableChannels);
        });
        $configurationStatus = (string) ($query['configuration_status'] ?? '');
        if (in_array($configurationStatus, NotificationSceneConfigurationStatus::all(), true)) {
            $rows = $rows->where('configuration_status', $configurationStatus)->values();
        }

        $total = $rows->count();
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($query['per_page'] ?? 20)));

        return [
            'results' => $rows->slice(($page - 1) * $perPage, $perPage)->values()->all(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'filters' => $this->sceneFilterOptions(),
        ];
    }

    public function scene(int $id): array
    {
        $scene = NotificationScene::query()
            ->with([
                'templates' => static function ($query): void {
                    $query->orderBy('channel')->orderBy('locale');
                },
                'routes',
            ])
            ->find($id);
        if (!$scene instanceof NotificationScene) {
            throw new BackgroundException('通知场景不存在');
        }

        $data = $this->sceneSummary($scene, $this->availableChannels());
        $data['description'] = $scene->description;
        $data['variables'] = array_values((array) $scene->variables);
        $data['templates'] = $scene->templates->map(function (NotificationTemplate $template): array {
            return $this->template($template);
        })->all();
        $data['routes'] = $this->routeGroups($scene);

        return $data;
    }

    public function updateTemplate(int $id, ?array $config, ?bool $enabled = null): array
    {
        return DB::transaction(function () use ($id, $config, $enabled): array {
            $template = NotificationTemplate::query()->lockForUpdate()->find($id);
            if (!$template instanceof NotificationTemplate) {
                throw new BackgroundException('通知模板不存在');
            }
            $scene = NotificationScene::query()->find((int) $template->scene_id);
            if (!$scene instanceof NotificationScene) {
                throw new BackgroundException('通知模板所属场景不存在');
            }
            if (false === $enabled && in_array($template->channel, (array) $scene->default_channels, true)) {
                throw new BackgroundException('默认渠道模板不能禁用，请先调整场景默认渠道');
            }

            if (null !== $config) {
                $template->config = $this->registry->normalizeTemplateConfig(
                    $scene,
                    (string) $template->channel,
                    (string) $template->mode,
                    $config
                );
                $template->customized = 1;
            }
            if (null !== $enabled) {
                $template->enabled = $enabled ? 1 : 0;
            }
            $template->save();

            return $this->template($template->fresh());
        });
    }

    public function channels(): array
    {
        $templateCounts = NotificationTemplate::query()
            ->where('enabled', 1)
            ->selectRaw('channel, count(*) as aggregate')
            ->groupBy('channel')
            ->pluck('aggregate', 'channel')
            ->all();
        $grouped = [];
        foreach ($this->providers->all() as $provider) {
            $code = (string) $provider['channel'];
            $grouped[$code][] = $provider;
        }

        $channels = [];
        foreach ($grouped as $code => $providers) {
            $primary = $providers[0];
            $channels[] = [
                'code' => $code,
                'title' => $this->channelTitle($code),
                'available' => [] !== array_filter($providers, static function (array $provider): bool {
                    return true === $provider['available'];
                }),
                'configured' => [] !== array_filter($providers, static function (array $provider): bool {
                    return true === $provider['configured'];
                }),
                // 保留旧版单 provider 字段，已有调用方可以平滑迁移到 providers 数组。
                'enabled' => [] !== array_filter($providers, static function (array $provider): bool {
                    return true === $provider['available'];
                }),
                'driver' => (string) $primary['driver'],
                'provider' => (string) $primary['code'],
                'config_source' => (string) $primary['config_source'],
                'implementation_count' => count($providers),
                'template_count' => (int) ($templateCounts[$code] ?? 0),
                'providers' => array_values($providers),
            ];
        }

        usort($channels, static function (array $left, array $right): int {
            return $left['code'] <=> $right['code'];
        });

        return $channels;
    }

    public function updateRoutes(int $sceneId, string $channel, array $payload): array
    {
        $channel = NotificationChannel::normalize($channel);
        $scene = NotificationScene::query()->with('templates')->find($sceneId);
        if (!$scene instanceof NotificationScene) {
            throw new BackgroundException('通知场景不存在');
        }
        if (!$scene->templates->contains('channel', $channel)) {
            throw new BackgroundException('通知场景没有配置渠道 ['.$channel.'] 的模板');
        }

        $dispatchMode = (string) ($payload['dispatch_mode'] ?? '');
        if (!in_array($dispatchMode, NotificationDispatchMode::all(), true)) {
            throw new BackgroundException('通知场景路由的投递模式无效');
        }
        $strategy = null;
        if (NotificationDispatchMode::SELECT_ONE === $dispatchMode) {
            $strategy = (string) ($payload['strategy'] ?? '');
            if (!in_array($strategy, [NotificationStrategy::FIXED, NotificationStrategy::PRIORITY, NotificationStrategy::ROUND_ROBIN], true)) {
                throw new BackgroundException('通知场景路由的选择策略无效');
            }
        }

        $references = [];
        foreach ((array) ($payload['instances'] ?? []) as $reference) {
            if (!is_array($reference)) {
                continue;
            }
            $addonCode = isset($reference['addon_code']) && '' !== $reference['addon_code']
                ? (string) $reference['addon_code']
                : null;
            $group = (string) ($reference['group'] ?? '');
            $providerCode = (string) ($reference['provider'] ?? '');
            $instanceCode = (string) ($reference['instance_code'] ?? '');
            $registered = $this->providers->findInstance(
                $channel,
                $providerCode,
                $group,
                $addonCode,
                $instanceCode
            );
            if (null === $registered || true !== $registered['instance']['available']) {
                throw new BackgroundException(
                    '通知场景路由引用的插件渠道实例 ['.$providerCode.'/'.$instanceCode.'] 不存在或不可用'
                );
            }
            $key = ($addonCode ?? '').'|'.$group.'|'.$providerCode.'|'.$instanceCode;
            $references[$key] = [
                'addon_code' => $addonCode,
                'provider_group' => $group,
                'provider' => $providerCode,
                'instance_code' => $instanceCode,
            ];
        }
        $references = array_values($references);
        if ([] === $references) {
            throw new BackgroundException('通知场景路由至少需要选择一个渠道实例');
        }
        if (NotificationStrategy::FIXED === $strategy && 1 !== count($references)) {
            throw new BackgroundException('固定策略只能选择一个渠道实例');
        }

        DB::transaction(function () use ($sceneId, $channel, $references, $dispatchMode, $strategy): void {
            $revision = (int) NotificationSceneRoute::query()
                ->where('scene_id', $sceneId)
                ->where('channel', $channel)
                ->max('revision') + 1;
            NotificationSceneRoute::query()->where('scene_id', $sceneId)->where('channel', $channel)->delete();
            foreach ($references as $priority => $reference) {
                NotificationSceneRoute::query()->create(array_merge($reference, [
                    'scene_id' => $sceneId,
                    'channel' => $channel,
                    'dispatch_mode' => $dispatchMode,
                    'strategy' => $strategy,
                    'priority' => $priority,
                    'weight' => 100,
                    'revision' => max(1, $revision),
                    'enabled' => 1,
                ]));
            }
        });

        $fresh = NotificationScene::query()->with('routes')->findOrFail($sceneId);
        foreach ($this->routeGroups($fresh) as $group) {
            if ($channel === $group['channel']) {
                return $group;
            }
        }

        throw new BackgroundException('通知场景路由保存失败');
    }

    public function clearRoutes(int $sceneId, string $channel): array
    {
        $channel = NotificationChannel::normalize($channel);
        $scene = NotificationScene::query()->with('templates')->find($sceneId);
        if (!$scene instanceof NotificationScene) {
            throw new BackgroundException('通知场景不存在');
        }
        if (!$scene->templates->contains('channel', $channel)) {
            throw new BackgroundException('通知场景没有配置渠道 ['.$channel.'] 的模板');
        }

        DB::transaction(static function () use ($sceneId, $channel): void {
            NotificationSceneRoute::query()
                ->where('scene_id', $sceneId)
                ->where('channel', $channel)
                ->delete();
        });

        return [
            'channel' => $channel,
            'mode' => 'automatic',
        ];
    }

    private function sceneSummary(NotificationScene $scene, array $availableChannels): array
    {
        $templates = $scene->templates;
        $enabledTemplates = $templates->filter(static function (NotificationTemplate $template): bool {
            return 1 === (int) $template->enabled;
        });
        $configuredChannels = $enabledTemplates->pluck('channel')->unique()->values()->all();
        $missingDefaultChannels = [];
        foreach ((array) $scene->default_channels as $channel) {
            if (!in_array($channel, $configuredChannels, true) || !$this->channelReady($scene, (string) $channel, $availableChannels)) {
                $missingDefaultChannels[] = $channel;
            }
        }

        if ($enabledTemplates->isEmpty()) {
            $configurationStatus = NotificationSceneConfigurationStatus::PENDING;
        } elseif ([] !== $missingDefaultChannels) {
            $configurationStatus = NotificationSceneConfigurationStatus::INCOMPLETE;
        } else {
            $configurationStatus = NotificationSceneConfigurationStatus::COMPLETE;
        }

        return [
            'id' => (int) $scene->id,
            'code' => (string) $scene->code,
            'title' => (string) $scene->title,
            'source_type' => (string) $scene->source_type,
            'source_code' => (string) $scene->source_code,
            'group_code' => (string) $scene->group_code,
            'group_title' => (string) $scene->group_title,
            'purpose' => (string) $scene->purpose,
            'default_channels' => array_values((array) $scene->default_channels),
            'configured_channels' => $configuredChannels,
            'missing_default_channels' => $missingDefaultChannels,
            'configuration_status' => $configurationStatus,
            'enabled' => (bool) $scene->enabled,
            'variable_count' => count((array) $scene->variables),
            'template_count' => $templates->count(),
            'updated_at' => (int) $scene->updated_at,
        ];
    }

    private function availableChannels(): array
    {
        $channels = [];
        foreach ($this->providers->all() as $provider) {
            if (true === ($provider['available'] ?? false)) {
                $channels[(string) $provider['channel']][] = $provider;
            }
        }

        return $channels;
    }

    private function channelReady(NotificationScene $scene, string $channel, array $availableChannels): bool
    {
        if (NotificationChannel::SITE === $channel) {
            return true;
        }

        $routes = $scene->routes->where('channel', $channel)->where('enabled', 1);
        if ($routes->isNotEmpty()) {
            foreach ($routes as $route) {
                $registered = $this->providers->findInstance(
                    $channel,
                    (string) $route->provider,
                    (string) $route->provider_group,
                    null === $route->addon_code ? null : (string) $route->addon_code,
                    (string) $route->instance_code
                );
                if (null === $registered || true !== $registered['instance']['available']) {
                    return false;
                }
            }

            return true;
        }

        $availableInstances = [];
        $legacyProviderAvailable = false;
        foreach ($availableChannels[$channel] ?? [] as $provider) {
            $instances = (array) ($provider['instances'] ?? []);
            if ([] === $instances) {
                $legacyProviderAvailable = true;

                continue;
            }
            foreach ($instances as $instance) {
                if (true === $instance['available']) {
                    $availableInstances[] = $instance;
                }
            }
        }

        if ([] !== array_filter($availableInstances, static function (array $instance): bool {
            return true === $instance['is_default'];
        })) {
            return true;
        }
        if (1 === count($availableInstances)) {
            return true;
        }
        if ([] !== array_filter($availableInstances, static function (array $instance): bool {
            return NotificationChannelTargetMode::FIXED !== $instance['target_mode'];
        })) {
            return true;
        }

        return $legacyProviderAvailable;
    }

    private function sceneFilterOptions(): array
    {
        $sources = [];
        $groups = [];
        foreach (NotificationScene::query()->get(['source_type', 'source_code', 'group_code', 'group_title']) as $scene) {
            $sourceKey = $scene->source_type.'|'.$scene->source_code;
            if (!isset($sources[$sourceKey])) {
                $sources[$sourceKey] = [
                    'source_type' => (string) $scene->source_type,
                    'source_code' => (string) $scene->source_code,
                    'count' => 0,
                ];
            }
            ++$sources[$sourceKey]['count'];

            $groupKey = $sourceKey.'|'.$scene->group_code;
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'source_type' => (string) $scene->source_type,
                    'source_code' => (string) $scene->source_code,
                    'code' => (string) $scene->group_code,
                    'title' => (string) $scene->group_title,
                    'count' => 0,
                ];
            }
            ++$groups[$groupKey]['count'];
        }

        $channels = NotificationTemplate::query()
            ->selectRaw('channel, count(distinct scene_id) as aggregate')
            ->where('enabled', 1)
            ->groupBy('channel')
            ->orderBy('channel')
            ->get()
            ->map(function (NotificationTemplate $template): array {
                return [
                    'code' => (string) $template->channel,
                    'title' => $this->channelTitle((string) $template->channel),
                    'count' => (int) $template->aggregate,
                ];
            })
            ->all();

        $sourceRows = array_values($sources);
        usort($sourceRows, static function (array $left, array $right): int {
            return [$left['source_type'], $left['source_code']] <=> [$right['source_type'], $right['source_code']];
        });
        $groupRows = array_values($groups);
        usort($groupRows, static function (array $left, array $right): int {
            return [$left['source_type'], $left['source_code'], $left['code']]
                <=> [$right['source_type'], $right['source_code'], $right['code']];
        });

        return [
            'sources' => $sourceRows,
            'groups' => $groupRows,
            'channels' => $channels,
            'configuration_statuses' => array_map(static function (string $code, string $title): array {
                return ['code' => $code, 'title' => $title];
            }, array_keys(NotificationSceneConfigurationStatus::titles()), NotificationSceneConfigurationStatus::titles()),
        ];
    }

    private function template(NotificationTemplate $template): array
    {
        $providers = $this->providers->forChannel((string) $template->channel);

        return [
            'id' => (int) $template->id,
            'scene_id' => (int) $template->scene_id,
            'channel' => (string) $template->channel,
            'locale' => (string) $template->locale,
            'mode' => (string) $template->mode,
            'config' => (array) $template->config,
            'customized' => (bool) $template->customized,
            'enabled' => (bool) $template->enabled,
            'capability_available' => [] !== $providers,
            'provider_count' => count($providers),
        ];
    }

    private function routeGroups(NotificationScene $scene): array
    {
        return $scene->routes
            ->sortBy('priority')
            ->groupBy('channel')
            ->map(function ($routes, string $channel): array {
                /** @var NotificationSceneRoute $first */
                $first = $routes->first();

                return [
                    'channel' => $channel,
                    'dispatch_mode' => (string) $first->dispatch_mode,
                    'strategy' => $first->strategy,
                    'revision' => (int) $first->revision,
                    'instances' => $routes->map(function (NotificationSceneRoute $route): array {
                        $registered = $this->providers->findInstance(
                            (string) $route->channel,
                            (string) $route->provider,
                            (string) $route->provider_group,
                            null === $route->addon_code ? null : (string) $route->addon_code,
                            (string) $route->instance_code
                        );

                        return [
                            'route_id' => (int) $route->id,
                            'priority' => (int) $route->priority,
                            'enabled' => 1 === (int) $route->enabled,
                            'addon_code' => null === $route->addon_code ? null : (string) $route->addon_code,
                            'group' => (string) $route->provider_group,
                            'provider' => (string) $route->provider,
                            'instance_code' => (string) $route->instance_code,
                            'instance' => $registered['instance'] ?? null,
                            'available' => null !== $registered && true === $registered['instance']['available'],
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function channelTitle(string $channel): string
    {
        $titles = [
            NotificationChannel::SITE => '站内通知',
            NotificationChannel::MAIL => '邮件',
            NotificationChannel::SMS => '短信',
            NotificationChannel::WECHAT_WORK => '企业微信',
            NotificationChannel::WECHAT_MINI_PROGRAM => '微信小程序',
            NotificationChannel::WEBHOOK => 'Webhook',
        ];

        return $titles[$channel] ?? $channel;
    }
}
