<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Services;

use PTAdmin\Admin\Support\RuntimeConfigIndex;

final class AdminFrontendBuildService
{
    public const DEFAULT_MANIFEST_URL = 'https://cloud.api.pangtou.com/storage/starter/console-build.json';

    public function syncFromManifest(
        string $packageRoot,
        string $ref = 'latest',
        string $backendVersion = ''
    ): array {
        $manifestUrl = self::DEFAULT_MANIFEST_URL;
        $manifest = $this->readJsonFromUrl($manifestUrl);
        $version = $this->resolveVersion($manifest, $ref);
        $artifact = $this->resolveArtifact($version);
        $archiveUrl = $this->normalizeArchiveUrl((string) ($artifact['url'] ?? ''), (string) ($manifest['base_url'] ?? $manifestUrl));
        if ('' === $archiveUrl) {
            throw new \RuntimeException('Admin frontend archive url is empty.');
        }

        $temporaryRoot = rtrim(sys_get_temp_dir(), \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.'ptadmin-console-build-'.uniqid();
        $archivePath = $temporaryRoot.\DIRECTORY_SEPARATOR.'console-build.zip';
        $extractPath = $temporaryRoot.\DIRECTORY_SEPARATOR.'extract';
        $targetPath = $this->bundledFrontendPath($packageRoot);

        $this->ensureDirectory($temporaryRoot);
        $this->ensureDirectory($extractPath);

        try {
            $body = $this->download($archiveUrl);
            file_put_contents($archivePath, $body);

            $sha256 = trim((string) ($artifact['sha256'] ?? ''));
            if ($this->isValidSha256($sha256)) {
                $actual = hash_file('sha256', $archivePath);
                if (!hash_equals(strtolower($sha256), strtolower((string) $actual))) {
                    throw new \RuntimeException(sprintf('Admin frontend archive sha256 mismatch. expected=%s actual=%s', $sha256, $actual));
                }
            }

            $sourcePath = $this->extractArchive($archivePath, $extractPath);
            $this->assertFrontendBuild($sourcePath);

            $this->deletePath($targetPath);
            $this->copyDirectory($sourcePath, $targetPath);
            $this->prepareBundledIndex($targetPath.\DIRECTORY_SEPARATOR.'index.html');

            $lock = [
                'name' => (string) ($manifest['name'] ?? 'console-build'),
                'version' => (string) ($version['version'] ?? $ref),
                'backend_version' => $backendVersion,
                'manifest_url' => $manifestUrl,
                'archive_url' => $archiveUrl,
                'sha256' => $this->isValidSha256($sha256) ? $sha256 : hash_file('sha256', $archivePath),
                'synced_at' => date(DATE_ATOM),
            ];
            file_put_contents(
                $targetPath.\DIRECTORY_SEPARATOR.'.release-lock.json',
                json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL
            );

            return $lock + ['path' => $targetPath];
        } finally {
            $this->deletePath($temporaryRoot);
        }
    }

    public function installBundled(string $packageRoot, string $appRoot, array $options): array
    {
        $sourcePath = $this->bundledFrontendPath($packageRoot);
        $this->assertFrontendBuild($sourcePath);

        $lock = $this->readLockFile($sourcePath);
        $version = trim((string) ($lock['version'] ?? 'bundled'));
        if ('' === $version) {
            $version = 'bundled';
        }

        $webPrefix = $this->normalizePrefix((string) ($options['web_prefix'] ?? 'admin'));
        $apiPrefix = $this->normalizePrefix((string) ($options['api_prefix'] ?? 'ptadmin'));
        $appUrl = rtrim((string) ($options['app_url'] ?? ''), '/');
        $appName = (string) ($options['app_name'] ?? 'PTAdmin');

        $publicPath = $this->publicFrontendPath($appRoot, $webPrefix);
        $modulesState = $this->publishToPublic($sourcePath, $publicPath, [
            'app_name' => $appName,
            'app_url' => $appUrl,
            'api_prefix' => $apiPrefix,
            'web_prefix' => $webPrefix,
            'version' => $version,
        ]);

        return [
            'version' => $version,
            'public_path' => $publicPath,
            'web_prefix' => $webPrefix,
            'modules' => $modulesState,
        ];
    }

    public function publishBundled(string $packageRoot, string $appRoot): array
    {
        $sourcePath = $this->bundledFrontendPath($packageRoot);
        $this->assertFrontendBuild($sourcePath);

        $webPrefix = \function_exists('admin_web_prefix') ? admin_web_prefix() : (string) config('ptadmin.web_prefix', 'admin');
        $publicPath = $this->publicFrontendPath($appRoot, $webPrefix);
        $lock = $this->readLockFile($sourcePath);
        $modulesState = $this->publishToPublic($sourcePath, $publicPath, [
            'app_name' => (string) config('app.name', 'PTAdmin'),
            'app_url' => (string) config('app.url', ''),
            'api_prefix' => \function_exists('admin_api_prefix') ? admin_api_prefix() : 'ptadmin',
            'api_url' => (string) config('ptadmin.api_url', ''),
            'web_prefix' => $webPrefix,
            'asset_url' => (string) config('ptadmin.asset_url', ''),
            'module_asset_url' => (string) config('ptadmin.module_asset_url', ''),
            'version' => (string) ($lock['version'] ?? 'bundled'),
        ]);

        return [
            'source_path' => $sourcePath,
            'public_path' => $publicPath,
            'runtime_config' => 'generated',
            'modules' => $modulesState,
        ];
    }

    public function bundledFrontendPath(string $packageRoot): string
    {
        return rtrim($packageRoot, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.'resources'.\DIRECTORY_SEPARATOR.'admin-frontend';
    }

    private function publicFrontendPath(string $appRoot, string $webPrefix): string
    {
        $prefix = $this->normalizePrefix($webPrefix);
        if ('' === $prefix) {
            throw new \RuntimeException('Admin web prefix cannot be empty when publishing frontend assets.');
        }

        return rtrim($appRoot, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.'public'.\DIRECTORY_SEPARATOR.$prefix;
    }

    /**
     * @param array<string, mixed> $runtimeOptions
     */
    private function publishToPublic(string $sourcePath, string $publicPath, array $runtimeOptions): string
    {
        $modulesPath = $publicPath.\DIRECTORY_SEPARATOR.'modules';
        $preservedModulesPath = $this->preservePath($modulesPath);
        $modulesState = null !== $preservedModulesPath ? 'preserved' : 'created';

        try {
            $this->deletePath($publicPath);
            $this->ensureDirectory(\dirname($publicPath));
            $this->copyDirectory($sourcePath, $publicPath);

            $runtimeScript = $this->writeRuntimeConfig($publicPath.\DIRECTORY_SEPARATOR.'ptconfig.js', $runtimeOptions);
            $this->writeRuntimeIndex($publicPath.\DIRECTORY_SEPARATOR.'index.html', $runtimeScript);

            $this->restorePreservedPath($preservedModulesPath, $modulesPath);
            $preservedModulesPath = null;
            $this->ensureRuntimeDirectory($modulesPath);
        } finally {
            if (null !== $preservedModulesPath) {
                $this->deletePath($preservedModulesPath);
            }
        }

        return $modulesState;
    }

    private function readJsonFromUrl(string $url): array
    {
        $body = $this->download($url);
        $payload = json_decode($body, true);
        if (!\is_array($payload)) {
            throw new \RuntimeException('Admin frontend manifest is invalid JSON.');
        }

        return $payload;
    }

    private function download(string $url): string
    {
        if ('' === trim($url)) {
            throw new \RuntimeException('Download url is empty.');
        }

        $errorMessage = '';
        if (\function_exists('curl_init')) {
            $ch = curl_init($url);
            if (false === $ch) {
                throw new \RuntimeException(sprintf('Failed to initialize curl: %s', $url));
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if (\is_string($body) && '' !== $body && $status >= 200 && $status < 300) {
                return $body;
            }

            $errorMessage = '' !== $error ? $error : sprintf('HTTP status %d', $status);
        }

        $context = stream_context_create([
            'http' => ['timeout' => 120],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $body = @file_get_contents($url, false, $context);
        if (\is_string($body) && '' !== $body) {
            return $body;
        }

        $body = $this->downloadWithCurlBinary($url);
        if (\is_string($body) && '' !== $body) {
            return $body;
        }

        throw new \RuntimeException(sprintf('Failed to download admin frontend artifact: %s%s', $url, '' !== $errorMessage ? ' ('.$errorMessage.')' : ''));
    }

    private function downloadWithCurlBinary(string $url): ?string
    {
        if (!\function_exists('proc_open')) {
            return null;
        }

        $command = 'curl -LfsS --max-time 120 '.escapeshellarg($url);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes);
        if (!\is_resource($process)) {
            return null;
        }

        fclose($pipes[0]);
        $body = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        return 0 === $status && \is_string($body) && '' !== $body ? $body : null;
    }

    private function resolveVersion(array $manifest, string $ref): array
    {
        $requested = trim($ref);
        $latest = trim((string) ($manifest['latest'] ?? ''));
        $target = \in_array($requested, ['', 'latest', 'main', 'master'], true) ? $latest : $requested;
        $versions = \is_array($manifest['versions'] ?? null) ? $manifest['versions'] : [];

        foreach ($versions as $version) {
            if (\is_array($version) && ('' === $target || $target === (string) ($version['version'] ?? ''))) {
                return $version;
            }
        }

        if ([] !== $versions && '' === $target && \is_array($versions[0] ?? null)) {
            return $versions[0];
        }

        throw new \RuntimeException(sprintf('Unable to resolve admin frontend version: %s', $target ?: $requested));
    }

    private function resolveArtifact(array $version): array
    {
        $artifacts = \is_array($version['artifacts'] ?? null) ? $version['artifacts'] : [];
        $artifact = $artifacts['primary'] ?? null;
        if (\is_array($artifact)) {
            return $artifact;
        }

        foreach ($artifacts as $candidate) {
            if (\is_array($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Admin frontend artifact is missing.');
    }

    private function normalizeArchiveUrl(string $url, string $baseUrl): string
    {
        $url = trim($url);
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if ('' === $url) {
            return '';
        }

        $base = trim($baseUrl);
        $path = (string) (parse_url($base, PHP_URL_PATH) ?: '');
        if (preg_match('#\.[a-z0-9]+$#i', $path)) {
            $base = preg_replace('#/[^/]*$#', '/', $base) ?: $base;
        }

        return rtrim($base, '/').'/'.ltrim($url, '/');
    }

    private function extractArchive(string $archivePath, string $extractPath): string
    {
        $zip = new \ZipArchive();
        if (true !== $zip->open($archivePath)) {
            throw new \RuntimeException('Unable to open admin frontend archive.');
        }
        if (true !== $zip->extractTo($extractPath)) {
            $zip->close();
            throw new \RuntimeException('Unable to extract admin frontend archive.');
        }
        $zip->close();

        $entries = array_values(array_filter(scandir($extractPath) ?: [], static fn (string $entry): bool => !\in_array($entry, ['.', '..'], true)));
        if (1 === \count($entries)) {
            $single = $extractPath.\DIRECTORY_SEPARATOR.$entries[0];
            if (is_dir($single)) {
                return $single;
            }
        }

        return $extractPath;
    }

    private function assertFrontendBuild(string $path): void
    {
        if (!is_dir($path)) {
            throw new \RuntimeException(sprintf('Admin frontend build directory does not exist: %s', $path));
        }
        foreach (['index.html', 'ptconfig.js', 'assets'] as $entry) {
            if (!file_exists($path.\DIRECTORY_SEPARATOR.$entry)) {
                throw new \RuntimeException(sprintf('Admin frontend build is missing [%s] in %s', $entry, $path));
            }
        }
    }

    private function readLockFile(string $sourcePath): array
    {
        $lockPath = $sourcePath.\DIRECTORY_SEPARATOR.'.release-lock.json';
        if (!is_file($lockPath)) {
            return [];
        }

        $payload = json_decode((string) file_get_contents($lockPath), true);

        return \is_array($payload) ? $payload : [];
    }

    private function writeRuntimeConfig(string $path, array $options): string
    {
        $existing = is_file($path) ? (string) file_get_contents($path) : "/** @type {Window['ptconfig']} */\nwindow.ptconfig = window.ptconfig || {};\n";
        $apiPrefix = $this->normalizePrefix((string) $options['api_prefix']);
        $webPrefix = $this->normalizePrefix((string) $options['web_prefix']);
        $apiBase = $this->runtimeApiBase((string) ($options['api_url'] ?? ''), $apiPrefix);
        $webBase = '/'.$webPrefix.'/';
        $assetBase = rtrim((string) ($options['asset_url'] ?? ''), '/');
        if ('' === $assetBase) {
            $assetBase = '/'.trim((string) config('ptadmin.web_asset_path', 'vendor/ptadmin/admin'), '/');
        }
        $moduleAssetBase = rtrim((string) ($options['module_asset_url'] ?? ''), '/');
        if ('' === $moduleAssetBase) {
            $moduleAssetBase = rtrim($assetBase, '/').'/modules';
        }

        $override = [
            'title' => (string) $options['app_name'],
            'shortTitle' => (string) $options['app_name'],
            'version' => (string) $options['version'],
            'coreVersion' => (string) $options['version'],
            'core_version' => (string) $options['version'],
            'baseURL' => $apiBase,
            'uploadURL' => $this->joinRuntimeUrl($apiBase, 'upload'),
            'basePath' => $webBase,
            'assetBase' => $assetBase,
            'moduleAssetBase' => $moduleAssetBase,
            'bootstrap' => [
                'loginEndpoint' => '/login',
                'profileEndpoint' => '/auth/profile',
                'frontendsEndpoint' => '/auth/frontends',
                'resourcesEndpoint' => '/auth/resources',
            ],
        ];

        $script = $existing.PHP_EOL.PHP_EOL.'window.ptconfig = Object.assign(window.ptconfig || {}, '.json_encode($override, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).');'.PHP_EOL;
        file_put_contents($path, $script);

        return $script;
    }

    private function writeRuntimeIndex(string $path, string $runtimeScript): void
    {
        if (!is_file($path)) {
            return;
        }

        $html = (string) file_get_contents($path);
        $html = RuntimeConfigIndex::inject($html, $runtimeScript);
        file_put_contents($path, $html);
    }

    private function prepareBundledIndex(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $html = (string) file_get_contents($path);
        $html = RuntimeConfigIndex::prepare($html);
        file_put_contents($path, $html);
    }

    private function copyDirectory(string $source, string $target): void
    {
        $this->copyDirectoryExcept($source, $target);
    }

    /**
     * @param array<int, string> $excludedRelativePaths
     */
    private function copyDirectoryExcept(string $source, string $target, array $excludedRelativePaths = []): void
    {
        $this->ensureDirectory($target);
        $excluded = array_fill_keys(array_map(static function (string $path): string {
            return str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, trim($path, '/\\'));
        }, $excludedRelativePaths), true);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathName();
            if (isset($excluded[$relativePath])) {
                continue;
            }

            $targetPath = $target.\DIRECTORY_SEPARATOR.$relativePath;
            if ($item->isDir()) {
                $this->ensureDirectory($targetPath);
                continue;
            }
            $this->ensureDirectory(\dirname($targetPath));
            copy($item->getPathname(), $targetPath);
        }
    }

    private function preservePath(string $path): ?string
    {
        if (!file_exists($path) && !is_link($path)) {
            return null;
        }

        $temporaryPath = \dirname(\dirname($path)).\DIRECTORY_SEPARATOR.'.'.basename(\dirname($path)).'-'.basename($path).'-preserve-'.uniqid();
        if (!@rename($path, $temporaryPath)) {
            throw new \RuntimeException(sprintf('Unable to preserve frontend runtime path: %s', $path));
        }

        return $temporaryPath;
    }

    private function restorePreservedPath(?string $preservedPath, string $targetPath): void
    {
        if (null === $preservedPath) {
            return;
        }

        $this->deletePath($targetPath);
        $this->ensureDirectory(\dirname($targetPath));

        if (!@rename($preservedPath, $targetPath)) {
            throw new \RuntimeException(sprintf('Unable to restore frontend runtime path: %s', $targetPath));
        }
    }

    private function ensureRuntimeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            $this->ensureDirectory($path);
        }

        @chmod($path, 0777);
    }

    private function deletePath(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() && !$item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($path);
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Unable to create directory: %s', $path));
        }
    }

    private function normalizePrefix(string $prefix): string
    {
        return trim($prefix, "/ \t\n\r\0\x0B");
    }

    private function runtimeApiBase(string $apiUrl, string $apiPrefix): string
    {
        $apiUrl = trim($apiUrl);
        if ('' !== $apiUrl) {
            return rtrim($apiUrl, '/').'/';
        }

        return '/'.$apiPrefix.'/';
    }

    private function joinRuntimeUrl(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    private function isValidSha256(string $value): bool
    {
        return 1 === preg_match('/^[a-f0-9]{64}$/i', $value);
    }
}
