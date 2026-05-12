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
            ['__PTADMIN_WEB_ASSET_BASE__', '__PTADMIN_APP_NAME__'],
            ['/'.trim(admin_web_asset_path(), '/').'/assets', (string) config('app.name', 'PTAdmin')],
            $html
        );

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function config(): Response
    {
        $payload = [
            'appName' => (string) config('app.name', 'PTAdmin'),
            'webBase' => admin_web_url(),
            'apiBase' => admin_api_url(),
            'assetBase' => '/'.admin_web_asset_path(),
            'loginPath' => admin_api_url('login'),
            'logoutPath' => admin_api_url('logout'),
            'uploadPath' => admin_api_url('upload'),
            'userResourcesPath' => admin_api_url('auth/resources'),
            'moduleManifestPath' => admin_api_url('auth/frontends'),
        ];

        $script = 'window.__PTADMIN__ = '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).';';

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
}
