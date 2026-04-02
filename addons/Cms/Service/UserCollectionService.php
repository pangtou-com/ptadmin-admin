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

namespace Addon\Cms\Service;

use Addon\Cms\Models\UserCollection;
use App\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Enum\StatusEnum;

class UserCollectionService
{
    public function store($targetId, $targetType, $userId): void
    {
        DB::beginTransaction();

        try {
            $model = UserCollection::query()->where('user_id', $userId)->where('target_id', $targetId)->where('target_type', $targetType)->first();
            if ($model) {
                $model->status = (int) !$model->status;
            } else {
                $model = new UserCollection();
                $model->user_id = $userId;
                $model->target_id = $targetId;
                $model->target_type = $targetType;
                $model->status = StatusEnum::ENABLE;
            }
            $model->save();
            $this->after($targetId, $targetType, $model);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    public function pages($userId, $targetType): array
    {
        return UserCollection::query()
            ->where('user_id', $userId)
            ->where('target_type', $targetType)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')->paginate()->toArray();
    }

    /**
     * 收藏后操作.
     *
     * @param $targetId
     * @param $targetType
     * @param $dao
     */
    public function after($targetId, $targetType, $dao): void
    {
        // 收藏数量增加或减少
        $target = (new $targetType())->newQuery()->findOrFail($targetId);
        if (StatusEnum::ENABLE === (int) $dao->status) {
            $target->increment('collection_num');
        } else {
            $target->decrement('collection_num');
        }
    }
}
