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
use PTAdmin\Addon\AddonApi;
use PTAdmin\Admin\Requests\AddonRequest;
use PTAdmin\Admin\Services\AddonFrontendService;
use PTAdmin\Foundation\Response\AdminResponse;

class AddonController extends AbstractBackgroundController
{
    public $unAddon;

    public function __construct()
    {
        parent::__construct();

        view()->share('ptadmin_addon_user', AddonApi::getCloudUserinfo());
    }

    public function index()
    {
        return view('ptadmin.addon.cloud');
    }

    /**
     * 获取本地插件信息.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAddonLocal(): \Illuminate\Http\JsonResponse
    {
        $data = [];

        return AdminResponse::pages($data);
    }

    public function store(AddonRequest $addonRequest)
    {
        return AdminResponse::success();
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
        return AdminResponse::success();
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
        $result = AddonApi::getMyAddon($request->all());

        return AdminResponse::success($result);
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
        return AdminResponse::success();
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
        return AdminResponse::success();
    }

    public function addonCloud(Request $request): \Illuminate\Http\JsonResponse
    {
        $result = AddonApi::getCloudMarket($request->all());

        return AdminResponse::success($result);
    }
}
