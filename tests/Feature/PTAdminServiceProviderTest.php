<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use PTAdmin\Admin\Http\Middleware\AuthenticateMiddleware;
use PTAdmin\Admin\Http\Middleware\AuthorizationMiddleware;
use PTAdmin\Admin\Http\Middleware\ExceptionResponseMiddleware;
use PTAdmin\Admin\Http\Middleware\OperationRecordMiddleware;
use PTAdmin\Admin\Providers\PTAdminServiceProvider;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminServiceProviderTest extends TestCase
{
    public function test_provider_registers_config_routes_and_middlewares(): void
    {
        self::assertSame('api', config('ptadmin-auth.guard'));
        self::assertSame('system', config('ptadmin-auth.api_prefix'));
        self::assertSame('admin', config('ptadmin-auth.web_prefix'));

        $middleware = app(Router::class)->getMiddleware();

        self::assertSame(AuthenticateMiddleware::class, $middleware['ptadmin.auth'] ?? null);
        self::assertSame(ExceptionResponseMiddleware::class, $middleware['ptadmin.response'] ?? null);
        self::assertSame(AuthorizationMiddleware::class, $middleware['ptadmin.resource'] ?? null);
        self::assertSame(OperationRecordMiddleware::class, $middleware['ptadmin.operation.record'] ?? null);
        self::assertSame('操作成功', __('ptadmin::common.success'));
        self::assertSame('/system/login', route('admin_login', [], false));
        self::assertSame('/admin', route('ptadmin.web.index', [], false));
    }

    public function test_provider_registers_publishable_assets(): void
    {
        $configPublishes = ServiceProvider::pathsToPublish(PTAdminServiceProvider::class, 'ptadmin-config');
        $migrationPublishes = ServiceProvider::pathsToPublish(PTAdminServiceProvider::class, 'ptadmin-migrations');
        $langPublishes = ServiceProvider::pathsToPublish(PTAdminServiceProvider::class, 'ptadmin-lang');
        $assetPublishes = ServiceProvider::pathsToPublish(PTAdminServiceProvider::class, 'ptadmin-assets');

        self::assertCount(1, $configPublishes);
        self::assertSame('ptadmin-auth.php', basename((string) array_key_first($configPublishes)));
        self::assertSame('ptadmin-auth.php', basename((string) current($configPublishes)));

        self::assertCount(5, $migrationPublishes);
        self::assertSame([
            '2026_04_09_110000_create_admin_foundation_tables.php',
            '2026_04_09_120000_create_admin_authorization_tables.php',
            '2026_04_09_130000_create_admin_authorization_extension_tables.php',
            '2026_04_09_140000_seed_admin_default_resources.php',
            '2026_04_10_120000_rename_attachments_to_assets.php',
        ], array_values(array_map('basename', array_keys($migrationPublishes))));

        self::assertCount(1, $langPublishes);
        self::assertSame('lang', basename((string) array_key_first($langPublishes)));
        self::assertSame('ptadmin', basename((string) current($langPublishes)));

        self::assertCount(1, $assetPublishes);
        self::assertSame('dist', basename((string) array_key_first($assetPublishes)));
        self::assertSame('admin', basename((string) current($assetPublishes)));
    }
}
