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
use PTAdmin\Support\Enums\StatusEnum;
use PTAdmin\Foundation\Exceptions\BackgroundException;
use PTAdmin\Admin\Models\AdminRole;
use PTAdmin\Admin\Models\System;
use PTAdmin\Admin\Models\SystemLog;
use PTAdmin\Contracts\Auth\AdminRoleServiceInterface;

class SystemService
{
    private AdminRoleServiceInterface $adminRoleService;

    public function __construct(AdminRoleServiceInterface $adminRoleService)
    {
        $this->adminRoleService = $adminRoleService;
    }

    public function page($search = []): array
    {
        $model = System::query();

        return $model->orderBy('id', 'desc')->paginate()->toArray();
    }

    public function create(array $data): System
    {
        return DB::transaction(function () use ($data): System {
            $system = new System();
            $system->password = Hash::make(trim((string) $data['password']));
            $system->fill($data);
            $system->save();

            $roleIds = $this->normalizeRoleIds((array) ($data['role_id'] ?? []));
            if ([] !== $roleIds) {
                $this->adminRoleService->syncUserRoles((int) $system->id, $roleIds);
            }

            return $system;
        });
    }

    public function update(int $id, array $data): System
    {
        return DB::transaction(function () use ($id, $data): System {
            /** @var System $system */
            $system = System::query()->findOrFail($id);
            if (isset($data['password']) && '' !== trim((string) $data['password'])) {
                $system->password = Hash::make((string) $data['password']);
            }

            if (array_key_exists('role_id', $data)) {
                $this->adminRoleService->syncUserRoles((int) $system->id, $this->normalizeRoleIds((array) $data['role_id']));
            }

            $system->update($data);

            return $system;
        });
    }

    public function details(int $id): array
    {
        /** @var System $system */
        $system = System::query()->select(['id', 'nickname', 'username'])->findOrFail($id);
        $roleIds = array_column($this->adminRoleService->getUserRoles((int) $system->id), 'id');

        return [
            'id' => $system->id,
            'nickname' => $system->nickname,
            'username' => $system->username,
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
        /** @var System $system */
        $system = System::query()->select(['id'])->findOrFail($id);
        $this->adminRoleService->syncUserRoles((int) $system->id, $this->normalizeRoleIds($roleIds));
    }

    public function deleteSystems(array $ids): void
    {
        System::query()->where('is_founder', 0)->whereIn('id', array_values(array_unique(array_map('intval', $ids))))->get()->each(function (System $system): void {
            $system->delete();
        });
    }

    public function updateStatus(array $ids, int $status): void
    {
        System::query()->where('is_founder', 0)->whereIn('id', array_values(array_unique(array_map('intval', $ids))))->update([
            'status' => $status,
        ]);
    }

    public function loginLogs(int $systemId): LengthAwarePaginator
    {
        return SystemLog::query()
            ->select(['id', 'system_id', 'login_at', 'login_ip', 'status'])
            ->where('system_id', $systemId)
            ->with('system:id,nickname')
            ->orderBy('id', 'desc')
            ->paginate();
    }

    public function updatePassword(System $system, string $oldPassword, string $newPassword): void
    {
        if (!Hash::check($oldPassword, $system->password)) {
            throw new BackgroundException('原密码错误');
        }

        $system->password = Hash::make($newPassword);
        $system->update();
    }

    /**
     * 初始化创始人账户.
     *
     * @param $data
     */
    public static function initializeFounder($data): void
    {
        if (!app()->runningInConsole() && (!isset($data['force']) || true !== $data['force'])) {
            throw new BackgroundException('请在命令行模式下运行');
        }
        $model = System::query()->where('is_founder', 1)->first();
        if ($model && (!isset($data['force']) || false === $data['force'])) {
            throw new BackgroundException('已有创始人账户，如需重新初始化请使用 --force|-f 参数');
        }
        if (!$model) {
            $model = new System();
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
