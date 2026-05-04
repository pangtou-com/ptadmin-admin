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
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use PTAdmin\Admin\Services\AdminResourceService;
use PTAdmin\Admin\Services\LoginService;
use PTAdmin\Foundation\Response\AdminResponse;
use PTAdmin\Foundation\Auth\AdminAuth;
use PTAdmin\Contracts\Auth\AdminRoleServiceInterface;

class LoginController extends AbstractBackgroundController
{
    private LoginService $loginService;
    private AdminResourceService $adminResourceService;
    private AdminRoleServiceInterface $adminRoleService;

    public function __construct(LoginService $loginService, AdminResourceService $adminResourceService, AdminRoleServiceInterface $adminRoleService)
    {
        $this->loginService = $loginService;
        $this->adminResourceService = $adminResourceService;
        $this->adminRoleService = $adminRoleService;
    }

    /**
     * 输出后台接口登录提示页，便于浏览器直接访问接口时定位问题。
     */
    public function notice(Request $request): Response
    {
        $redirect = trim((string) $request->query('redirect', ''), ' ');
        $html = '<!DOCTYPE html><html lang="'.e(app()->getLocale()).'"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>'.e(__('ptadmin::common.login_notice.title')).'</title><style>body{margin:0;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",sans-serif;background:#f5f7fb;color:#1f2937}main{max-width:760px;margin:8vh auto;padding:32px;background:#fff;border-radius:16px;box-shadow:0 18px 60px rgba(15,23,42,.08)}h1{margin:0 0 12px;font-size:28px}p{line-height:1.7;color:#4b5563}code{display:inline-block;padding:2px 8px;border-radius:6px;background:#f3f4f6;color:#111827}pre{padding:14px 16px;border-radius:10px;background:#111827;color:#f9fafb;overflow:auto}a.button{display:inline-block;margin-right:12px;padding:10px 16px;border-radius:10px;background:#111827;color:#fff;text-decoration:none}a.link{color:#2563eb;text-decoration:none}</style></head><body><main><h1>'.e(__('ptadmin::common.login_notice.title')).'</h1><p>'.e(__('ptadmin::common.login_notice.description')).'</p><p>'.e(__('ptadmin::common.login_notice.api_path')).' <code>'.e(admin_api_url('login')).'</code></p><p>'.e(__('ptadmin::common.login_notice.web_entry')).' <code>'.e(admin_web_url()).'</code></p>';

        if ('' !== $redirect) {
            $html .= '<p>'.e(__('ptadmin::common.login_notice.redirect_target')).' <code>'.e($redirect).'</code></p>';
        }

        $html .= '<p><a class="button" href="'.e(admin_web_url()).'">'.e(__('ptadmin::common.login_notice.open_admin')).'</a><a class="link" href="'.e(admin_api_url('login')).'">'.e(__('ptadmin::common.login_notice.view_login_api')).'</a></p></main></body></html>';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
        $data = $this->loginService->login($request->only(['username', 'password', 'code']));
        
        return AdminResponse::success($data);
    }

    /**
     * 获取登录账户授权菜单树.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminResources(): \Illuminate\Http\JsonResponse
    {
        $data = $this->adminResourceService->myResources(AdminAuth::user());
        if (!AdminAuth::isFounder()) {
            $roles = array_values(array_filter(array_map(static function (array $role): ?string {
                return isset($role['name']) ? (string) $role['name'] : null;
            }, $this->adminRoleService->getUserRoles((int) AdminAuth::user()->id))));
        } else {
            $roles = ['创始人'];
        }

        return AdminResponse::success(['roles' => $roles, 'resources' => $data]);
    }

    public function logout(): \Illuminate\Http\JsonResponse
    {
        Auth::guard(AdminAuth::getGuard())->logout();

        return AdminResponse::success(['url' => route('admin_login')]);
    }
}
