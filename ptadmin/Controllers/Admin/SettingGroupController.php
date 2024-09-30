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

use Illuminate\Validation\Rule;
use PTAdmin\Admin\Controllers\Traits\EditTrait;
use PTAdmin\Admin\Controllers\Traits\ExtendTrait;
use PTAdmin\Admin\Controllers\Traits\StoreTrait;
use PTAdmin\Admin\Controllers\Traits\ValidateTrait;
use PTAdmin\Admin\Models\SettingGroup;
use PTAdmin\Admin\Service\SettingGroupService;
use PTAdmin\Admin\Utils\ResultsVo;

class SettingGroupController extends AbstractBackgroundController
{
    use EditTrait;
    use ExtendTrait;
    use StoreTrait;
    use ValidateTrait;
    protected $settingGroupService;

    public function __construct(SettingGroupService $settingGroupService)
    {
        $this->settingGroupService = $settingGroupService;
        parent::__construct();
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        $results = SettingGroup::query()
            ->select(['id', 'parent_id', 'title', 'name', 'weight', 'intro', 'status'])
            ->orderBy('weight', 'desc')
            ->with('setting')
            ->get()->toArray();
        $results = infinite_tree($results);

        return ResultsVo::success(['results' => $results]);
    }

    public function byConfigureCategoryId($id): \Illuminate\Http\JsonResponse
    {
        $data = $this->settingGroupService->byParentId($id);

        return ResultsVo::success($data);
    }

    /**
     * 通过根节点ID获取当前节点下的分类信息和分类字段信息.
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRootConfigureCategoryId($id): \Illuminate\Http\JsonResponse
    {
        $results = $this->settingGroupService->getRootConfigureCategoryId($id);

        return ResultsVo::success($results);
    }

    public function delete(): \Illuminate\Http\JsonResponse
    {
        $ids = $this->getIds();
        $this->settingGroupService->del(reset($ids));

        return ResultsVo::success();
    }

    protected function rules(): array
    {
        $id = (int) request()->route('id');
        $parentId = (int) request()->get('parent_id');

        return [
            'title' => ['required', 'max:255', Rule::unique(SettingGroup::class)->whereNull('deleted_at')->ignore($id)],
            'name' => [
                'required', 'max:255',
                Rule::unique(SettingGroup::class)->whereNull('deleted_at')->ignore($id),
            ],
            'weight' => 'integer|min:0|max:255',
            'parent_id' => $parentId ? [
                Rule::exists(SettingGroup::class, 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($id): void {
                    if ((int) $value === $id) {
                        $fail('上级分组不能为自身');
                    }
                },
            ] : [],
            'remark' => 'max:255',
            'status' => 'in:0,1',
            'tab' => 'integer',
        ];
    }
}
