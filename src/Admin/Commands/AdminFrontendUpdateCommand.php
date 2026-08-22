<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Commands;

use Illuminate\Console\Command;
use PTAdmin\Admin\Services\AdminFrontendBuildService;

class AdminFrontendUpdateCommand extends Command
{
    protected $signature = 'admin:fe:update
    {--ref=latest : 前端构建版本，默认 latest}
    {--backend-version= : 当前后端版本，用于写入锁文件}';

    protected $description = '拉取并发布主应用后台前端构建包（短命令）';

    public function handle(AdminFrontendBuildService $service): int
    {
        try {
            $result = $service->update(
                dirname(__DIR__, 3),
                base_path(),
                (string) $this->option('ref'),
                (string) $this->option('backend-version')
            );
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return 1;
        }

        $this->info('Admin frontend build updated.');
        $this->line('Version: '.$result['version']);
        $this->line('Source: '.$result['source_path']);
        $this->line('Public: '.$result['public_path']);

        return 0;
    }
}
