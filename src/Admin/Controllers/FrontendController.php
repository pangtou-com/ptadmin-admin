<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Controllers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class FrontendController
{
    /**
     * 输出后台前端入口页。
     * 发布后的静态资源使用固定目录，页面入口与接口前缀由运行时配置注入。
     *
     * @throws FileNotFoundException
     */
    public function index(): Response
    {
        $html = File::get($this->resolveDistPath('index.html'));
        $html = str_replace(
            ['__PTADMIN_WEB_ASSET_BASE__', '__PTADMIN_APP_NAME__', '__PTADMIN_CONFIG_URL__'],
            [$this->assetBaseUrl('assets'), (string) config('app.name', 'PTAdmin'), admin_web_url('ptconfig.js')],
            $html
        );

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function config(): Response
    {
        $appName = (string) config('app.name', 'PTAdmin');
        $apiBase = $this->absoluteUrl(admin_api_url());
        $webBase = $this->absoluteUrl(admin_web_url());
        $assetBase = $this->assetBaseUrl();
        $moduleAssetBase = $this->moduleAssetBaseUrl();

        $ptconfig = [
            'title' => $appName,
            'shortTitle' => $appName,
            'baseURL' => $this->ensureTrailingSlash($apiBase),
            'uploadURL' => $this->joinUrl($apiBase, 'upload'),
            'basePath' => $this->ensureTrailingSlash(admin_web_url()),
            'assetBase' => $assetBase,
            'moduleAssetBase' => $moduleAssetBase,
            'bootstrap' => [
                'loginEndpoint' => '/login',
                'profileEndpoint' => '/auth/profile',
                'frontendsEndpoint' => '/auth/frontends',
                'resourcesEndpoint' => '/auth/resources',
            ],
        ];

        $payload = [
            'appName' => (string) config('app.name', 'PTAdmin'),
            'webBase' => $webBase,
            'apiBase' => $this->ensureTrailingSlash($apiBase),
            'assetBase' => $assetBase,
            'moduleAssetBase' => $moduleAssetBase,
            'loginPath' => $this->joinUrl($apiBase, 'login'),
            'logoutPath' => $this->joinUrl($apiBase, 'logout'),
            'uploadPath' => $this->joinUrl($apiBase, 'upload'),
            'userResourcesPath' => $this->joinUrl($apiBase, 'auth/resources'),
            'moduleManifestPath' => $this->joinUrl($apiBase, 'auth/frontends'),
        ];

        $script = implode("\n", [
            'window.ptconfig = window.ptconfig || {};',
            'window.ptconfig.bootstrap = Object.assign(window.ptconfig.bootstrap || {}, '.json_encode($ptconfig['bootstrap'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).');',
            'window.ptconfig = Object.assign(window.ptconfig, '.json_encode(array_diff_key($ptconfig, ['bootstrap' => true]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).');',
            'window.__PTADMIN__ = Object.assign(window.__PTADMIN__ || {}, '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).');',
        ])."\n";

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function resolveDistPath(string $file): string
    {
        $published = public_path(admin_web_asset_path().DIRECTORY_SEPARATOR.$file);
        if (File::exists($published)) {
            return $published;
        }

        return __DIR__.'/../../../resources/admin-frontend/'.$file;
    }

    private function assetBaseUrl(string $path = ''): string
    {
        $configured = trim((string) config('ptadmin.asset_url', ''));
        $base = '' !== $configured ? rtrim($configured, '/') : '/'.trim(admin_web_asset_path(), '/');

        return $this->joinUrl($base, $path);
    }

    private function moduleAssetBaseUrl(string $path = ''): string
    {
        $configured = trim((string) config('ptadmin.module_asset_url', ''));
        $base = '' !== $configured ? rtrim($configured, '/') : $this->assetBaseUrl('modules');

        return $this->joinUrl($base, $path);
    }

    private function absoluteUrl(string $path): string
    {
        $baseUrl = rtrim((string) config('app.url', ''), '/');
        if ('' === $baseUrl && request()) {
            $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        }

        if ('' === $baseUrl || preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }

    private function joinUrl(string $base, string $path = ''): string
    {
        $base = rtrim($base, '/');
        $path = trim($path, '/');

        if ('' === $path) {
            return $base;
        }

        return $base.'/'.$path;
    }

    private function ensureTrailingSlash(string $url): string
    {
        return rtrim($url, '/').'/';
    }
}
