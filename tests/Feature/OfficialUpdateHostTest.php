<?php

declare(strict_types=1);

namespace Tests\Feature;

use Composer\InstalledVersions;
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
            ->markInstalled(ltrim((string) InstalledVersions::getPrettyVersion('ptadmin/core'), 'v'), 'admin_release_test');
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
        $validator = $this->app->make(AdminFrontendManifest::class);
        $manifest = $validator->verify(public_path('admin_release_test'));
        $bundled = $validator->verify(config('ptadmin.frontend.bundled_path'));
        foreach (['frontend_version', 'artifact_sha256', 'source_sha256', 'git_commit'] as $field) {
            self::assertSame($bundled[$field], $manifest[$field]);
        }
    }
}
