<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Package;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use PTAdmin\Admin\Commands\AdminBootstrapAuthCommand;
use PTAdmin\Admin\Commands\AdminInitCommand;
use PTAdmin\Admin\Http\Middleware\AuthenticateMiddleware;
use PTAdmin\Admin\Http\Middleware\AuthorizationMiddleware;
use PTAdmin\Admin\Http\Middleware\ExceptionResponseMiddleware;
use PTAdmin\Admin\Http\Middleware\OperationRecordMiddleware;
use PTAdmin\Admin\Providers\PTAdminServiceProvider;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminPackageBootstrapTest extends TestCase
{
    public function test_package_registers_config_routes_translations_and_middlewares(): void
    {
        self::assertSame('api', config('ptadmin-auth.guard'));
        self::assertSame('system', config('ptadmin-auth.api_prefix'));
        self::assertSame('admin', config('ptadmin-auth.web_prefix'));
        self::assertSame('system', admin_route_prefix());
        self::assertSame('system', admin_api_prefix());
        self::assertSame('admin', admin_web_prefix());
        self::assertSame('vendor/ptadmin/admin', admin_web_asset_path());

        $routerMiddleware = app('router')->getMiddleware();

        self::assertSame(AuthenticateMiddleware::class, $routerMiddleware['ptadmin.auth'] ?? null);
        self::assertSame(ExceptionResponseMiddleware::class, $routerMiddleware['ptadmin.response'] ?? null);
        self::assertSame(AuthorizationMiddleware::class, $routerMiddleware['ptadmin.resource'] ?? null);
        self::assertSame(OperationRecordMiddleware::class, $routerMiddleware['ptadmin.operation.record'] ?? null);

        self::assertSame('操作成功', __('ptadmin::common.success'));
        self::assertSame('登录失败，账户密码错误', __('ptadmin::background.login.fail'));
        self::assertSame('/system/login', route('admin_login', [], false));
        self::assertSame('/admin', route('ptadmin.web.index', [], false));
        self::assertSame('/admin/ptconfig.js', route('ptadmin.web.config', [], false));
    }

    public function test_package_exposes_publishable_assets_and_console_commands(): void
    {
        $allPublishes = ServiceProvider::pathsToPublish(PTAdminServiceProvider::class, 'ptadmin');
        $configPublishes = ServiceProvider::pathsToPublish(PTAdminServiceProvider::class, 'ptadmin-config');
        $migrationPublishes = ServiceProvider::pathsToPublish(PTAdminServiceProvider::class, 'ptadmin-migrations');
        $langPublishes = ServiceProvider::pathsToPublish(PTAdminServiceProvider::class, 'ptadmin-lang');
        $assetPublishes = ServiceProvider::pathsToPublish(PTAdminServiceProvider::class, 'ptadmin-assets');

        self::assertCount(11, $allPublishes);
        self::assertCount(1, $configPublishes);
        self::assertSame('ptadmin-auth.php', basename((string) array_key_first($configPublishes)));
        self::assertSame('ptadmin-auth.php', basename((string) current($configPublishes)));

        self::assertCount(8, $migrationPublishes);
        self::assertSame([
            '2026_04_09_110000_create_admin_foundation_tables.php',
            '2026_04_09_120000_create_admin_authorization_tables.php',
            '2026_04_09_130000_create_admin_authorization_extension_tables.php',
            '2026_04_09_140000_seed_admin_default_resources.php',
            '2026_04_10_120000_create_assets_table.php',
            '2024_06_13_154934_mod_init.php',
            '2026_04_06_000000_create_model_versions_table.php',
            '2026_04_06_000001_create_audit_logs_table.php',
        ], array_values(array_map('basename', array_keys($migrationPublishes))));

        self::assertCount(1, $langPublishes);
        self::assertSame('lang', basename((string) array_key_first($langPublishes)));
        self::assertSame('ptadmin', basename((string) current($langPublishes)));

        self::assertCount(1, $assetPublishes);
        self::assertSame('dist', basename((string) array_key_first($assetPublishes)));
        self::assertSame('admin', basename((string) current($assetPublishes)));
        self::assertSame($configPublishes + $migrationPublishes + $langPublishes + $assetPublishes, $allPublishes);

        $commands = Artisan::all();

        self::assertArrayHasKey('admin:auth-bootstrap', $commands);
        self::assertArrayHasKey('admin:init', $commands);
        self::assertInstanceOf(AdminBootstrapAuthCommand::class, $commands['admin:auth-bootstrap']);
        self::assertInstanceOf(AdminInitCommand::class, $commands['admin:init']);
    }
}
