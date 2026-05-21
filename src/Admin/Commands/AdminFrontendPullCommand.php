<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Commands;

use Illuminate\Console\Command;
use PTAdmin\Admin\Services\AdminFrontendBuildService;

class AdminFrontendPullCommand extends Command
{
    protected $signature = 'admin:fe:pull
    {--ref=latest : 前端构建版本，默认 latest}
    {--backend-version= : 当前后端版本，用于写入锁文件}';

    protected $description = '拉取主应用后台前端构建包到 ptadmin 包内（短命令）';

    public function handle(AdminFrontendBuildService $service): int
    {
        try {
            $result = $service->syncFromManifest(
                dirname(__DIR__, 3),
                (string) $this->option('ref'),
                (string) $this->option('backend-version')
            );
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return 1;
        }

        $this->info('Admin frontend build pulled.');
        $this->line('Version: '.$result['version']);
        $this->line('Path: '.$result['path']);

        return 0;
    }
}
