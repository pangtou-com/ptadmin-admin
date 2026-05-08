<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Commands;

use Illuminate\Console\Command;
use PTAdmin\Admin\Services\AdminFrontendBuildService;

class AdminFrontendPullCommand extends Command
{
    protected $signature = 'admin:frontend:pull
    {--manifest-url=https://cloud.api.pangtou.com/storage/starter/console-build.json : 前端构建包 manifest 地址}
    {--ref=latest : 前端构建版本，默认 latest}
    {--backend-version= : 当前后端版本，用于写入锁文件}';

    protected $description = '拉取主应用后台前端构建包到 ptadmin 包内';

    public function handle(AdminFrontendBuildService $service): int
    {
        try {
            $result = $service->syncFromManifest(
                dirname(__DIR__, 3),
                (string) $this->option('manifest-url'),
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
