<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2024 重庆胖头网络技术有限公司，并保留所有权利。
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

namespace PTAdmin\Admin\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\AdminLoginLog;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Support\Query\BuilderQueryApplier;
use PTAdmin\Contracts\Auth\AdminRoleServiceInterface;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Support\Enums\StatusEnum;

class AdminService
{
    private AdminRoleServiceInterface $adminRoleService;

    public function __construct(AdminRoleServiceInterface $adminRoleService)
    {
        $this->adminRoleService = $adminRoleService;
    }

    public function page($search = []): array
    {
        $model = Admin::query();

        return (new BuilderQueryApplier())->fetch(
            $model,
            is_array($search) ? $search : [],
            [
                'allowed_filters' => ['id', 'username', 'nickname', 'mobile', 'email', 'status', 'is_founder'],
                'allowed_sorts' => ['id', 'username', 'nickname', 'status', 'created_at'],
                'allowed_keyword_fields' => ['username', 'nickname', 'mobile', 'email'],
                'keyword_fields' => ['username', 'nickname', 'mobile', 'email'],
                'default_order' => ['id' => 'desc'],
                'default_limit' => 15,
            ]
        )->toArray();
    }

    public function create(array $data): Admin
    {
        return DB::transaction(function () use ($data): Admin {
            $admin = new Admin();
            $admin->password = Hash::make(trim((string) $data['password']));
            $admin->fill($data);
            $admin->save();

            $roleIds = $this->normalizeRoleIds((array) ($data['role_id'] ?? []));
            if ([] !== $roleIds) {
                $this->adminRoleService->syncUserRoles((int) $admin->id, $roleIds);
            }

            return $admin;
        });
    }

    public function update(int $id, array $data): Admin
    {
        return DB::transaction(function () use ($id, $data): Admin {
            /** @var Admin $admin */
            $admin = Admin::query()->findOrFail($id);
            if (isset($data['password']) && '' !== trim((string) $data['password'])) {
                $admin->password = Hash::make((string) $data['password']);
            }

            if (array_key_exists('role_id', $data)) {
                $this->adminRoleService->syncUserRoles((int) $admin->id, $this->normalizeRoleIds((array) $data['role_id']));
            }

            $admin->update($data);

            return $admin;
        });
    }

    public function details(int $id): array
    {
        /** @var Admin $admin */
        $admin = Admin::query()->select(['id', 'nickname', 'username'])->findOrFail($id);
        $roleIds = array_column($this->adminRoleService->getUserRoles((int) $admin->id), 'id');

        return [
            'id' => $admin->id,
            'nickname' => $admin->nickname,
            'username' => $admin->username,
            'role_ids' => $roleIds,
            'role_id' => $roleIds[0] ?? null,
            'role' => AdminRole::query()
                ->select(['id', 'name'])
                ->where('status', StatusEnum::ENABLE)
                ->whereNull('deleted_at')
                ->orderBy('sort')
                ->orderBy('id')
                ->get()
                ->map(static function (AdminRole $role): array {
                    return [
                        'id' => (int) $role->id,
                        'title' => (string) $role->name,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function syncRoles(int $id, array $roleIds): void
    {
        /** @var Admin $admin */
        $admin = Admin::query()->select(['id'])->findOrFail($id);
        $this->adminRoleService->syncUserRoles((int) $admin->id, $this->normalizeRoleIds($roleIds));
    }

    public function deleteAdmins(array $ids): void
    {
        Admin::query()->where('is_founder', 0)->whereIn('id', array_values(array_unique(array_map('intval', $ids))))->get()->each(function (Admin $admin): void {
            $admin->delete();
        });
    }

    public function updateStatus(array $ids, int $status): void
    {
        Admin::query()->where('is_founder', 0)->whereIn('id', array_values(array_unique(array_map('intval', $ids))))->update([
            'status' => $status,
        ]);
    }

    public function loginLogs(int $adminId): LengthAwarePaginator
    {
        return AdminLoginLog::query()
            ->select(['id', 'admin_id', 'login_at', 'login_ip', 'status'])
            ->where('admin_id', $adminId)
            ->with('admin:id,nickname')
            ->orderBy('id', 'desc')
            ->paginate();
    }

    public function updatePassword(Admin $admin, string $oldPassword, string $newPassword): void
    {
        if (!Hash::check($oldPassword, $admin->password)) {
            throw new BackgroundException(__('ptadmin::background.old_password_invalid'));
        }

        $admin->password = Hash::make($newPassword);
        $admin->update();
    }

    /**
     * 初始化创始人账户.
     *
     * @param $data
     */
    public static function initializeFounder($data): void
    {
        if (!app()->runningInConsole() && (!isset($data['force']) || true !== $data['force'])) {
            throw new BackgroundException(__('ptadmin::background.command_only'));
        }
        $model = Admin::query()->where('is_founder', 1)->first();
        if ($model && (!isset($data['force']) || false === $data['force'])) {
            throw new BackgroundException(__('ptadmin::background.founder_reinit_required'));
        }
        if (!$model) {
            $model = new Admin();
            $model->is_founder = 1;
        }
        $model->fill($data);
        $model->status = 1;
        $model->password = Hash::make($data['password']);
        $model->save();
    }

    private function normalizeRoleIds(array $roleIds): array
    {
        return array_values(array_unique(array_map('intval', $roleIds)));
    }
}
