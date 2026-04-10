<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Feature\Install;

use Illuminate\Support\Facades\Artisan;
use PTAdmin\Admin\Services\Install\Pipe\DatabaseInitialize;
use PTAdmin\Admin\Tests\TestCase;

class PTAdminInstallDatabaseInitializeTest extends TestCase
{
    public function test_database_initialize_stops_pipeline_when_migrate_command_returns_non_zero(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', ['--force' => true])
            ->andReturn(1);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Migration failed.');

        $pipe = new DatabaseInitialize();
        $nextCalled = false;

        ob_start();
        $pipe->handle([], function () use (&$nextCalled): void {
            $nextCalled = true;
        });
        $output = (string) ob_get_clean();

        self::assertFalse($nextCalled);
        self::assertStringContainsString('迁移命令执行失败:Migration failed.', $output);
    }

    public function test_database_initialize_continues_pipeline_when_migrate_command_succeeds(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', ['--force' => true])
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->never();

        $pipe = new DatabaseInitialize();
        $nextCalled = false;

        ob_start();
        $pipe->handle([], function () use (&$nextCalled): void {
            $nextCalled = true;
        });
        ob_end_clean();

        self::assertTrue($nextCalled);
    }

    public function test_database_initialize_reports_default_message_when_migrate_output_is_empty(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', ['--force' => true])
            ->andReturn(1);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('');

        $pipe = new DatabaseInitialize();

        ob_start();
        $pipe->handle([], static function (): void {
        });
        $output = (string) ob_get_clean();

        self::assertStringContainsString('迁移命令执行失败:迁移命令返回非零状态码: 1', $output);
    }
}
