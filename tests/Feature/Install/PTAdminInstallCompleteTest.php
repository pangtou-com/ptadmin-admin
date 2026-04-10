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
        self::assertStringContainsString('创建管理员失败:Init failed.', $output);
    }

    public function test_complete_writes_installed_marker_after_admin_init_succeeds(): void
    {
        $commands = [];

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
        ], function () use (&$nextCalled): void {
            $nextCalled = true;
        });
        $output = (string) ob_get_clean();

        self::assertTrue($nextCalled);
        self::assertFileExists($this->installedMarkerPath());
        self::assertStringContainsString('安装成功', $output);
        self::assertSame([
            ['admin:init', ['-u' => 'admin', '-p' => 'secret123', '-f' => true]],
            ['cache:clear', []],
            ['config:clear', []],
            ['event:clear', []],
            ['route:clear', []],
            ['view:clear', []],
            ['permission:cache-reset', []],
        ], $commands);
    }

    private function installedMarkerPath(): string
    {
        return storage_path('installed');
    }
}
