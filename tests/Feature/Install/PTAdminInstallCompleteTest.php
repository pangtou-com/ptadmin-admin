<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Install;

use Illuminate\Support\Facades\Artisan;
use PTAdmin\Admin\Services\Install\Pipe\Complete;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminInstallCompleteTest extends TestCase
{
    protected function tearDown(): void
    {
        @unlink($this->installedMarkerPath());

        parent::tearDown();
    }

    public function test_complete_stops_when_admin_init_command_fails(): void
    {
        $commands = [];

        Artisan::shouldReceive('call')
            ->andReturnUsing(function (string $command, array $arguments = []) use (&$commands): int {
                $commands[] = [$command, $arguments];

                return 'admin:init' === $command ? 1 : 0;
            });
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Init failed.');

        $nextCalled = false;
        $pipe = new Complete();

        ob_start();
        $pipe->handle([
            'username' => 'admin',
            'password' => 'secret123',
        ], function () use (&$nextCalled): void {
            $nextCalled = true;
        });
        $output = (string) ob_get_clean();

        self::assertFalse($nextCalled);
        self::assertFileDoesNotExist($this->installedMarkerPath());
        self::assertSame([
            ['admin:init', ['-u' => 'admin', '-p' => 'secret123', '-f' => true]],
        ], $commands);
        self::assertStringContainsString(__('ptadmin::install.logs.admin_create_failed', ['message' => 'Init failed.']), $output);
    }

    public function test_complete_writes_installed_marker_after_admin_init_succeeds(): void
    {
        $commands = [];
        $envPath = storage_path('install-test.env');

        Artisan::shouldReceive('all')
            ->once()
            ->andReturn([
                'permission:cache-reset' => new \stdClass(),
            ]);
        Artisan::shouldReceive('call')
            ->andReturnUsing(function (string $command, array $arguments = []) use (&$commands): int {
                $commands[] = [$command, $arguments];

                return 0;
            });
        Artisan::shouldReceive('output')
            ->never();

        $nextCalled = false;
        $pipe = new Complete();

        ob_start();
        $pipe->handle([
            'username' => 'admin',
            'password' => 'secret123',
            'app_name' => 'PTAdmin',
            'app_url' => 'http://example.test',
            'ptadmin_web_prefix' => 'admin-web',
            'ptadmin_api_prefix' => 'admin-api',
            '__install_env_path' => $envPath,
            '__install_env_content' => "APP_NAME=PTAdmin\nAPP_ENV=local\n",
        ], function () use (&$nextCalled): void {
            $nextCalled = true;
        });
        $output = (string) ob_get_clean();

        self::assertTrue($nextCalled);
        self::assertFileExists($this->installedMarkerPath());
        self::assertFileExists($envPath);
        $lock = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/resources/admin-frontend/.release-lock.json'), true);
        $frontendVersion = (string) ($lock['version'] ?? 'bundled');
        self::assertFileExists(storage_path('app/ptadmin/frontend/admin/releases/'.$frontendVersion.'/index.html'));
        self::assertTrue(is_link(public_path('admin-web')) || is_dir(public_path('admin-web')));
        self::assertStringContainsString('admin-api', (string) file_get_contents(storage_path('app/ptadmin/frontend/admin/releases/'.$frontendVersion.'/ptconfig.js')));
        self::assertSame("APP_NAME=PTAdmin\nAPP_ENV=local\n", file_get_contents($envPath));
        self::assertStringContainsString('安装成功', $output);
        self::assertStringContainsString('保存配置文件', $output);
        self::assertSame([
            ['admin:init', ['-u' => 'admin', '-p' => 'secret123', '-f' => true]],
            ['cache:clear', []],
            ['config:clear', []],
            ['event:clear', []],
            ['route:clear', []],
            ['view:clear', []],
            ['permission:cache-reset', []],
        ], $commands);

        @unlink($envPath);
        $this->deletePath(public_path('admin-web'));
        $this->deletePath(storage_path('app/ptadmin/frontend/admin'));
    }

    public function test_complete_skips_permission_cache_reset_when_command_is_unavailable(): void
    {
        $commands = [];
        $envPath = storage_path('install-test-no-permission.env');

        Artisan::shouldReceive('all')
            ->once()
            ->andReturn([]);
        Artisan::shouldReceive('call')
            ->andReturnUsing(function (string $command, array $arguments = []) use (&$commands): int {
                $commands[] = [$command, $arguments];

                return 0;
            });
        Artisan::shouldReceive('output')
            ->never();

        $pipe = new Complete();

        ob_start();
        $pipe->handle([
            'username' => 'admin',
            'password' => 'secret123',
            '__install_env_path' => $envPath,
            '__install_env_content' => "APP_NAME=PTAdmin\n",
        ], static function (): void {
        });
        ob_end_clean();

        self::assertSame([
            ['admin:init', ['-u' => 'admin', '-p' => 'secret123', '-f' => true]],
            ['cache:clear', []],
            ['config:clear', []],
            ['event:clear', []],
            ['route:clear', []],
            ['view:clear', []],
        ], $commands);

        @unlink($envPath);
        @unlink($this->installedMarkerPath());
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

    private function installedMarkerPath(): string
    {
        return storage_path('installed');
    }
}
