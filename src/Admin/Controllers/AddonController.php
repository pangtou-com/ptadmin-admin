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

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PTAdmin\Addon\AddonApi;
use PTAdmin\Admin\Services\AddonFrontendService;
use PTAdmin\Admin\Services\AddonPlatformService;
use PTAdmin\Foundation\Response\AdminResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AddonController extends AbstractBackgroundController
{
    public $unAddon;
    private AddonPlatformService $addonPlatformService;

    public function __construct(AddonPlatformService $addonPlatformService)
    {
        parent::__construct();
        $this->addonPlatformService = $addonPlatformService;

        view()->share('ptadmin_addon_user', AddonApi::getCloudUserinfo());
    }

    /**
     * 获取本地插件信息.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAddonLocal(): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->addonPlatformService->localAddons());
    }

    /**
     * 下载插件.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function upLoadAddon($id, Request $request)
    {
        return AdminResponse::success();
    }

    public function localInstall(Request $request)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:zip',
            'force' => 'sometimes|boolean',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        return AdminResponse::success($this->addonPlatformService->installFromLocal($file, (bool) ($data['force'] ?? false)));
    }

    /**
     * 获取插件地址并下载.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAddonDownloadUrl(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->only(['code', 'addon_id', 'addon_version_id']);
        $codes = \PTAdmin\Addon\Addon::getInstalledAddonsCode();
        if (\in_array($data['code'], $codes, true)) {
            return AdminResponse::fail(__('ptadmin::background.addon_installed'));
        }
        $result = AddonApi::getAddonDownloadUrl($data);

        return AdminResponse::success($result);
    }

    public function myAddon(Request $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->addonPlatformService->myCloudAddons($request->all()));
    }

    /**
     * 返回后台前端需要的插件模块清单。
     *
     * 前端启动后可以通过该接口识别：
     * - 当前有哪些插件前端模块
     * - 哪些模块需要预加载
     * - 哪些模块需要启用前端缓存
     */
    public function moduleManifests(AddonFrontendService $service): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($service->manifests());
    }

    /**
     * 保存配置.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addonSetting(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:100',
            'values' => 'sometimes|array',
        ]);

        return AdminResponse::success($this->addonPlatformService->saveAddonConfig((string) $data['code'], $request->all()));
    }

    /**
     * 图片地址
     *
     * @param $code
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function showImage($code)
    {
        $image = addon_path((string) $code, 'cover.png');
        response()->header('Content-Length', filesize($image));
        response()->header('Content-Disposition', 'attachment; filename="'.$code.'.jpg"');
        response()->header('Content-Transfer-Encoding', 'binary');
        response()->header('Expires', '0');
        response()->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        response()->header('Pragma', 'public');

        return response()->setContent(file_get_contents($image));
    }

    /**
     * 卸载.
     *
     * @param $code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uninstall($code): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->addonPlatformService->uninstall((string) $code, request()->boolean('force')));
    }

    public function addonCloud(Request $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->addonPlatformService->cloudMarket($request->all()));
    }

    /**
     * 新接口：获取本地插件列表。
     */
    public function local(): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->addonPlatformService->localAddons());
    }

    /**
     * 新接口：获取云市场插件列表。
     */
    public function cloud(Request $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->addonPlatformService->cloudMarket($request->all()));
    }

    /**
     * 新接口：获取当前云账号的插件列表。
     */
    public function cloudMine(Request $request): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->addonPlatformService->myCloudAddons($request->all()));
    }

    /**
     * 新接口：从云平台安装插件。
     */
    public function installCloud(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:100',
            'addon_version_id' => 'sometimes|integer|min:0',
            'force' => 'sometimes|boolean',
        ]);

        return response()->stream(function () use ($data): void {
            try {
                $result = $this->addonPlatformService->installFromCloud(
                    (string) $data['code'],
                    (int) ($data['addon_version_id'] ?? 0),
                    (bool) ($data['force'] ?? false)
                );

                $this->sendStreamMessage([
                    'type' => 'success',
                    'message' => __('ptadmin::common.success'),
                    'data' => $result,
                ]);
            } catch (\Throwable $throwable) {
                Log::error('PTAdmin addon cloud install stream failed', [
                    'code' => (string) $data['code'],
                    'message' => $throwable->getMessage(),
                ]);

                $this->sendStreamMessage([
                    'type' => 'error',
                    'message' => $throwable->getMessage(),
                    'data' => [],
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'X-Powered-By' => 'ptadmin',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * 新接口：初始化插件开发脚手架。
     */
    public function init(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:100',
            'title' => 'sometimes|string|max:150',
            'force' => 'sometimes|boolean',
        ]);

        return response()->stream(function () use ($data): void {
            try {
                $result = $this->addonPlatformService->initAddon(
                    (string) $data['code'],
                    (string) ($data['title'] ?? ''),
                    (bool) ($data['force'] ?? false)
                );

                $this->sendStreamMessage([
                    'type' => 'success',
                    'message' => __('ptadmin::common.success'),
                    'data' => $result,
                ]);
            } catch (\Throwable $throwable) {
                Log::error('PTAdmin addon init stream failed', [
                    'code' => (string) $data['code'],
                    'message' => $throwable->getMessage(),
                ]);

                $this->sendStreamMessage([
                    'type' => 'error',
                    'message' => $throwable->getMessage(),
                    'data' => [],
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'X-Powered-By' => 'ptadmin',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * 新接口：拉取插件前端开发模板。
     */
    public function pullFrontend(string $code, Request $request): StreamedResponse
    {
        $data = $request->validate([
            'template' => 'sometimes|string|max:100',
            'ref' => 'sometimes|string|max:100',
            'source' => 'sometimes|string|max:50',
            'force' => 'sometimes|boolean',
        ]);

        return response()->stream(function () use ($code, $data): void {
            try {
                $result = $this->addonPlatformService->pullFrontend(
                    $code,
                    (string) ($data['template'] ?? 'vue3-admin'),
                    (string) ($data['ref'] ?? 'main'),
                    (string) ($data['source'] ?? ''),
                    (bool) ($data['force'] ?? false)
                );

                $this->sendStreamMessage([
                    'type' => 'success',
                    'message' => __('ptadmin::common.success'),
                    'data' => $result,
                ]);
            } catch (\Throwable $throwable) {
                Log::error('PTAdmin addon frontend pull stream failed', [
                    'code' => $code,
                    'message' => $throwable->getMessage(),
                ]);

                $this->sendStreamMessage([
                    'type' => 'error',
                    'message' => $throwable->getMessage(),
                    'data' => [],
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'X-Powered-By' => 'ptadmin',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * 新接口：返回插件当前状态。
     */
    public function status(string $code): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->addonPlatformService->status($code));
    }

    /**
     * 新接口：返回插件通用配置。
     */
    public function config(string $code): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->addonPlatformService->addonConfig($code));
    }

    /**
     * 新接口：保存插件通用配置。
     */
    public function saveConfig(string $code, Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'values' => 'sometimes|array',
        ]);

        return AdminResponse::success($this->addonPlatformService->saveAddonConfig($code, $request->all()));
    }

    /**
     * 新接口：启用插件。
     */
    public function enable(string $code): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->addonPlatformService->enable($code));
    }

    /**
     * 新接口：停用插件。
     */
    public function disable(string $code): \Illuminate\Http\JsonResponse
    {
        return AdminResponse::success($this->addonPlatformService->disable($code));
    }

    /**
     * 新接口：升级插件。
     */
    public function upgrade(string $code, Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'addon_version_id' => 'sometimes|integer|min:0',
            'force' => 'sometimes|boolean',
        ]);

        return AdminResponse::success($this->addonPlatformService->upgrade(
            $code,
            (int) ($data['addon_version_id'] ?? 0),
            (bool) ($data['force'] ?? false)
        ));
    }

    private function sendStreamMessage(array $payload): void
    {
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
