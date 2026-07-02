<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Commands;

use Illuminate\Console\Command;

class AdminUpgradeCommand extends Command
{
    protected $signature = 'admin:upgrade
    {--ref=latest : 前端构建版本，默认 latest}
    {--backend-version= : 当前后端版本，用于写入锁文件}
    {--skip-migrate : 跳过数据库迁移}
    {--skip-frontend : 跳过后台前端壳更新}';

    protected $description = '执行 PTAdmin Composer 包升级后的应用收尾动作';

    public function handle(): int
    {
        $steps = [
            ['optimize:clear', []],
        ];

        if (!$this->option('skip-migrate')) {
            $steps[] = ['migrate', ['--force' => true]];
        }

        $steps[] = ['admin:fix', []];

        if (!$this->option('skip-frontend')) {
            $arguments = [
                '--ref' => (string) $this->option('ref'),
            ];
            $backendVersion = (string) $this->option('backend-version');
            if ('' !== $backendVersion) {
                $arguments['--backend-version'] = $backendVersion;
            }
            $steps[] = ['admin:fe:update', $arguments];
        }

        $steps[] = ['optimize:clear', []];

        foreach ($steps as $step) {
            [$command, $arguments] = $step;
            $this->line(sprintf('Running: php artisan %s', $command));
            $exitCode = $this->call($command, $arguments);
            if (0 !== $exitCode) {
                $this->error(sprintf('PTAdmin upgrade stopped at [%s].', $command));

                return (int) $exitCode;
            }
        }

        $this->info('PTAdmin upgrade completed.');

        return 0;
    }
}
