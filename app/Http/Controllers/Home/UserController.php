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

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PTAdmin\Admin\Request\UserRequest;
use PTAdmin\Admin\Service\UserService;
use PTAdmin\Admin\Service\UserVerifyService;
use PTAdmin\Admin\Utils\ResultsVo;

class UserController extends Controller
{
    protected $userService;
    protected $uploadService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * 登录.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:1,2',
            'code' => 'required_if:type,1|max:6',
            'username' => 'required|max:50',
            'password' => 'required_if:type,2|min:6|max:32',
        ]);

        $this->userService->login($data);

        return ResultsVo::success([], '登录成功');
    }

    /**
     * 退出登录.
     */
    public function logout(): \Illuminate\Http\RedirectResponse
    {
        $this->userService->logout();

        return redirect()->to('/');
    }

    /**
     * 第三方认证方式.
     *
     * @param mixed $tag
     */
    public function otherOauth($tag): \Illuminate\Http\JsonResponse
    {
        $results = $this->userService->otherOauth($tag);

        return ResultsVo::success($results);
    }

    /**
     * 第三方认证回调.
     *
     * @param Request $request
     * @param $tag
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function oauthCallback(Request $request, $tag): \Illuminate\Http\RedirectResponse
    {
        $this->userService->otherOauthCallback($request->all(), $tag);

        return redirect()->to(url('user/setting'));
    }

    /**
     * 注册.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        if ($request->expectsJson()) {
            $data = $request->validate([
                'type' => 'required|in:1,2',
                'mobile' => 'required_if:type,1|max:20',
                'code' => 'required|max:6',
                'email' => 'required_if:type,2|max:50',
                'password' => 'required|min:6|max:32',
            ]);
            //手机号注册 邮箱注册
            if (1 === (int) $data['type']) {
                UserVerifyService::verifyCode($data['mobile'], $data['code'], UserVerifyService::SCENE_REGISTER, UserVerifyService::TYPE_SMS);
            } else {
                UserVerifyService::verifyCode($data['email'], $data['code'], UserVerifyService::SCENE_REGISTER, UserVerifyService::TYPE_EMAIL);
            }
            $this->userService->register($data);

            return ResultsVo::success([], '注册成功');
        }

        return view('default.user.register');
    }

    /**
     * 重置密码.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function reset(Request $request)
    {
        if ($request->expectsJson()) {
            $data = $request->validate([
                'type' => 'required|in:1,2',
                'mobile' => 'required_if:type,1|max:20',
                'code' => 'required|max:6',
                'email' => 'required_if:type,2|max:50',
                'password' => 'required|confirmed|min:6|max:32',
            ]);
            if (1 === (int) $data['type']) {
                UserVerifyService::verifyCode($data['mobile'], $data['code'], UserVerifyService::SCENE_FORGET, UserVerifyService::TYPE_SMS);
            } else {
                UserVerifyService::verifyCode($data['email'], $data['code'], UserVerifyService::SCENE_FORGET, UserVerifyService::TYPE_EMAIL);
            }
            $this->userService->reset($data);

            return ResultsVo::success([], '重置密码成功');
        }

        return view('default.user.reset');
    }

    /**
     * 个人中心.
     *
     * @return mixed
     */
    public function index()
    {
        $user = Auth::guard('web')->user();
        $consume_amount = $this->userService->getConsumeAmount($user->id);

        return view('default.user.index', compact('user', 'consume_amount'));
    }

    /**
     * 个人设置.
     *
     * @param $request
     *
     * @return mixed
     */
    public function setting(UserRequest $request)
    {
        $user = Auth::guard('web')->user();
        if ($request->expectsJson()) {
            $data = $request->validated();
            $this->userService->updateProfile($user->id, $data);

            return ResultsVo::success([], '修改成功');
        }

        return view('default.user.setting', compact('user'));
    }
}
