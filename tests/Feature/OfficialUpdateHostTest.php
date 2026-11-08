<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use PTAdmin\Core\Install\AdminFrontendManifest;
use PTAdmin\Core\Install\AdminFrontendPublisher;
use PTAdmin\Core\Install\InstallStateStore;
use Tests\TestCase;

final class OfficialUpdateHostTest extends TestCase
{
    private string $temporaryStorage;

    public function createApplication(): Application
    {
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->temporaryStorage = sys_get_temp_dir().'/ptadmin-admin-updates-'.bin2hex(random_bytes(6));
        foreach (['framework/cache', 'framework/sessions', 'framework/views', 'logs', 'public', 'bootstrap/cache'] as $directory) {
            mkdir($this->temporaryStorage.'/'.$directory, 0700, true);
        }
        $app->useStoragePath($this->temporaryStorage);
        $app->usePublicPath($this->temporaryStorage.'/public');
        $app->useBootstrapPath($this->temporaryStorage.'/bootstrap');
        (new InstallStateStore($this->temporaryStorage.'/app/ptadmin/install'))
            ->markInstalled('0.5.0', 'admin_release_test');
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        (new Filesystem())->deleteDirectory($this->temporaryStorage);
    }

    public function test_installed_host_exposes_update_commands_and_publishes_a_compatible_shell(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('ptadmin.updates.install');
        self::assertNotNull($route);
        self::assertSame(['POST'], $route->methods());
        self::assertContains(\PTAdmin\Core\Http\Middleware\AuthenticateSessionMiddleware::class, $this->app['router']->gatherRouteMiddleware($route));
        self::assertArrayHasKey('ptadmin:updates:install', $this->app->make(Kernel::class)->all());
        $this->app->make(AdminFrontendPublisher::class)->publishForInstall('admin_release_test');
        $manifest = (new AdminFrontendManifest('0.5.0', 1, 1))->verify(public_path('admin_release_test'));
        self::assertSame('0.4.0', $manifest['frontend_version']);
        self::assertSame('5421b5e74d5563b80b80f00489799b9ba546900a82dee950e8f960e5f6585f0e', $manifest['artifact_sha256']);
    }
}
