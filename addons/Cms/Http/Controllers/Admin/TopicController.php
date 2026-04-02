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

use Addon\Cms\Enum\SEOEnum;
use Addon\Cms\Http\Request\Admin\TopicAssociationRequest;
use Addon\Cms\Http\Request\Admin\TopicNavigationRequest;
use Addon\Cms\Http\Request\Admin\TopicRequest;
use Addon\Cms\Models\Topic;
use Addon\Cms\Models\TopicAssociation;
use Addon\Cms\Models\TopicNavigation;
use Addon\Cms\Service\CategoryService;
use Addon\Cms\Service\SeoUrlService;
use Addon\Cms\Service\TopicAssociationService;
use Addon\Cms\Service\TopicNavigationService;
use Addon\Cms\Service\TopicService;
use Illuminate\Http\Request;
use PTAdmin\Admin\Controllers\Admin\AbstractBackgroundController;
use PTAdmin\Admin\Utils\ResultsVo;

class TopicController extends AbstractBackgroundController
{
    protected $topicService;

    protected $topicNavigationService;
    protected $categoryService;
    protected $topicAssociationService;

    protected $seoUrlService;

    public function __construct(TopicService $topicService, TopicNavigationService $topicNavigationService, CategoryService $categoryService, TopicAssociationService $topicAssociationService, SeoUrlService $seoUrlService)
    {
        parent::__construct();

        $this->topicService = $topicService;
        $this->topicNavigationService = $topicNavigationService;
        $this->categoryService = $categoryService;
        $this->topicAssociationService = $topicAssociationService;
        $this->seoUrlService = $seoUrlService;
    }

    /**
     * 专题管理-列表.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if (request()->expectsJson()) {
            $data = $this->topicService->page($request->all());

            return ResultsVo::pages($data);
        }

        return view($this->getViewPath());
    }

    /**
     * 专题管理-新增.
     *
     * @param TopicRequest $topicRequest
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function store(TopicRequest $topicRequest)
    {
        if (request()->expectsJson()) {
            $this->topicService->store($topicRequest->all());

            return ResultsVo::success();
        }

        $dao = new Topic();
        $dao->weight = 99;
        $dao->status = 1;

        return view($this->getViewPath(), compact('dao'));
    }

    /**
     * 专题管理-修改.
     *
     * @param $id
     * @param TopicRequest $topicRequest
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function edit($id, TopicRequest $topicRequest)
    {
        if (request()->expectsJson()) {
            $this->topicService->edit($id, $topicRequest->all());

            return ResultsVo::success();
        }
        $dao = Topic::query()->findOrFail($id);

        return view($this->getViewPath(), compact('dao'));
    }

    public function detail($id)
    {
        $dao = Topic::query()->findOrFail($id);
        $this->seoUrlService->getUrlArr(SEOEnum::TOPIC);

        return view('cms::ptadmin.topic.details', compact('dao'));
    }

    /**
     * 专题管理-专题导航-列表.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function navigations($id, Request $request)
    {
        if (request()->expectsJson()) {
            $search = $request->all();
            $search['topic_id'] = $id;
            $data = $this->topicNavigationService->page($search);

            return ResultsVo::pages($data);
        }

        return view('cms::ptadmin.topic.navigations', compact('id'));
    }

    /**
     * 专题管理-专题导航-新增.
     *
     * @param $topicId
     * @param TopicNavigationRequest $topicNavigationRequest
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function navigationStore($topicId, TopicNavigationRequest $topicNavigationRequest)
    {
        if (request()->expectsJson()) {
            $this->topicNavigationService->store($topicNavigationRequest->all());

            return ResultsVo::success();
        }
        $dao = new TopicNavigation();
        $dao->weight = 99;
        $dao->status = 1;
        $dao->navigation_type = 1;
        $dao->topic_id = $topicId;
        $categories = $this->topicAssociationService->getAttribute();
        $categories = json_encode($categories['categories'], JSON_UNESCAPED_UNICODE);

        return view('cms::ptadmin.topic.navForm', compact('dao', 'categories'));
    }

    /**
     * 专题管理-专题导航-修改.
     *
     * @param $topicId
     * @param $id
     * @param TopicNavigationRequest $topicNavigationRequest
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function navigationEdit($topicId, $id, TopicNavigationRequest $topicNavigationRequest)
    {
        if (request()->expectsJson()) {
            Topic::query()->findOrFail($topicId);
            $this->topicNavigationService->edit($id, $topicNavigationRequest->all());

            return ResultsVo::success();
        }
        $dao = TopicNavigation::query()->findOrFail($id);
        $categories = $this->topicAssociationService->getAttribute();
        $categories = json_encode($categories['categories'], JSON_UNESCAPED_UNICODE);

        return view('cms::ptadmin.topic.navForm', compact('dao', 'categories'));
    }

    /**
     * 专题管理-专题导航-删除.
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function navigationDelete($id): \Illuminate\Http\JsonResponse
    {
        $dao = TopicNavigation::query()->findOrFail($id);
        $dao->delete();

        return ResultsVo::success();
    }

    /**
     * 专题管理-专题分类-列表.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function associations($id, Request $request)
    {
        if (request()->expectsJson()) {
            $search = $request->all();
            $search['topic_id'] = $id;
            $data = $this->topicAssociationService->page($search);

            return ResultsVo::pages($data);
        }

        return view('cms::ptadmin.topic.column', compact('id'));
    }

    /**
     * 专题管理-专题分类-新增.
     *
     * @param $topicId
     * @param TopicAssociationRequest $topicAssociationRequest
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function associationStore($topicId, TopicAssociationRequest $topicAssociationRequest)
    {
        if (request()->expectsJson()) {
            $this->topicAssociationService->store($topicAssociationRequest->all());

            return ResultsVo::success();
        }
        $dao = new TopicAssociation();
        $dao->weight = 99;
        $dao->status = 1;
        $dao->association_type = 1;
        $dao->all_num = 5;
        $dao->show_num = 5;
        $dao->topic_id = $topicId;
        $attribute = json_encode($this->topicAssociationService->getAttribute(), JSON_UNESCAPED_UNICODE);
        $dao->correlation = $this->topicAssociationService->correlation(0);

        return view('cms::ptadmin.topic.columnForm', compact('dao', 'attribute'));
    }

    public function associationEdit($id, TopicAssociationRequest $topicAssociationRequest)
    {
        if (request()->expectsJson()) {
            $this->topicAssociationService->edit($id, $topicAssociationRequest->all());

            return ResultsVo::success();
        }
        $dao = TopicAssociation::query()->findOrFail($id);
        $attribute = json_encode($this->topicAssociationService->getAttribute(), JSON_UNESCAPED_UNICODE);
        $dao->correlation = $this->topicAssociationService->correlation($id);

        return view('cms::ptadmin.topic.columnForm', compact('dao', 'attribute'));
    }

    public function associationDelete($id): \Illuminate\Http\JsonResponse
    {
        $dao = TopicAssociation::query()->findOrFail($id);
        $dao->delete();

        return ResultsVo::success();
    }

    public function topicStatus($type, $id): \Illuminate\Http\JsonResponse
    {
        if ('topic' === $type) {
            //主体修改状态
            $this->topicService->status($id);
        } elseif ('navigation' === $type) {
            //导航修改状态
            $this->topicNavigationService->status($id);
        } elseif ('association' === $type) {
            //关联修改状态
            $this->topicAssociationService->status($id);
        }

        return ResultsVo::success();
    }
}
