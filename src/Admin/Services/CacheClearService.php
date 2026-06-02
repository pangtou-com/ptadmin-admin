<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use PTAdmin\Admin\Models\AdminResource;

class CacheClearService
{
    /**
     * @var null|callable(string): int
     */
    private $commandRunner;

    /**
     * @param null|callable(string): int $commandRunner
     */
    public function __construct(?callable $commandRunner = null)
    {
        $this->commandRunner = $commandRunner;
    }

    /**
     * 清理后台可确定归属的系统缓存。
     *
     * @return array<string, mixed>
     */
    public function clear(): array
    {
        $items = [];

        $commands = [
            'laravel.cache_store' => 'cache:clear',
            'laravel.config' => 'config:clear',
            'laravel.event' => 'event:clear',
            'laravel.route' => 'route:clear',
            'laravel.view' => 'view:clear',
        ];

        foreach ($commands as $code => $command) {
            $items[] = $this->callCommand($code, $command);
        }

        if ($this->commandExists('permission:cache-reset')) {
            $items[] = $this->callCommand('permission.cache', 'permission:cache-reset');
        } else {
            $items[] = $this->skipped('permission.cache', 'permission:cache-reset command not registered');
        }

        $items[] = $this->forgetKey('admin.resource.audit_meta', AdminResource::AUDIT_META_CACHE_KEY);
        $items[] = $this->refreshSystemConfig();

        if ($this->commandExists('addon:cache-clear')) {
            $items[] = $this->callCommand('addon.manifest', 'addon:cache-clear');
        } else {
            $items[] = $this->skipped('addon.manifest', 'addon:cache-clear command not registered');
        }

        $items[] = $this->skipped('addon.cache_hooks', 'addon-provided cache clear hooks are not implemented in this version');

        return [
            'items' => $items,
            'cleared' => $this->codesByStatus($items, 'cleared'),
            'skipped' => $this->codesByStatus($items, 'skipped'),
            'failed' => $this->codesByStatus($items, 'failed'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function callCommand(string $code, string $command): array
    {
        try {
            $this->runCommand($command);

            return $this->cleared($code, ['command' => $command]);
        } catch (\Throwable $exception) {
            return $this->failed($code, $exception->getMessage(), ['command' => $command]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function forgetKey(string $code, string $key): array
    {
        try {
            Cache::forget($key);

            return $this->cleared($code);
        } catch (\Throwable $exception) {
            return $this->failed($code, $exception->getMessage());
        }
    }

    /**
     * @return array<string, string>
     */
    private function refreshSystemConfig(): array
    {
        try {
            SystemConfigService::refreshSystemConfigCache();

            return $this->cleared('system.config');
        } catch (\Throwable $exception) {
            return $this->failed('system.config', $exception->getMessage());
        }
    }

    private function commandExists(string $command): bool
    {
        if (null !== $this->commandRunner) {
            return true;
        }

        return array_key_exists($command, Artisan::all());
    }

    private function runCommand(string $command): void
    {
        if (null !== $this->commandRunner) {
            ($this->commandRunner)($command);

            return;
        }

        Artisan::call($command);
    }

    /**
     * @return array<string, string>
     */
    private function cleared(string $code, array $extra = []): array
    {
        return array_merge([
            'code' => $code,
            'status' => 'cleared',
        ], $extra);
    }

    /**
     * @return array<string, string>
     */
    private function skipped(string $code, string $message): array
    {
        return [
            'code' => $code,
            'status' => 'skipped',
            'message' => $message,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function failed(string $code, string $message, array $extra = []): array
    {
        return array_merge([
            'code' => $code,
            'status' => 'failed',
            'message' => $message,
        ], $extra);
    }

    /**
     * @param array<int, array<string, string>> $items
     *
     * @return array<int, string>
     */
    private function codesByStatus(array $items, string $status): array
    {
        return array_values(array_map(static function (array $item): string {
            return $item['code'];
        }, array_filter($items, static function (array $item) use ($status): bool {
            return ($item['status'] ?? '') === $status;
        })));
    }
}
