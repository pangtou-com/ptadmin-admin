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

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PTAdmin\Admin\Services\SystemConfigGroupService;
use PTAdmin\Admin\Services\SystemConfigService;
use PTAdmin\Foundation\Response\AdminResponse;

class SystemConfigController extends AbstractBackgroundController
{
    protected $systemConfigService;
    protected $systemConfigGroupService;

    public function __construct(
        SystemConfigService $systemConfigService,
        SystemConfigGroupService $systemConfigGroupService
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->systemConfigGroupService = $systemConfigGroupService;
    }

    /**
     * 返回系统配置导航树。
     *
     * 只输出一级分组和二级分组信息，前端根据 section id
     * 再单独获取 schema 与当前值。
     */
    public function index(): JsonResponse
    {
        return AdminResponse::success([
            'groups' => $this->systemConfigGroupService->navigation(),
        ]);
    }

    /**
     * 返回系统配置项定义列表。
     */
    public function items(Request $request): JsonResponse
    {
        $results = $this->systemConfigService->items($request->all());

        return AdminResponse::pages($results);
    }

    /**
     * 返回某个配置页签的 schema 与当前值。
     *
     * data.schema 来自 easy blueprint，前端按协议渲染表单；
     * data.values 为当前实际值，前端回填后可直接提交保存。
     */
    public function details($id): JsonResponse
    {
        return AdminResponse::success($this->systemConfigService->section((int) $id));
    }

    /**
     * 保存某个配置页签的值。
     */
    public function updateSection($id, Request $request): JsonResponse
    {
        return AdminResponse::success([
            'values' => $this->systemConfigService->saveSection((int) $id, $request->all()),
        ]);
    }

    /**
     * 保存配置值
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveValues(Request $request): JsonResponse
    {
        $this->systemConfigService->saveValues($request->all());

        return AdminResponse::success();
    }

    /**
     * 新增配置项定义。
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $data = $validated;
        $this->systemConfigService->store($data);

        return AdminResponse::success();
    }

    /**
     * 编辑配置项定义。
     */
    public function edit($id, Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $data = $validated;
        $this->systemConfigService->edit((int) $id, $data);

        return AdminResponse::success();
    }

    /**
     * 删除配置项定义。
     */
    public function delete(Request $request): JsonResponse
    {
        $ids = [];
        $routeId = (int) $request->route('id');
        if (0 !== $routeId) {
            $ids[] = $routeId;
        }

        $batchIds = norm_ids($request->get('ids'));
        foreach ($batchIds as $id) {
            $ids[] = (int) $id;
        }

        $this->systemConfigService->delete($ids);

        return AdminResponse::success();
    }

    protected function rules(): array
    {
        return [
            'system_config_group_id' => 'required',
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
            'system_config_group_id.required' => '请选择配置分组',
            'title.required' => '请输入配置名称',
            'title.max' => '配置名称最多255个字符',
            'name.required' => '请输入配置标识',
            'name.max' => '配置标识最多32个字符',
            'name.regex' => '配置标识只能包含字母、数字、下划线，且必须以字母开头',
            'type.required' => '请选择配置属性',
            'type.max' => '配置属性最多20个字符',
        ];
    }

    /**
     * 对配置项写接口做显式参数校验。
     *
     * 这里不直接使用 `$request->validate()`，避免在包级接口中落回
     * Laravel 默认的 422 响应格式，而是统一收口为后台接口约定的
     * `code=20000` 业务失败结构。
     *
     * @return array<string, mixed>|JsonResponse
     */
    private function validatePayload(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules(), $this->messages());
        if ($validator->fails()) {
            $message = collect($validator->errors()->toArray())
                ->map(static function (array $item): string {
                    return implode('|', $item);
                })
                ->implode('');

            return AdminResponse::fail($message, 20000);
        }

        return $validator->validated();
    }
}
