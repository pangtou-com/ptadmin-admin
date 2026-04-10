<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Commands;

use Illuminate\Console\Command;
use PTAdmin\Admin\Services\Auth\AuthorizationBootstrapService;

class AdminBootstrapAuthCommand extends Command
{
    protected $signature = 'admin:auth-bootstrap
    {--role-code=super_admin : 默认角色编码}
    {--role-name=超级管理员 : 默认角色名称}
    {--assign-user-id= : 绑定到指定后台用户ID}
    {--force : 重新同步默认角色权限}';

    protected $description = '初始化默认后台角色并授予全部资源权限';

    public function handle(AuthorizationBootstrapService $bootstrapService): int
    {
        $roleCode = (string) $this->option('role-code');
        $roleName = (string) $this->option('role-name');
        $force = (bool) $this->option('force');
        $assignUserId = $this->option('assign-user-id');
        $result = $bootstrapService->bootstrap(
            $roleCode,
            $roleName,
            null !== $assignUserId && '' !== $assignUserId ? (int) $assignUserId : null,
            $force
        );

        if (null !== $result['assigned_user_id']) {
            $this->info(sprintf('角色 [%s] 已绑定到后台用户 [%d]。', $result['role']['name'], $result['assigned_user_id']));
        }

        $this->info(sprintf('默认角色 [%s] 初始化完成。', $result['role']['name']));
        $this->info(sprintf('已授予资源数量: %d', $result['resource_count']));

        return 0;
    }
}
