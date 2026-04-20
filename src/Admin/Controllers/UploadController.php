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

namespace PTAdmin\Admin\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PTAdmin\Admin\Services\UploadService;
use PTAdmin\Foundation\Response\AdminResponse;

class UploadController extends AbstractBackgroundController
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
        parent::__construct();
    }
    
    /**
     * 上传单个文件。
     * @throws \Illuminate\Validation\ValidationException
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        return AdminResponse::success($this->uploadService->upload($request));
    }
    
    /**
     * TinyMCE 上传接口，仅返回编辑器要求的 location 字段。
     * @throws \Illuminate\Validation\ValidationException
     */
    public function tiny(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $result = $this->uploadService->upload($request);

        return response()->json([
            'location' => $result['url'],
        ]);
    }
    
    /**
     * 对上传接口做显式参数校验，统一返回后台 JSON 结构。
     *
     * @param Request $request
     * @return array<string, mixed>|JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validatePayload(Request $request)
    {
        $fieldName = (string) $request->get('filename', 'file');
        $payload = array_merge($request->all(), [
            $fieldName => $request->file($fieldName),
        ]);

        $validator = Validator::make($payload, [
            'group' => 'nullable|string|max:50',
            'filename' => 'nullable|string|max:50',
            $fieldName => 'required|file',
        ], [
            "{$fieldName}.required" => '请上传文件',
            "{$fieldName}.file" => '上传内容必须是有效文件',
            'group.max' => '上传分组最多50个字符',
            'filename.max' => '文件字段名最多50个字符',
        ]);

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
