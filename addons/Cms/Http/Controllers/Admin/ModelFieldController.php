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

namespace Addon\Cms\Http\Controllers\Admin;

use App\Exceptions\CommonExceptionConstants;
use App\Exceptions\ServiceException;
use Illuminate\Http\Request;
use PTAdmin\Admin\Controllers\Admin\AbstractBackgroundController;
use PTAdmin\Admin\Utils\ResultsVo;
use PTAdmin\Easy\Easy;
use PTAdmin\Easy\Model\Mod;
use PTAdmin\Easy\Model\ModField;

class ModelFieldController extends AbstractBackgroundController
{
    public function index(Request $request, $id)
    {
        if ($request->expectsJson()) {
            $results = Easy::field()->lists($request->all(), $id);

            return ResultsVo::pages($results);
        }
        $mod = Easy::mod()->find($id);

        return $this->view(compact('id', 'mod'));
    }

    public function store(Request $request)
    {
        if ($request->expectsJson()) {
            $this->checkModIsPublish($request->input('mod_id', 0));
            Easy::field()->store($request->all());

            return ResultsVo::success();
        }
        $mod_id = (int) $request->input('mod_id');

        return $this->view(compact('mod_id'));
    }

    public function edit(Request $request, $id)
    {
        if ($request->expectsJson()) {
            $this->checkModIsPublish($request->input('mod_id', 0));
            Easy::field()->edit($request->all(), $id);

            return ResultsVo::success();
        }
        /** @var ModField $dao */
        $dao = Easy::field()->find($id);
        $mod_id = $dao->mod_id;

        return $this->view(compact('dao', 'mod_id'));
    }

    public function status(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        /** @var ModField $field */
        $field = Easy::field()->find($id);
        $this->checkModIsPublish($field->mod_id);
        $value = (int) $request->get('value');
        Easy::field()->setStatus($id, $value);

        return ResultsVo::success();
    }

    public function restore(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $mod_id = (int) $request->input('mod_id');
        $this->checkModIsPublish($mod_id);
        Easy::field()->restore($id);

        return ResultsVo::success();
    }

    public function del($id): \Illuminate\Http\JsonResponse
    {
        /** @var ModField $field */
        $field = Easy::field()->find($id);
        $this->checkModIsPublish($field->mod_id);
        Easy::field()->delete($id);

        return ResultsVo::success();
    }

    public function thoroughDel($id): \Illuminate\Http\JsonResponse
    {
        Easy::field()->thoroughDel($id);

        return ResultsVo::success();
    }

    /**
     * 校验模型是否发布.
     *
     * @param $mod_id
     */
    private function checkModIsPublish($mod_id): void
    {
        /** @var Mod $mod */
        $mod = Easy::mod()->find($mod_id);
        if (blank($mod)) {
            throw new ServiceException('模型不存在', CommonExceptionConstants::NO_FIND_DATA);
        }
        if (1 === $mod->is_publish) {
            throw new ServiceException('发布状态的模型不允许操作模型字段', CommonExceptionConstants::DATA_SAVE_FAIL);
        }
    }
}
