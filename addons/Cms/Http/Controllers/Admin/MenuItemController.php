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

namespace Addon\Cms\Http\Controllers\Admin;

use Addon\Cms\Http\Request\Admin\MenuItemRequest;
use Addon\Cms\Models\MenuItem;
use Addon\Cms\Service\MenuItemService;
use Illuminate\Http\Request;
use PTAdmin\Admin\Controllers\Admin\AbstractBackgroundController;
use PTAdmin\Admin\Utils\ResultsVo;

class MenuItemController extends AbstractBackgroundController
{
    protected $menuItemService;

    public function __construct(MenuItemService $menuItemService)
    {
        parent::__construct();
        $this->menuItemService = $menuItemService;
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $menuId = (int) $request->get('menu_id');
        $data = $this->menuItemService->getMenuItemTree($menuId);

        return ResultsVo::success($data);
    }

    public function detail($id): \Illuminate\Http\JsonResponse
    {
        $dao = MenuItem::query()->findOrFail($id);

        return ResultsVo::success($dao);
    }

    public function store(MenuItemRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->menuItemService->store($request->all());

        return ResultsVo::success();
    }

    public function edit(MenuItemRequest $request, $id): \Illuminate\Http\JsonResponse
    {
        $this->menuItemService->edit($request->all(), (int) $id);

        return ResultsVo::success();
    }

    public function delete($id): \Illuminate\Http\JsonResponse
    {
        $this->menuItemService->delete((int) $id);

        return ResultsVo::success();
    }
}
