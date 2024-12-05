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

namespace PTAdmin\Admin\Controllers\Admin;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PTAdmin\Admin\Controllers\Traits\EditTrait;
use PTAdmin\Admin\Controllers\Traits\ExtendTrait;
use PTAdmin\Admin\Controllers\Traits\StoreTrait;
use PTAdmin\Admin\Controllers\Traits\ValidateTrait;
use PTAdmin\Admin\Models\Setting;
use PTAdmin\Admin\Service\SettingGroupService;
use PTAdmin\Admin\Service\SettingService;
use PTAdmin\Admin\Utils\ResultsVo;

class SettingController extends AbstractBackgroundController
{
    use EditTrait;
    use ExtendTrait;
    use StoreTrait;
    use ValidateTrait;

    protected $settingService;
    protected $settingGroupService;

    public function __construct(
        SettingService $settingService,
        SettingGroupService $settingGroupService
    ) {
        $this->settingService = $settingService;
        $this->settingGroupService = $settingGroupService;
        parent::__construct();
    }

    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            $data = $this->settingGroupService->getGroupAndSettingAll();

            return ResultsVo::success($data);
        }

        return $this->view();
    }

    public function page(Request $request): JsonResponse
    {
        $results = $this->settingService->page($request->all());

        return ResultsVo::pages($results);
    }

    /**
     * 保存配置值
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveValue(Request $request): JsonResponse
    {
        $this->settingService->save($request->all());

        return ResultsVo::success();
    }

    /**
     * 新增配置值
     *
     * @param Request $request
     *
     * @return Application|Factory|JsonResponse|View
     */
    public function store(Request $request)
    {
        if ($request->expectsJson()) {
            if (method_exists($this, 'validated')) {
                $this->validated();
            }
            $this->settingService->store($request->all());

            return ResultsVo::success();
        }
        $dao = new Setting();

        return view($this->getViewPath(), compact('dao'));
    }

    /**
     * 新增配置值
     *
     * @param $id
     * @param Request $request
     *
     * @return Application|Factory|JsonResponse|View
     */
    public function edit($id, Request $request)
    {
        if ($request->expectsJson()) {
            if (method_exists($this, 'validated')) {
                $this->validated();
            }
            $this->settingService->edit($id, $request->all());

            return ResultsVo::success();
        }
        $dao = Setting::query()->findOrFail($id);
        $extra_value = $dao->extra_value;

        return view($this->getViewPath(), compact('dao', 'extra_value'));
    }

    protected function rules(): array
    {
        return [
            'setting_group_id' => 'required',
            'title' => ['required', 'max:255'],
            'name' => [
                'required', 'max:32', 'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/',
            ],
            'type' => 'required|max:20',
            'value' => 'max:255',
            'default_val' => 'max:255',
            'weight' => 'integer|min:0|max:255',
            'intro' => 'max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'setting_group_id.required' => '请选择配置分组',
            'title.required' => '请输入配置名称',
            'title.max' => '配置名称最多255个字符',
            'name.required' => '请输入配置标识',
            'name.max' => '配置标识最多32个字符',
            'name.regex' => '配置标识只能包含字母、数字、下划线，且必须以字母开头',
            'type.required' => '请选择配置属性',
            'type.max' => '配置属性最多20个字符',
        ];
    }
}
