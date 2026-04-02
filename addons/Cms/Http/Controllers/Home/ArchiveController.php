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

namespace Addon\Cms\Http\Controllers\Home;

use Addon\Cms\Enum\SEOEnum;
use Addon\Cms\Models\Archive;
use Addon\Cms\Models\Category;
use Addon\Cms\Models\Tag;
use Addon\Cms\Models\Topic;
use Addon\Cms\Service\ArchiveService;
use Addon\Cms\Service\CategoryService;
use Addon\Cms\Service\SeoService;
use App\Exceptions\ServiceException;
use PTAdmin\Admin\Controllers\Home\AbstractWebController;

class ArchiveController extends AbstractWebController
{
    private $archiveService;
    private $seoService;
    private $categoryService;

    public function __construct(
        ArchiveService $archiveService,
        SeoService $seoService,
        CategoryService $categoryService
    ) {
        $this->archiveService = $archiveService;
        $this->seoService = $seoService;
        $this->categoryService = $categoryService;
    }

    /**
     * 文章详情页面读取.
     *
     * @param ...$param
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function detail(...$param)
    {
        list($params) = $this->seoService->parserRequestParams(SEOEnum::DETAIL, $param);

        $detail = $this->archiveService->getDetail($params['id']);
        $category = $this->categoryService->byId($detail['category_id']);

        view()->share([
            'd' => $detail,
            'c' => $category,
            'current' => 'detail',
        ]);
        $view = $this->getViewTemplate(SEOEnum::DETAIL, $category);

        return view($view);
    }

    /**
     * 文章详情h5页面读取.
     *
     * @param ...$param
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function h5detail(...$param)
    {
        list($params) = $this->seoService->parserRequestParams(SEOEnum::DETAIL, $param);

        $detail = $this->archiveService->getDetail($params['id']);
        $category = $this->categoryService->byId($detail['category_id']);

        view()->share([
            'd' => $detail,
            'c' => $category,
            'current' => 'detail',
        ]);

        return view('default.cms.detail_h5_default');
    }

    /**
     * 文章列表页.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function lists(...$param)
    {
        list($params) = $this->seoService->parserRequestParams(SEOEnum::LIST, $param);

        if (!isset($params['category_id']) && isset($params['category'])) {
            $category = Category::query()->where('dir_name', $params['category'])->firstOrFail();
            $params['category_id'] = $category['id'];
        }
        $category = $this->categoryService->byId($params['category_id']);
        $view = $this->getViewTemplate(SEOEnum::LIST, $category);

        return view($view, compact('category'));
    }

    /**
     * 栏目频道页.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function channel(...$param)
    {
        list($params) = $this->seoService->parserRequestParams(SEOEnum::CHANNEL, $param);
        $category = $this->categoryService->byId($params['category_id']);
        $view = $this->getViewTemplate(SEOEnum::CHANNEL, $category);

        return view($view);
    }

    /**
     * 搜索页面.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function search()
    {
        return view('default.cms.search');
    }

    /**
     * 单页.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function single(...$param)
    {
        list($params) = $this->seoService->parserRequestParams(SEOEnum::SINGLE, $param);
        $category = $this->resolveCategory($params);

        if (!$category) {
            throw new ServiceException('未找到分类');
        }
        $detail = Archive::query()->where('category_id', $category['id'])->with('content')->first();
        if (!$detail) {
            throw new ServiceException('未找到文章');
        }
        $detail = $detail->toArray();
        view()->share([
            'd' => $detail,
            'c' => $category,
            'current' => 'single',
        ]);
        $view = $this->getViewTemplate(SEOEnum::SINGLE, $category);

        return view($view);
    }

    /**
     * 标签页.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function tag(...$param)
    {
        list($params) = $this->seoService->parserRequestParams(SEOEnum::TAG, $param);
        $tag = Tag::query()->findOrFail($params['tag_id']);
        $view = $this->getViewTemplate(SEOEnum::TAG, $tag);

        return view($view);
    }

    /**
     * 专题页.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function topic(...$param)
    {
        list($params) = $this->seoService->parserRequestParams(SEOEnum::TOPIC, $param);
        $topic = Topic::query()->findOrFail($params['topic_id']);
        $view = $this->getViewTemplate(SEOEnum::TOPIC, $topic);

        return view($view);
    }

    /**
     * 解析栏目.
     *
     * @param array $params
     *
     * @return null|array
     */
    private function resolveCategory(array $params): ?array
    {
        if (isset($params['category'])) {
            $category = Category::query()->where('dir_name', $params['category'])->first();

            return isset($category) ? $category->toArray() : null;
        }

        if (isset($params['category_id'])) {
            return $this->categoryService->byId($params['category_id']);
        }

        return null;
    }
}
