<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Commands;

use Illuminate\Console\Command;
use PTAdmin\Admin\Services\AdminFrontendBuildService;

class AdminFrontendUpdateCommand extends Command
{
    protected $signature = 'admin:fe:update
    {--manifest-url=https://cloud.api.pangtou.com/storage/starter/console-build.json : 前端构建包 manifest 地址}
    {--ref=latest : 前端构建版本，默认 latest}
    {--backend-version= : 当前后端版本，用于写入锁文件}';

    protected $description = '拉取并发布主应用后台前端构建包（短命令）';

    public function handle(AdminFrontendBuildService $service): int
    {
        try {
            $pulled = $service->syncFromManifest(
                dirname(__DIR__, 3),
                (string) $this->option('manifest-url'),
                (string) $this->option('ref'),
                (string) $this->option('backend-version')
            );
            $published = $service->publishBundled(dirname(__DIR__, 3), base_path());
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return 1;
        }

        $this->info('Admin frontend build updated.');
        $this->line('Version: '.$pulled['version']);
        $this->line('Source: '.$published['source_path']);
        $this->line('Current: '.$published['current_path']);

        return 0;
    }
}
