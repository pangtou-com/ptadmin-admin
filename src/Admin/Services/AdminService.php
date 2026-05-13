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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PTAdmin\Admin\Models\Admin;
use PTAdmin\Admin\Models\AdminLoginLog;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Models\AdminUserRole;
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
        /** @var LengthAwarePaginator $admins */
        $admins = (new BuilderQueryApplier())->fetch(
            Admin::query(),
            is_array($search) ? $search : [],
            [
                'allowed_filters' => ['id', 'username', 'nickname', 'mobile', 'email', 'status', 'is_founder'],
                'allowed_sorts' => ['id', 'username', 'nickname', 'status', 'created_at'],
                'allowed_keyword_fields' => ['username', 'nickname', 'mobile', 'email'],
                'keyword_fields' => ['username', 'nickname', 'mobile', 'email'],
                'default_order' => ['id' => 'desc'],
                'default_limit' => 15,
            ]
        );

        $admins->setCollection($this->attachRoleDisplayToAdmins($admins->getCollection()));

        return $admins->toArray();
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

    public function loginLogs(Admin $admin, array $query = []): LengthAwarePaginator
    {
        $query = $this->normalizeLoginLogQuery($query);

        $builder = AdminLoginLog::query()
            ->leftJoin('admins', 'admins.id', '=', 'admin_login_logs.admin_id')
            ->select([
                'admin_login_logs.id',
                'admin_login_logs.admin_id',
                'admin_login_logs.login_account',
                'admin_login_logs.login_at',
                'admin_login_logs.login_ip',
                'admin_login_logs.status',
                'admin_login_logs.reason',
                'admin_login_logs.user_agent',
                'admins.username as admin_username',
                'admins.nickname as admin_nickname',
            ]);

        if (1 !== $admin->is_founder) {
            $builder->where('admin_login_logs.admin_id', $admin->id);
        }

        /** @var LengthAwarePaginator $logs */
        $logs = (new BuilderQueryApplier())->fetch(
            $builder,
            $query,
            [
                'allowed_filters' => ['admin_login_logs.admin_id', 'admin_login_logs.login_account', 'admin_login_logs.login_ip', 'admin_login_logs.status', 'admin_login_logs.reason', 'admin_login_logs.login_at', 'admins.username', 'admins.nickname'],
                'allowed_sorts' => ['admin_login_logs.id', 'admin_login_logs.admin_id', 'admin_login_logs.login_at', 'admin_login_logs.status', 'admins.username', 'admins.nickname'],
                'allowed_keyword_fields' => ['admin_login_logs.login_account', 'admin_login_logs.login_ip', 'admin_login_logs.reason', 'admin_login_logs.user_agent', 'admins.username', 'admins.nickname'],
                'keyword_fields' => ['admin_login_logs.login_account', 'admin_login_logs.login_ip', 'admin_login_logs.reason', 'admin_login_logs.user_agent', 'admins.username', 'admins.nickname'],
                'default_order' => ['admin_login_logs.id' => 'desc'],
            ]
        );

        $logs->getCollection()->transform(function (AdminLoginLog $log): array {
            return [
                'id' => $log->id,
                'admin_id' => $log->admin_id,
                'login_account' => $log->login_account,
                'login_at' => $log->login_at,
                'login_ip' => $log->login_ip,
                'status' => $log->status,
                'reason' => $log->reason,
                'user_agent' => $log->user_agent,
                'admin' => [
                    'id' => $log->admin_id,
                    'username' => $log->admin_username,
                    'nickname' => $log->admin_nickname,
                ],
            ];
        });

        return $logs;
    }

    public function updatePassword(Admin $admin, string $oldPassword, string $newPassword): void
    {
        if (!Hash::check($oldPassword, $admin->password)) {
            throw new BackgroundException(__('ptadmin::background.old_password_invalid'));
        }

        $admin->password = Hash::make($newPassword);
        $admin->update();
    }

    public function updateProfile(Admin $admin, array $data): Admin
    {
        $admin->update([
            'nickname' => $data['nickname'],
            'email' => $data['email'] ?? null,
            'mobile' => $data['mobile'] ?? null,
        ]);

        return $admin->refresh();
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

    private function attachRoleDisplayToAdmins(Collection $admins): Collection
    {
        if ($admins->isEmpty()) {
            return $admins;
        }

        $adminIds = $admins->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $rolesByAdminId = AdminUserRole::query()
            ->with('role')
            ->whereIn('user_id', $adminIds)
            ->whereNull('tenant_id')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id')
            ->map(function (Collection $userRoles): array {
                $roles = $userRoles
                    ->map(static function (AdminUserRole $userRole): ?array {
                        if (null === $userRole->role) {
                            return null;
                        }

                        return [
                            'id' => (int) $userRole->role->id,
                            'title' => (string) $userRole->role->name,
                            'code' => (string) $userRole->role->code,
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    'role_id' => $roles->first()['id'] ?? null,
                    'role_ids' => $roles->pluck('id')->values()->all(),
                    'role_names' => $roles->pluck('title')->values()->all(),
                    'roles' => $roles->all(),
                ];
            })
            ->all();

        return $admins->map(function (Admin $admin) use ($rolesByAdminId): array {
            $payload = $admin->toArray();

            if (1 === (int) $admin->is_founder) {
                $payload['role_id'] = null;
                $payload['role_ids'] = [];
                $payload['role_names'] = ['创始人'];
                $payload['roles'] = [
                    [
                        'id' => 0,
                        'title' => '创始人',
                        'code' => 'founder',
                    ],
                ];

                return $payload;
            }

            $roleDisplay = $rolesByAdminId[(int) $admin->id] ?? [
                'role_id' => null,
                'role_ids' => [],
                'role_names' => [],
                'roles' => [],
            ];

            return array_merge($payload, $roleDisplay);
        })->values();
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    private function normalizeLoginLogQuery(array $query): array
    {
        $fieldMap = [
            'id' => 'admin_login_logs.id',
            'admin_id' => 'admin_login_logs.admin_id',
            'login_account' => 'admin_login_logs.login_account',
            'login_at' => 'admin_login_logs.login_at',
            'login_ip' => 'admin_login_logs.login_ip',
            'status' => 'admin_login_logs.status',
            'reason' => 'admin_login_logs.reason',
            'user_agent' => 'admin_login_logs.user_agent',
            'admin.id' => 'admin_login_logs.admin_id',
            'admin.username' => 'admins.username',
            'admin.nickname' => 'admins.nickname',
            'username' => 'admins.username',
            'nickname' => 'admins.nickname',
        ];

        $query['filters'] = array_map(function ($filter) use ($fieldMap) {
            if (!is_array($filter) || !isset($filter['field']) || !is_string($filter['field'])) {
                return $filter;
            }

            $filter['field'] = $fieldMap[$filter['field']] ?? $filter['field'];

            return $filter;
        }, (array) ($query['filters'] ?? []));

        $query['sorts'] = array_map(function ($sort) use ($fieldMap) {
            if (!is_array($sort) || !isset($sort['field']) || !is_string($sort['field'])) {
                return $sort;
            }

            $sort['field'] = $fieldMap[$sort['field']] ?? $sort['field'];

            return $sort;
        }, (array) ($query['sorts'] ?? []));

        $query['keyword_fields'] = array_values(array_map(function ($field) use ($fieldMap) {
            return is_string($field) ? ($fieldMap[$field] ?? $field) : $field;
        }, (array) ($query['keyword_fields'] ?? [])));

        return $query;
    }
}
