<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

use Illuminate\Support\Facades\Cache;
use PTAdmin\Admin\Models\NotificationScene;
use PTAdmin\Admin\Models\NotificationSceneRoute;
use PTAdmin\Admin\Models\NotificationTemplate;

final class NotificationSceneResolver
{
    /** @var NotificationTemplateRenderer */
    private $renderer;

    /** @var NotificationChannelProviderRegistry */
    private $providers;

    public function __construct(
        NotificationTemplateRenderer $renderer,
        NotificationChannelProviderRegistry $providers
    )
    {
        $this->renderer = $renderer;
        $this->providers = $providers;
    }

    public function resolve(NotificationSendRequest $request): ResolvedNotification
    {
        $scene = NotificationScene::query()
            ->where('code', $request->scene())
            ->where('enabled', 1)
            ->first();
        if (!$scene instanceof NotificationScene) {
            throw new \InvalidArgumentException('通知场景 ['.$request->scene().'] 未注册或已禁用');
        }

        [$variables, $secretNames] = $this->resolveVariables($scene, $request->variables());
        $channels = [] !== $request->channels()
            ? $request->channels()
            : array_values((array) $scene->default_channels);
        if ([] === $channels) {
            throw new \InvalidArgumentException('通知场景 ['.$scene->code.'] 未配置发送渠道');
        }

        $locale = $this->normalizeLocale((string) ($request->options()['locale'] ?? app()->getLocale()));
        $resolvedChannels = [];
        $canonicalTemplate = null;
        foreach ($channels as $channel) {
            foreach ($this->resolveRouteTargets($scene, (string) $channel) as $target) {
                $template = $this->resolveTemplate($scene, (string) $channel, $locale);
                if (!$canonicalTemplate instanceof NotificationTemplate || NotificationChannel::SITE === $channel) {
                    $canonicalTemplate = $template;
                }
                $resolvedChannels[] = $this->resolveChannel(
                    $template,
                    $target['provider'],
                    $variables,
                    [] !== $secretNames,
                    $target
                );
            }
        }

        $publicVariables = $variables;
        foreach ($secretNames as $name) {
            unset($publicVariables[$name]);
        }

        if (!$canonicalTemplate instanceof NotificationTemplate) {
            throw new \InvalidArgumentException('通知场景 ['.$scene->code.'] 没有可用模板');
        }
        $canonical = $this->resolveCanonicalContent($canonicalTemplate, $publicVariables, (string) $scene->title);
        $options = $request->options();
        unset($options['locale'], $options['channels'], $options['title'], $options['content'], $options['message']);
        $data = array_replace($publicVariables, (array) ($options['data'] ?? []));
        foreach ($secretNames as $name) {
            unset($data[$name]);
        }

        $message = array_replace($options, [
            'source_type' => (string) $scene->source_type,
            'source_code' => (string) $scene->source_code,
            'title' => $canonical['subject'],
            'content' => $canonical['content'],
            'biz_type' => (string) $scene->code,
            'data' => $data,
        ]);

        return new ResolvedNotification($message, $resolvedChannels);
    }

    private function resolveVariables(NotificationScene $scene, array $provided): array
    {
        $variables = $provided;
        $secretNames = [];

        foreach ((array) $scene->variables as $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $name = (string) ($definition['name'] ?? '');
            if ('' === $name) {
                continue;
            }
            if ((!array_key_exists($name, $variables) || $this->isMissing($variables[$name]))
                && array_key_exists('default', $definition)) {
                $variables[$name] = $definition['default'];
            }
            if (true === ($definition['secret'] ?? false)
                && array_key_exists($name, $variables)
                && !$this->isMissing($variables[$name])) {
                $secretNames[] = $name;
            }
            if (true === ($definition['required'] ?? false)
                && (!array_key_exists($name, $variables) || $this->isMissing($variables[$name]))) {
                throw new \InvalidArgumentException('通知场景 ['.$scene->code.'] 缺少必填变量 ['.$name.']');
            }
        }

        return [$variables, $secretNames];
    }

