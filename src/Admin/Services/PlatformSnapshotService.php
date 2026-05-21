<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use PTAdmin\Addon\Addon;
use PTAdmin\Addon\AddonApi;
use PTAdmin\Admin\Services\AdminFrontendBuildService;
use Throwable;

class PlatformSnapshotService
{
    private const LOCK_TIMEOUT = 600;

    /**
     * @return array<string, mixed>
     */
    public function read(): array
    {
        $path = $this->snapshotPath();
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $payload = false === $content ? null : json_decode($content, true);

        return \is_array($payload) ? $payload : [];
    }

    public function isStale(?array $snapshot = null): bool
    {
        $snapshot = \is_array($snapshot) ? $snapshot : $this->read();
        $syncedAt = strtotime((string) ($snapshot['synced_at'] ?? ''));
        if (false === $syncedAt || $syncedAt <= 0) {
            return true;
        }

        return $syncedAt + $this->ttl() <= time();
    }

    public function scheduleRefresh(): void
    {
        if (!$this->isStale() || !$this->acquireLock()) {
            return;
        }

        app()->terminating(function (): void {
            try {
                try {
                    $this->refresh();
                } catch (Throwable $exception) {
                }
            } finally {
                $this->releaseLock();
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function refresh(): array
    {
        $snapshot = [
            'synced_at' => date(DATE_ATOM),
            'source' => [
                'frontend_manifest_url' => $this->frontendManifestUrl(),
                'platform_snapshot_url' => '',
            ],
            'latest' => [
                'frontend_version' => '',
                'framework_version' => '',
            ],
            'framework' => [
                'version' => '',
                'changelog' => [],
                'security_alerts' => [],
            ],
            'addons' => [],
            'meta' => [
                'authorization_status' => 'unknown',
            ],
        ];

        $frontendManifest = $this->readJsonFromUrl($this->frontendManifestUrl());
        $snapshot['latest']['frontend_version'] = trim((string) ($frontendManifest['latest'] ?? ''));

        try {
            $platformSnapshot = AddonApi::getPlatformSnapshot();
            $snapshot = $this->mergePlatformSnapshot($snapshot, $platformSnapshot);
        } catch (Throwable $exception) {
        }

        $cloudUser = AddonApi::getCloudUserinfo();
        $snapshot['meta']['authorization_status'] = [] === $cloudUser ? 'unauthorized' : 'authorized';

        $addonPayload = $this->safeMyCloudAddons();
        if ([] !== $addonPayload) {
            $snapshot['addons'] = $this->normalizeAddonSnapshot((array) ($addonPayload['results'] ?? []));
        }

        $this->write($snapshot);

        return $snapshot;
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function mergePlatformSnapshot(array $snapshot, array $payload): array
    {
        $latest = \is_array($payload['latest'] ?? null) ? $payload['latest'] : [];
        $framework = \is_array($payload['framework'] ?? null) ? $payload['framework'] : [];
        $addons = \is_array($payload['addons'] ?? null) ? $payload['addons'] : [];

        if ('' !== trim((string) ($latest['frontend_version'] ?? ''))) {
            $snapshot['latest']['frontend_version'] = trim((string) $latest['frontend_version']);
        }
        if ('' !== trim((string) ($latest['framework_version'] ?? ''))) {
            $snapshot['latest']['framework_version'] = trim((string) $latest['framework_version']);
        }

        $snapshot['framework'] = array_merge($snapshot['framework'], [
            'version' => trim((string) ($framework['version'] ?? $snapshot['latest']['framework_version'] ?? '')),
            'changelog' => array_values(array_filter((array) ($framework['changelog'] ?? []), static function ($item): bool {
                return \is_array($item) || \is_string($item);
            })),
            'security_alerts' => array_values(array_filter((array) ($framework['security_alerts'] ?? []), static function ($item): bool {
                return \is_array($item) || \is_string($item);
            })),
        ]);

        if ([] !== $addons) {
            $snapshot['addons'] = $this->normalizeAddonSnapshot($addons);
        }

        if (\is_array($payload['meta'] ?? null)) {
            $snapshot['meta'] = array_merge($snapshot['meta'], (array) $payload['meta']);
        }

        return $snapshot;
    }

    /**
     * @param array<int, mixed> $items
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAddonSnapshot(array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $code = trim((string) ($item['code'] ?? $item['addon_code'] ?? ''));
            if ('' === $code) {
                continue;
            }

            $results[$code] = [
                'code' => $code,
                'latest_version' => trim((string) ($item['latest_version'] ?? $item['latestVersion'] ?? $item['new_version'] ?? $item['version_name'] ?? $item['version'] ?? '')),
                'authorized' => $this->normalizeAuthorizedFlag($item),
                'changelog' => array_values(array_filter((array) ($item['changelog'] ?? []), static function ($entry): bool {
                    return \is_array($entry) || \is_string($entry);
                })),
                'security_alerts' => array_values(array_filter((array) ($item['security_alerts'] ?? $item['security'] ?? []), static function ($entry): bool {
                    return \is_array($entry) || \is_string($entry);
                })),
            ];
        }

        ksort($results);

        return array_values($results);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function normalizeAuthorizedFlag(array $item): ?bool
    {
        foreach (['authorized', 'is_authorized', 'license_valid', 'purchased'] as $field) {
            if (!array_key_exists($field, $item)) {
                continue;
            }

            return filter_var($item[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function safeMyCloudAddons(): array
    {
        try {
            $payload = AddonApi::getMyAddon([]);

            return \is_array($payload) ? $payload : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonFromUrl(string $url): array
    {
        $target = trim($url);
        if ('' === $target) {
            return [];
        }

        $body = $this->download($target);
        $payload = json_decode($body, true);

        return \is_array($payload) ? $payload : [];
    }

    private function download(string $url): string
    {
        if (\function_exists('curl_init')) {
            $ch = curl_init($url);
            if (false !== $ch) {
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ]);

                $body = curl_exec($ch);
                $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if (\is_string($body) && '' !== $body && $status >= 200 && $status < 300) {
                    return $body;
                }
            }
        }

        $context = stream_context_create([
            'http' => ['timeout' => 15],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $body = @file_get_contents($url, false, $context);
        if (\is_string($body) && '' !== $body) {
            return $body;
        }

        throw new \RuntimeException(sprintf('Failed to download url: %s', $url));
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    private function write(array $snapshot): void
    {
        $path = $this->snapshotPath();
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create platform snapshot directory: %s', $directory));
        }

        file_put_contents($path, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    private function acquireLock(): bool
    {
        $path = $this->lockPath();
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return false;
        }

        $handle = @fopen($path, 'c+');
        if (!\is_resource($handle)) {
            return false;
        }

        if (!@flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return false;
        }

        $content = stream_get_contents($handle);
        $payload = \is_string($content) && '' !== trim($content) ? json_decode($content, true) : null;
        $startedAt = (int) ($payload['started_at'] ?? 0);
        if ($startedAt > 0 && $startedAt + self::LOCK_TIMEOUT > time()) {
            flock($handle, LOCK_UN);
            fclose($handle);

            return false;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode(['started_at' => time()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return true;
    }

    private function releaseLock(): void
    {
        $path = $this->lockPath();
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function snapshotPath(): string
    {
        return (string) config('ptadmin.platform_snapshot_path', storage_path('app/ptadmin/platform/snapshot.json'));
    }

    private function lockPath(): string
    {
        return dirname($this->snapshotPath()).DIRECTORY_SEPARATOR.'snapshot.lock';
    }

    private function ttl(): int
    {
        return max(60, (int) config('ptadmin.platform_snapshot_ttl', 86400));
    }

    private function frontendManifestUrl(): string
    {
        return AdminFrontendBuildService::DEFAULT_MANIFEST_URL;
    }
}
