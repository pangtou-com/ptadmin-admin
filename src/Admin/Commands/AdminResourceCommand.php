<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Commands;

use Illuminate\Console\Command;
use PTAdmin\Admin\Services\ProjectFrontendResourceService;

class AdminResourceCommand extends Command
{
    protected $signature = 'admin:resource';

    protected $description = '手动同步项目二开资源清单';

    public function handle(ProjectFrontendResourceService $resourceService): int
    {
        try {
            $count = $resourceService->sync();
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());

            return 1;
        }

        $this->info('项目二开资源同步完成');
        $this->line('清单：'.$resourceService->manifestPath());
        $this->line('资源数量：'.$count);

        return 0;
    }
}