    /**
     * @param mixed $value
     */
    private function isMissing($value): bool
    {
        return null === $value || '' === $value || [] === $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveRouteTargets(NotificationScene $scene, string $channel): array
    {
        $routes = $scene->routes
            ->where('channel', $channel)
            ->where('enabled', 1)
            ->sortBy('priority')
            ->values();
        if ($routes->isEmpty()) {
            $providers = $this->providers->forChannel($channel);
            $defaults = [];
            $availableInstances = [];
            $hasInstances = false;
            foreach ($providers as $provider) {
                foreach ((array) $provider['instances'] as $instance) {
                    $hasInstances = true;
                    if (true !== $instance['available']) {
                        continue;
                    }
                    $candidate = ['provider' => $provider, 'instance' => $instance];
                    $availableInstances[] = $candidate;
                    if (true === $instance['is_default']) {
                        $defaults[] = $candidate;
                    }
                }
            }
            if ([] !== $defaults) {
                $candidate = $defaults[0];

                return [[
                    'provider' => $candidate['provider'],
                    'instance' => $candidate['instance'],
                    'route_revision' => null,
                    'strategy' => NotificationStrategy::FIXED,
                ]];
            }
            if (1 === count($availableInstances)) {
                $candidate = $availableInstances[0];

                return [[
                    'provider' => $candidate['provider'],
                    'instance' => $candidate['instance'],
                    'route_revision' => null,
                    'strategy' => NotificationStrategy::FIXED,
                ]];
            }
            if (1 < count($availableInstances)
                && [] !== array_filter($availableInstances, static function (array $candidate): bool {
                    return NotificationChannelTargetMode::FIXED !== $candidate['instance']['target_mode'];
                })) {
                $candidate = array_values(array_filter($availableInstances, static function (array $candidate): bool {
                    return NotificationChannelTargetMode::FIXED !== $candidate['instance']['target_mode'];
                }))[0];

                return [[
                    'provider' => $candidate['provider'],
                    'instance' => $candidate['instance'],
                    'route_revision' => null,
                    'strategy' => NotificationStrategy::FIXED,
                ]];
            }
            if ($hasInstances) {
                throw new \InvalidArgumentException('通知场景 ['.$scene->code.'] 的渠道 ['.$channel.'] 未配置明确投递路由');
            }

            return [[
                'provider' => $this->providers->resolve($channel),
                'instance' => null,
                'route_revision' => null,
                'strategy' => null,
            ]];
        }

        $targets = $routes->map(function (NotificationSceneRoute $route) use ($scene, $channel): array {
            $registered = $this->providers->findInstance(
                $channel,
                (string) $route->provider,
                (string) $route->provider_group,
                null === $route->addon_code ? null : (string) $route->addon_code,
                (string) $route->instance_code
            );
            if (null === $registered) {
                $registered = [
                    'provider' => [
                        'channel' => $channel,
                        'code' => (string) $route->provider,
                        'group' => (string) $route->provider_group,
                        'addon_code' => null === $route->addon_code ? null : (string) $route->addon_code,
                        'driver' => 'addon',
                        'available' => false,
                    ],
                    'instance' => [
                        'code' => (string) $route->instance_code,
                        'name' => (string) $route->instance_code,
                        'target_mode' => NotificationChannelTargetMode::DYNAMIC,
                        'available' => false,
                        'is_default' => false,
                        'management' => null,
                    ],
                ];
            }

            return [
                'provider' => $registered['provider'],
                'instance' => $registered['instance'],
                'route_revision' => (int) $route->revision,
                'strategy' => $route->strategy,
            ];
        })->values();

        $first = $routes->first();
        if (NotificationDispatchMode::FAN_OUT === $first->dispatch_mode) {
            return $targets->map(static function (array $target): array {
                $target['strategy'] = null;

                return $target;
            })->all();
        }
        if (NotificationStrategy::ROUND_ROBIN === $first->strategy && $routes->isNotEmpty()) {
            $cacheKey = 'ptadmin:notification-route:'.(int) $scene->id.':'.$channel.':'.(int) $first->revision;
            Cache::add($cacheKey, 0, 31536000);
            $index = max(0, (int) Cache::increment($cacheKey) - 1) % $routes->count();
            $target = $targets->get($index);
        } else {
            $target = $targets->first();
        }

        return [$target];
    }

    private function resolveTemplate(NotificationScene $scene, string $channel, string $locale): NotificationTemplate
    {
        $templates = NotificationTemplate::query()
            ->where('scene_id', (int) $scene->id)
            ->where('channel', $channel)
            ->where('enabled', 1)
            ->get();
        $selected = $this->selectTemplate($templates->all(), $locale);
        if (!$selected instanceof NotificationTemplate) {
            throw new \InvalidArgumentException('通知场景 ['.$scene->code.'] 的渠道 ['.$channel.'] 没有匹配语言的模板');
        }

        return $selected;
    }

    /**
     * @param array<int, NotificationTemplate> $templates
     */
    private function selectTemplate(array $templates, string $locale): ?NotificationTemplate
    {
        $fallback = $this->normalizeLocale((string) config('app.fallback_locale', 'zh-CN'));
        $locales = array_values(array_unique([
            $locale,
            explode('-', $locale)[0],
            $fallback,
            explode('-', $fallback)[0],
            'zh-CN',
        ]));

        foreach ($locales as $candidate) {
            foreach ($templates as $template) {
                if ($candidate === $template->locale) {
                    return $template;
                }
            }
        }

        return $templates[0] ?? null;
    }

    private function resolveChannel(NotificationTemplate $template, array $provider, array $variables, bool $sensitive, array $target = []): array
    {
        $config = (array) $template->config;
        $channel = (string) $template->channel;
        $reference = [
            'channel' => $channel,
            'provider' => (string) $provider['code'],
            'group' => (string) $provider['group'],
            'addon_code' => $provider['addon_code'],
            'available' => (bool) $provider['available'],
            'instance' => $target['instance'] ?? null,
            'instance_code' => $target['instance']['code'] ?? null,
            'route_revision' => $target['route_revision'] ?? null,
            'strategy' => $target['strategy'] ?? null,
        ];

        if (NotificationTemplateMode::REFERENCE === $template->mode) {
            $data = [];
            foreach ((array) ($config['variable_map'] ?? []) as $target => $source) {
                $data[(string) $target] = $variables[(string) $source] ?? null;
            }

            return array_merge($reference, [
                'template' => (string) ($config['template_key'] ?? ''),
                'sensitive' => $sensitive,
                'payload' => [
                    'subject' => null,
                    'message' => null,
                    'data' => $data,
                ],
            ]);
        }

        $format = (string) ($config['format'] ?? NotificationTemplateFormat::TEXT);

        return array_merge($reference, [
            'template' => null,
            'sensitive' => $sensitive,
            'payload' => [
                'subject' => $this->renderer->render(
                    $config['subject'] ?? null,
                    $variables,
                    NotificationTemplateFormat::JSON === $format ? NotificationTemplateFormat::TEXT : $format
                ),
                'message' => $this->renderer->render($config['content'] ?? null, $variables, $format),
                'data' => $variables,
            ],
        ]);
    }

    private function resolveCanonicalContent(NotificationTemplate $template, array $variables, string $fallbackTitle): array
    {
        if (NotificationTemplateMode::REFERENCE === $template->mode) {
            return ['subject' => $fallbackTitle, 'content' => null];
        }

        $config = (array) $template->config;
        $format = (string) ($config['format'] ?? NotificationTemplateFormat::TEXT);
        $subject = $this->renderer->render(
            $config['subject'] ?? null,
            $variables,
            NotificationTemplateFormat::JSON === $format ? NotificationTemplateFormat::TEXT : $format
        );

        return [
            'subject' => null === $subject || '' === trim($subject) ? $fallbackTitle : $subject,
            'content' => $this->renderer->render($config['content'] ?? null, $variables, $format),
        ];
    }

    private function normalizeLocale(string $locale): string
    {
        $parts = explode('-', str_replace('_', '-', trim($locale)));
        $parts[0] = strtolower($parts[0] ?? 'zh');
        if (isset($parts[1]) && 2 === strlen($parts[1])) {
            $parts[1] = strtoupper($parts[1]);
        }

        return implode('-', $parts);
    }
}
