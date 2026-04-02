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

namespace Addon\Cms\Http\Request\Admin;

use Addon\Cms\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CmsCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        $id = (int) request()->route('id');

        return [
            'parent_id' => ['integer'],
            'type' => ['required', 'in:list,page,link'],
            'title' => ['required', 'max:100', 'min:2'],
            'mod_id' => ['required', 'integer'],
            'subtitle' => 'max:255',
            'alias' => [
                'max:255',
                Rule::unique(Category::class, 'alias')->ignore($id),
                function ($attribute, $value, $fail): void {
                    if (!preg_match('/^[a-zA-Z_\-\d]+$/', $value)) {
                        $fail('目录名称只能是英文字母以及数字');
                    }
                },
            ],
            'weight' => 'integer|max:255',
            'description' => 'max:500',
            'external_link' => 'max:255',
            'icon' => 'max:255',
            'cover' => 'max:255',
            'is_nav' => 'in:0,1',
            'status' => 'in:0,1',
            'seo_title' => 'max:255',
            'seo_keyword' => 'max:255',
            'seo_doc' => 'max:255',
            'template_list' => 'max:255',
            'template_detail' => 'max:255',
            'template_channel' => 'max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => '父栏目',
            'title' => '标题',
            'subtitle' => '副标题',
            'dir_name' => '目录名称',
            'mod_id' => '所属模型',
            'cover' => '封面图',
            'is_single' => '是否为单页',
            'status' => '状态',
            'note' => '备注',
            'links' => '外部链接',
            'weight' => '权重',
            'seo_title' => 'SEO标题',
            'seo_keyword' => 'SEO关键词',
            'seo_doc' => 'SEO描述',
            'template_list' => '列表页',
            'template_detail' => '详情页',
            'template_channel' => '频道页',
        ];
    }
}
