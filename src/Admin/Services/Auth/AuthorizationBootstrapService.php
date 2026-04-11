<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2026 重庆胖头网络技术有限公司，并保留所有权利。
 *  网站地址: https://www.pangtou.com
 *  ----------------------------------------------------------------------------
 *  尊敬的用户，
 *     感谢您对我们产品的关注与支持。我们希望提醒您，在商业用途中使用我们的产品时，请务必前往官方渠道购买正版授权。
 *  购买正版授权不仅有助于支持我们不断提供更好的产品和服务，更能够确保您在使用过程中不会引起不必要的法律纠纷。
 *  正版授权是保障您合法使用产品的最佳方式，也有助于维护您的权益和公司的声誉。我们一直致力于为客户提供高质量的解决方案，并通过正版授权机制确保产品的可靠性和安全性。
 *  如果您有任何疑问或需要帮助，我们的客户服务团队将随时为您提供支持。感谢您的理解与合作。
 *  诚挚问候，
 *  【重庆胖头网络技术有限公司】
 *  ============================================================================
 *  Author:    Zane
 *  Homepage:  https://www.pangtou.com
 *  Email:     vip@pangtou.com
 */

namespace PTAdmin\Admin\Services\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\AdminGrant;
use PTAdmin\Admin\Models\AdminResource;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Models\AdminUserRole;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Contracts\Auth\AdminGrantServiceInterface;
use PTAdmin\Contracts\Auth\AdminRoleServiceInterface;
use PTAdmin\Support\Enums\Ability;
use PTAdmin\Support\Enums\GrantEffect;
use PTAdmin\Support\ValueObjects\GrantPayload;

class AuthorizationBootstrapService
{
    private AdminGrantServiceInterface $adminGrantService;
    private AdminRoleServiceInterface $adminRoleService;

    public function __construct(AdminGrantServiceInterface $adminGrantService, AdminRoleServiceInterface $adminRoleService)
    {
        $this->adminGrantService = $adminGrantService;
        $this->adminRoleService = $adminRoleService;
    }

    public function bootstrapFounder(
        string $username,
        string $password,
        string $nickname = 'root',
        ?string $email = null,
        ?string $mobile = null,
        bool $bootstrapAuthorization = true,
        string $roleCode = 'super_admin',
        string $roleName = ''
    ): array {
        if ('' === $roleName) {
            $roleName = __('ptadmin::common.auth.super_admin_role_name');
        }

        if (Admin::query()->where('is_founder', 1)->exists()) {
            throw new BackgroundException(__('ptadmin::background.founder_exists'));
        }

        if (Admin::query()->where('username', $username)->exists()) {
            throw new BackgroundException(__('ptadmin::background.admin_username_exists'));
        }

        return DB::transaction(function () use ($username, $password, $nickname, $email, $mobile, $bootstrapAuthorization, $roleCode, $roleName): array {
            $admin = new Admin();
            $admin->username = $username;
            $admin->nickname = $nickname;
            $admin->email = $email;
            $admin->mobile = $mobile;
            $admin->status = 1;
            $admin->is_founder = 1;
            $admin->password = Hash::make($password);
            $admin->save();

            $result = [
                'founder' => [
                    'id' => $admin->id,
                    'username' => $admin->username,
                    'nickname' => $admin->nickname,
                    'is_founder' => true,
                ],
            ];

            if ($bootstrapAuthorization) {
                $result['authorization'] = $this->bootstrap($roleCode, $roleName, $admin->id);
            }

            return $result;
        });
    }

    public function bootstrap(string $roleCode = 'super_admin', string $roleName = '', ?int $assignUserId = null, bool $force = false): array
    {
        if ('' === $roleName) {
            $roleName = __('ptadmin::common.auth.super_admin_role_name');
        }

        $roleDescription = __('ptadmin::common.auth.default_role_description');

        /** @var null|AdminRole $role */
        $role = AdminRole::query()
            ->where('code', $roleCode)
            ->whereNull('deleted_at')
            ->first()
        ;
        if (null === $role) {
            $role = $this->adminRoleService->create([
                'code' => $roleCode,
                'name' => $roleName,
                'description' => $roleDescription,
                'status' => 1,
            ]);
        } else {
            $role = $this->adminRoleService->update($role->id, [
                'name' => $roleName,
                'description' => $roleDescription,
                'status' => 1,
            ]);
        }

        $resourceCodes = AdminResource::query()
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->pluck('code')
            ->filter()
            ->values()
            ->all()
        ;

        if ($force || 0 === \count($this->adminGrantService->getRoleGrants((int) $role->id))) {
            $payloads = array_map(static function (string $resourceCode): GrantPayload {
                return new GrantPayload($resourceCode, GrantEffect::ALLOW, [Ability::ACCESS]);
            }, $resourceCodes);

            $this->adminGrantService->syncRoleGrants((int) $role->id, $payloads);
        }

        $admin = null;
        if (null !== $assignUserId) {
            /** @var Admin $admin */
            $admin = Admin::query()->findOrFail($assignUserId);
            $this->adminRoleService->syncUserRoles($admin->id, [(int) $role->id]);
        }

        return [
            'role' => [
                'id' => (int) $role->id,
                'code' => (string) $role->code,
                'name' => (string) $role->name,
            ],
            'assigned_user_id' => null !== $admin ? $admin->id : null,
            'resource_count' => \count($resourceCodes),
        ];
    }

    public function status(): array
    {
        return [
            'admins' => $this->countIfTableExists('admins', Admin::class),
            'founders' => $this->countIfTableExists('admins', Admin::class, ['is_founder' => 1]),
            'admin_resources' => $this->countIfTableExists('admin_resources', AdminResource::class),
            'admin_roles' => $this->countIfTableExists('admin_roles', AdminRole::class),
            'admin_user_roles' => $this->countIfTableExists('admin_user_roles', AdminUserRole::class),
            'admin_grants' => $this->countIfTableExists('admin_grants', AdminGrant::class),
        ];
    }

    private function countIfTableExists(string $table, string $modelClass, array $where = []): int
    {
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return 0;
        }

        $query = $modelClass::query();

        foreach ($where as $field => $value) {
            $query->where($field, $value);
        }

        return (int) $query->count();
    }
}
