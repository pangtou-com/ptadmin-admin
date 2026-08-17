<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

use PTAdmin\Addon\Addon;
use PTAdmin\Addon\Service\InjectPayload;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

final class NotificationChannelProviderRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $providers = array_merge($this->builtinProviders(), $this->addonProviders());

        usort($providers, static function (array $left, array $right): int {
            return [$left['channel'], $left['driver'], $left['addon_code'] ?? '', $left['code']]
                <=> [$right['channel'], $right['driver'], $right['addon_code'] ?? '', $right['code']];
        });

        return $providers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forChannel(string $channel): array
    {
        $channel = NotificationChannel::normalize($channel);

        return array_values(array_filter($this->all(), static function (array $provider) use ($channel): bool {
            return $channel === $provider['channel'];
        }));
    }

    public function hasChannel(string $channel): bool
    {
        return [] !== $this->forChannel($channel);
    }

    /**
     * 兼容未声明实例协议的旧 Provider，以及站内通知和内置邮件。
     *
     * @return array<string, mixed>
     */
    public function resolve(string $channel): array
    {
        $providers = $this->forChannel($channel);
        if ([] === $providers) {
            throw new \InvalidArgumentException('通知渠道 ['.$channel.'] 没有已安装并启用的实现');
        }

        $preferredCode = (string) config('ptadmin.notifications.providers.'.$channel.'.code', '');
        if ('' !== $preferredCode) {
            foreach ($providers as $provider) {
                if ($preferredCode === $provider['code']) {
                    return $provider;
                }
            }
        }

        foreach ($providers as $provider) {
            if (true === $provider['available']) {
                return $provider;
            }
        }

        return $providers[0];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $code, ?string $group = null, ?string $addonCode = null, ?string $channel = null): ?array
    {
        foreach ($this->all() as $provider) {
            if ($code !== $provider['code']) {
                continue;
            }
            if (null !== $group && $group !== $provider['group']) {
                continue;
            }
            if (null !== $addonCode && $addonCode !== ($provider['addon_code'] ?? null)) {
                continue;
            }
            if (null !== $channel && $channel !== $provider['channel']) {
                continue;
            }

            return $provider;
        }

        return null;
    }

    /**
     * @return array{provider: array<string, mixed>, instance: array<string, mixed>}|null
     */
    public function findInstance(
        string $channel,
        string $providerCode,
        string $group,
        ?string $addonCode,
        string $instanceCode
    ): ?array {
        $provider = $this->find($providerCode, $group, $addonCode, NotificationChannel::normalize($channel));
        if (null === $provider) {
            return null;
        }

        foreach ((array) $provider['instances'] as $instance) {
            if ($instanceCode === ($instance['code'] ?? null)) {
                return ['provider' => $provider, 'instance' => $instance];
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function builtinProviders(): array
    {
        $mailConfigured = '' !== trim((string) system_config('mail.host', ''));

        return [[
            'channel' => NotificationChannel::SITE,
            'code' => NotificationChannel::SITE,
            'title' => '站内通知',
            'group' => 'internal',
            'addon_code' => null,
            'driver' => 'site',
            'available' => true,
            'configured' => true,
            'config_source' => 'internal',
            'instances' => [],
        ], [
            'channel' => NotificationChannel::MAIL,
            'code' => NotificationChannel::MAIL,
            'title' => '内置邮件',
            'group' => 'internal',
            'addon_code' => null,
            'driver' => 'mail',
            'available' => $mailConfigured,
            'configured' => $mailConfigured,
            'config_source' => 'system.mail',
            'instances' => [],
        ]];
    }

    /**
     * `notify` 能力的 types 表示它实现的通知渠道；独立 `sms` 能力天然实现 sms 渠道。
     *
     * @return array<int, array<string, mixed>>
     */
    private function addonProviders(): array
    {
        $providers = [];
        foreach (['notify', 'sms'] as $group) {
            foreach ($this->addonDefinitions($group) as $definition) {
                $channels = 'sms' === $group
                    ? [NotificationChannel::SMS]
                    : $this->notificationTypes((array) ($definition['type'] ?? []));

                foreach ($channels as $channel) {
                    $code = (string) $definition['code'];
                    $addonCode = (string) $definition['addon_code'];
                    $available = $this->definitionAvailable($definition, ['channel' => $channel]);
                    $instances = $this->definitionInstances($definition, $channel, $available);
                    $providers[] = [
                        'channel' => $channel,
                        'code' => $code,
                        'title' => (string) ($definition['title'] ?? $code),
                        'group' => $group,
                        'addon_code' => $addonCode,
                        'driver' => 'addon',
                        'available' => $available,
                        'configured' => [] === $instances ? $available : [] !== array_filter(
                            $instances,
                            static function (array $instance): bool {
                                return true === $instance['available'];
                            }
                        ),
                        'config_source' => 'addon.'.$addonCode,
                        'instances' => $instances,
                    ];
                }
            }
        }

        return $providers;
    }

    /**
     * @param array<int, mixed> $types
     * @return array<int, string>
     */
    private function notificationTypes(array $types): array
    {
        $channels = [];
        foreach ($types as $type) {
            $type = trim((string) $type);
            if ('template' === $type || 1 !== preg_match('/\A[a-z][a-z0-9_-]{0,49}\z/', $type)) {
                continue;
            }
            $channels[$type] = $type;
        }

        return array_values($channels);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function addonDefinitions(string $group): array
    {
        $definitions = [];
        foreach (array_keys(Addon::getAddons()) as $addonCode) {
            foreach ((array) (Addon::getInject($addonCode)[$group] ?? []) as $definition) {
                if (is_array($definition)) {
                    $definitions[] = ['addon_code' => $addonCode] + $definition;
                }
            }
        }

        return $definitions;
    }

    /**
     * 插件没有 readiness 方法时按可用处理，兼容既有 inject 能力。
     *
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $context
     */
    private function definitionAvailable(array $definition, array $context): bool
    {
        try {
            $instance = app((string) ($definition['class'] ?? ''));
            if (!method_exists($instance, 'ready')) {
                return true;
            }

            $reflection = new ReflectionMethod($instance, 'ready');
            if (0 === $reflection->getNumberOfParameters()) {
                return true === $instance->ready();
            }

            $type = $reflection->getParameters()[0]->getType();
            if ($type instanceof ReflectionNamedType && 'array' === $type->getName()) {
                return true === $instance->ready($context);
            }

            return true === $instance->ready(InjectPayload::make($context));
        } catch (Throwable $throwable) {
            return false;
        }
    }

    /**
     * 实例摘要完全由插件提供。通知内核只做字段归一化，不保存插件配置。
     *
     * @param array<string, mixed> $definition
     * @return array<int, array<string, mixed>>
     */
    private function definitionInstances(array $definition, string $channel, bool $providerAvailable): array
    {
        try {
            $handler = app((string) ($definition['class'] ?? ''));
            if (!method_exists($handler, 'instances')) {
                return [];
            }

            $reflection = new ReflectionMethod($handler, 'instances');
            $declared = 0 === $reflection->getNumberOfParameters()
                ? (array) $handler->instances()
                : (array) $handler->instances($channel);
        } catch (Throwable $throwable) {
            return [];
        }

        $instances = [];
        foreach ($declared as $instance) {
            if (!is_array($instance)) {
                continue;
            }
            $code = (string) ($instance['code'] ?? '');
            if (1 !== preg_match('/\A[a-z][a-z0-9_-]{0,99}\z/', $code)) {
                continue;
            }
            $channels = array_values((array) ($instance['channels'] ?? []));
            if ([] !== $channels && !in_array($channel, $channels, true)) {
                continue;
            }
            $targetMode = (string) ($instance['target_mode'] ?? NotificationChannelTargetMode::DYNAMIC);
            if (!in_array($targetMode, NotificationChannelTargetMode::all(), true)) {
                $targetMode = NotificationChannelTargetMode::DYNAMIC;
            }
            $management = $instance['management'] ?? null;
            if (!is_array($management) || !isset($management['type'], $management['key'])) {
                $management = null;
            } else {
                $management = [
                    'type' => (string) $management['type'],
                    'key' => (string) $management['key'],
                ];
            }

            $instances[$code] = [
                'code' => $code,
                'name' => (string) ($instance['name'] ?? $code),
                'target_mode' => $targetMode,
                'available' => $providerAvailable && true === ($instance['available'] ?? true),
                'is_default' => true === ($instance['is_default'] ?? false),
                'management' => $management,
            ];
        }

        $instances = array_values($instances);
        usort($instances, static function (array $left, array $right): int {
            return [(int) !$left['is_default'], $left['code']]
                <=> [(int) !$right['is_default'], $right['code']];
        });

        return $instances;
    }
}
