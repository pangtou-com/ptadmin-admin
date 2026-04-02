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

use Addon\Cms\Service\ModelService;
use App\Exceptions\CommonExceptionConstants;
use App\Exceptions\ServiceException;
use Illuminate\Http\Request;
use PTAdmin\Admin\Controllers\Admin\AbstractBackgroundController;
use PTAdmin\Admin\Utils\ResultsVo;
use PTAdmin\Build\Layui;
use PTAdmin\Easy\Easy;
use PTAdmin\Easy\Model\Mod;

class ModelController extends AbstractBackgroundController
{
    protected $modelService;

    public function __construct(ModelService $modelService)
    {
        parent::__construct();
        $this->modelService = $modelService;
    }

    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $results = Easy::mod()->lists($request->only(['recycle']), ModelService::MOD_NAME);

            return ResultsVo::pages($results);
        }

        return $this->view();
    }

    public function store(Request $request)
    {
        if ($request->expectsJson()) {
            Easy::mod()->store($request->all(), ModelService::MOD_NAME);

            return ResultsVo::success();
        }

        return $this->view();
    }

    public function edit(Request $request, $id)
    {
        if ($request->expectsJson()) {
            /** @var Mod $model */
            $model = Easy::mod()->find($id);
            if (1 === $model->is_publish) {
                throw new ServiceException('发布状态的模型不允许编辑', CommonExceptionConstants::DATA_SAVE_FAIL);
            }
            Easy::mod()->edit($request->all(), $id);

            return ResultsVo::success();
        }
        $dao = Easy::mod()->find($id);

        return $this->view(compact('dao'));
    }

    /**
     * 预览表单.
     *
     * @param $id
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function preview($id)
    {
        $results = $this->modelService->getFormBuildRender((int) $id, true);

        $render = Layui::make();
        $render->setRules($results);

        return $this->view(compact('render'));
    }

    /**
     * 状态切换.
     *
     * @param Request $request
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        Easy::mod()->setStatus($id, (int) $request->get('value'));

        return ResultsVo::success();
    }

    /**
     * 发布模型.
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function publish($id): \Illuminate\Http\JsonResponse
    {
        Easy::mod()->publish($id);

        return ResultsVo::success();
    }

    /**
     * 取消发布.
     *
     * @param mixed $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($id): \Illuminate\Http\JsonResponse
    {
        Easy::mod()->unPublish($id);

        return ResultsVo::success();
    }

    /**
     * 删除数据.
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function del($id): \Illuminate\Http\JsonResponse
    {
        /** @var Mod $model */
        $model = Easy::mod()->find($id);
        if (1 === $model->is_publish) {
            throw new ServiceException('发布状态的模型不允许删除', CommonExceptionConstants::DATA_EXCEPTION);
        }
        Easy::mod()->delete($id);

        return ResultsVo::success();
    }

    /**
     * 恢复数据.
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id): \Illuminate\Http\JsonResponse
    {
        Easy::mod()->restore($id);

        return ResultsVo::success();
    }

    /**
     * 彻底删除.
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function thoroughDel($id): \Illuminate\Http\JsonResponse
    {
        Easy::mod()->thoroughDel($id);

        return ResultsVo::success();
    }
}
