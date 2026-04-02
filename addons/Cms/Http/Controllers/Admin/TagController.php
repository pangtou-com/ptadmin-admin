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

use Addon\Cms\Http\Request\Admin\CmsTagRequest;
use Addon\Cms\Models\Tag;
use Addon\Cms\Service\ArchiveService;
use Addon\Cms\Service\TagService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PTAdmin\Admin\Controllers\Admin\AbstractBackgroundController;
use PTAdmin\Admin\Utils\ResultsVo;

class TagController extends AbstractBackgroundController
{
    protected $tagService;
    protected $archiveService;

    public function __construct(TagService $tagService, ArchiveService $archiveService)
    {
        parent::__construct();
        $this->tagService = $tagService;
        $this->archiveService = $archiveService;
    }

    public function index(Request $request)
    {
        if (request()->expectsJson()) {
            $data = $this->tagService->page($request->all());

            return ResultsVo::pages($data);
        }

        return view($this->getViewPath());
    }

    public function store(CmsTagRequest $request)
    {
        if (request()->expectsJson()) {
            $this->tagService->store($request->all());

            return ResultsVo::success();
        }
        $dao = new Tag();
        $dao->weight = 99;

        return view($this->getViewPath(), compact('dao'));
    }

    public function edit($id, CmsTagRequest $request)
    {
        if (request()->expectsJson()) {
            $this->tagService->edit($id, $request->all());

            return ResultsVo::success();
        }
        $dao = Tag::query()->findOrFail($id);
        $dao->weight = 99;

        return view($this->getViewPath(), compact('dao'));
    }

    public function delete($id): JsonResponse
    {
        $this->tagService->delete($id);

        return ResultsVo::success();
    }

    /**
     * 获取文章信息.
     *
     * @param Request $request
     *
     * @return Application|Factory|JsonResponse|View
     */
    public function archiveList(Request $request)
    {
        $id = $request->get('id');
        $checked = $request->get('checked');
        $search = $request->only(['checked', 'title', 'category_id', 'mod_id']);
        $search['tag_id'] = $id;

        if ($request->expectsJson()) {
            $articleList = $this->archiveService->page($search);

            return ResultsVo::pages($articleList);
        }

        return view('cms::ptadmin.tag.association', compact('id', 'checked'));
    }

    /**
     * 关联文章.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function association(Request $request): JsonResponse
    {
        $data = $request->only('tag_id', 'ids');
        $this->tagService->association($data);

        return ResultsVo::success();
    }

    /**
     * 删除关联信息.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function delAssociation(Request $request): JsonResponse
    {
        $data = $request->only(['tag_id', 'archive_ids']);
        $this->tagService->delAssociation($data);

        return ResultsVo::success();
    }

    public function getCategoryUrl(): void
    {
        // 根据配置生成路由地址信息和跳转方法
        // 1、条件参数
        // 2、默认路由信息
        // 3、匹配规则
    }
}
