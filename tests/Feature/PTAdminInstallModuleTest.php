<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use PTAdmin\Admin\Http\Middleware\CanInstallMiddleware;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminInstallModuleTest extends TestCase
{
    protected function tearDown(): void
    {
        @unlink($this->installedMarkerPath());

        parent::tearDown();
    }

    public function test_install_module_is_available_before_system_is_marked_as_installed(): void
    {
        self::assertSame('7.4.0', config('ptadmin-install.core.min_php_version'));
        self::assertSame(
            CanInstallMiddleware::class,
            app(Router::class)->getMiddleware()['ptadmin.install'] ?? null
        );
        self::assertTrue(Route::has('ptadmin.install.welcome'));
        self::assertTrue(Route::has('ptadmin.install.requirements'));
        self::assertTrue(Route::has('ptadmin.install.environment'));
        self::assertTrue(Route::has('ptadmin.install.stream'));

        $this->get('/install')
            ->assertOk()
            ->assertSee('欢迎使用PTAdmin')
            ->assertDontSee('layui');

        $this->get('/install/requirements')
            ->assertOk()
            ->assertSee('环境检测')
            ->assertSee('PHP版本')
            ->assertSee('.env');

        $this->get('/install/env')
            ->assertOk()
            ->assertSee('基础信息')
            ->assertSee('install-dialog-mask')
            ->assertSee('fetch(url, {method: \'POST\', body: formData})', false);
    }

    public function test_install_routes_are_not_registered_after_system_is_installed(): void
    {
        file_put_contents($this->installedMarkerPath(), 'installed');

        $this->refreshApplication();

        self::assertFalse(Route::has('ptadmin.install.welcome'));
        self::assertFalse(Route::has('ptadmin.install.requirements'));
        self::assertFalse(Route::has('ptadmin.install.environment'));
        self::assertFalse(Route::has('ptadmin.install.stream'));

        $this->get('/install')->assertNotFound();
        $this->get('/install/requirements')->assertNotFound();
        $this->get('/install/env')->assertNotFound();
    }

    private function installedMarkerPath(): string
    {
        return storage_path('installed');
    }
}
