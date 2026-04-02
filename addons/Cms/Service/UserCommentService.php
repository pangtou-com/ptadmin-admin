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

use Addon\Cms\Enum\UserCommentStatusEnum;
use Addon\Cms\Models\UserComment;
use App\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Models\User;

class UserCommentService
{
    /**
     * 新增用户评论.
     *
     * @param $targetId
     * @param $targetType
     * @param $userId
     * @param int   $cite_target_comment_id
     * @param mixed $content
     */
    public function store($targetId, $targetType, $userId, int $cite_target_comment_id, $content): void
    {
        $new = (new $targetType())->newQuery()->findOrFail($targetId)->toArray();
        $user = User::query()->findOrFail($userId)->toArray();

        $comment = new UserComment();
        if (isset($new['parent_id']) || isset($new['target_parent_id'])) {
            $comment->target_parent_id = $new['parent_id'] ?? $new['target_parent_id'];
        }
        if (isset($new['target_parent_id']) && 0 !== $cite_target_comment_id) {
            $comment->cite_target_comment_id = $cite_target_comment_id;
        }
        $comment->user_id = $user['id'];
        $comment->nickname = $user['nickname'];
        $comment->avatar = $user['avatar'];
        $comment->target_id = $targetId;
        $comment->target_type = $targetType;
        $comment->content = $content;
        $comment->save();
    }

    /**
     * 编辑用户评论.
     *
     * @param $id
     * @param $userId
     * @param $content
     */
    public function edit($id, $userId, $content): void
    {
        $comment = UserComment::query()->findOrFail($id);
        $oldCommentStatus = $comment->status;
        if ($comment->user_id !== $userId) {
            throw new ServiceException('您没有权限操作此评论');
        }
        $User = User::query()->findOrFail($userId);
        $comment->nickname = $User['nickname'];
        $comment->avatar = $User['avatar'];
        $comment->content = $content;
        $comment->status = 0;
        DB::beginTransaction();

        try {
            $comment->save();
            $target = (new $comment['target_type']())->newQuery()->findOrFail($comment['target_id']);
            if ($oldCommentStatus) {
                $target->decrement('comment_num');
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    /**
     * 删除用户评论.
     *
     * @param $id
     * @param $userId
     */
    public function delete($id, $userId): void
    {
        $comment = UserComment::query()->findOrFail($id);
        if ($userId !== $comment->user_id) {
            throw new ServiceException('您没有权限操作此评论');
        }
        DB::beginTransaction();

        try {
            $comment->delete();
            $target = (new $comment['target_type']())->newQuery()->findOrFail($comment['target_id']);
            if (isset($target['comment_num'])) {
                $target->decrement('comment_num');
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    /**
     * 根据目标ID获取评论数量.
     *
     * @param $targetId
     * @param $targetType
     *
     * @return int
     */
    public function getCommentNumByTargetId($targetId, $targetType): int
    {
        return UserComment::query()->where('target_id', $targetId)->where('target_type', $targetType)->where('status', UserCommentStatusEnum::PASS)->count();
    }

    /**
     * 根据目标ID获取评论列表.
     *
     * @param $targetId
     * @param $targetType
     *
     * @return array
     */
    public function getCommentListByTargetId($targetId, $targetType): array
    {
        return UserComment::query()->where('target_id', $targetId)->where('target_type', $targetType)
            ->where('status', UserCommentStatusEnum::PASS)
            ->orderBy('created_at', 'desc')->paginate()->toArray();
    }

    /**
     * 根据用户ID获取评论数量.
     *
     * @param $userId
     * @param $targetType
     *
     * @return int
     */
    public function getCommentNumByUserId($userId, $targetType): int
    {
        return UserComment::query()->where('user_id', $userId)->where('target_type', $targetType)->where('status', UserCommentStatusEnum::PASS)->count();
    }

    /**
     * 根据用户ID获取评论列表.
     *
     * @param $userId
     * @param $targetType
     *
     * @return array
     */
    public function getCommentListByUserId($userId, $targetType): array
    {
        return UserComment::query()->where('user_id', $userId)->where('target_type', $targetType)
            ->where('status', UserCommentStatusEnum::PASS)
            ->orderBy('created_at', 'desc')->paginate()->toArray();
    }

    /**
     * 获取评论列表.
     *
     * @param array $search
     *
     * @return array
     */
    public function getCommentList(array $search = []): array
    {
        $filter = UserComment::query();
        if (isset($search['status']) && !blank($search['status'])) {
            $filter->where('status', $search['status']);
        }

        return $filter->orderBy('created_at', 'desc')->paginate()->toArray();
    }

    /**
     * 审核评论.
     *
     * @param $id
     * @param $status
     * @param null|string $note
     */
    public function examine($id, $status, string $note = null): void
    {
        $comment = UserComment::query()->findOrFail($id);
        if (UserCommentStatusEnum::PASS === $comment->status) {
            throw new ServiceException($id.'评论已通过审核，无需再次审核');
        }
        $comment->status = $status;
        if (isset($note) && !blank($note)) {
            $comment->note = $note;
        }
        DB::beginTransaction();

        try {
            $comment->save();
            if (UserCommentStatusEnum::PASS === $status) {
                $target = (new $comment['target_type']())->newQuery()->findOrFail($comment['target_id']);
                if (isset($target['comment_num']) && UserCommentStatusEnum::PASS === (int) $comment['status']) {
                    $target->increment('comment_num');
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }
}
