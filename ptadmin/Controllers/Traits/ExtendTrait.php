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

namespace PTAdmin\Admin\Controllers\Traits;

use App\Exceptions\ServiceException;
use PTAdmin\Admin\Utils\ResultsVo;

trait ExtendTrait
{
    /**
     * 删除数据.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(): \Illuminate\Http\JsonResponse
    {
        $model = model_build($this->getModel());
        $model->newQuery()->whereIn($model->getKeyName(), $this->getIds())->delete();

        return ResultsVo::success();
    }

    /**
     * 恢复被删除后的数据.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(): \Illuminate\Http\JsonResponse
    {
        $model = model_build($this->getModel());
        $model->newQuery()->whereIn($model->getKeyName(), $this->getIds());
        if (method_exists($model, 'restore')) {
            $model->restore();

            return ResultsVo::success();
        }

        return ResultsVo::fail('未定义回收站恢复方法！');
    }

    /**
     * 如果状态改变是多选 则需要传递状态值，
     * 状态切换，只有0，1两种状态的时候 可以使用这个方法.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(): \Illuminate\Http\JsonResponse
    {
        $ids = $this->getIds();
        $model = model_build($this->getModel());
        $field = request()->get('field', 'status');
        $fields = $model->getTableFields();
        // 当字段不存在时需要提示错误
        if (!\in_array($field, $fields, true)) {
            throw new ServiceException("字段：【{$field}】未在数据表中定义");
        }
        if (1 === \count($ids)) {
            $id = reset($ids);
            $dao = $model->newQuery()->where($model->getKeyName(), $id)->first();
            $dao->{$field} = !$dao->{$field};
            $dao->save();

            return ResultsVo::success();
        }
        $val = request()->get('value');
        $model->newQuery()->whereIn($model->getKeyName(), $ids)->update([
            $field => $val,
        ]);

        return ResultsVo::success();
    }

    /**
     * 单字段编辑支持
     *
     * @param mixed $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editField($id): \Illuminate\Http\JsonResponse
    {
        $model = model_build($this->getModel());
        $field = request()->get('field', 'status');
        $value = request()->get('value');
        $dao = $model->newQuery()->findOrFail($id);
        $dao->fill([
            $field => $value,
        ]);
        $dao->save();

        return ResultsVo::success();
    }
}
