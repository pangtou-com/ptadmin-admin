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

use Addon\Cms\Models\AdSpace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'ad_space_id' => ['required', Rule::exists(AdSpace::class, 'id')->whereNull('deleted_at')],
            'links' => 'max:255',
            'status' => 'required|in:0,1',
            'sort_order' => 'max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => '广告名称',
            'ad_space_id' => '广告位',
            'links' => '广告链接',
            'image' => '广告图片',
            'status' => '状态',
            'weight' => '排序',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '请输入广告名称.',
            'ad_space_id.required' => '请选择广告位.',
            'image.required' => '请上传广告图片.',
            'status.required' => '请选择状态.',
            'weight.required' => '请输入排序.',
            'title.max' => '广告名称不能超过255个字符.',
            'links.max' => '广告链接不能超过255个字符.',
            'image.max' => '广告图片地址不能超过255个字符.',
            'weight.max' => '排序大小不能超过255.',
            'status.in' => '请选择正确的状态.',
        ];
    }
}
