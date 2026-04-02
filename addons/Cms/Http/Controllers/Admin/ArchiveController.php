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

use Addon\Cms\Models\Category;
use Addon\Cms\Service\ArchiveService;
use Addon\Cms\Service\CategoryService;
use Addon\Cms\Service\ModelService;
use Illuminate\Http\Request;
use PTAdmin\Admin\Controllers\Admin\AbstractBackgroundController;
use PTAdmin\Admin\Utils\ResultsVo;

class ArchiveController extends AbstractBackgroundController
{
    protected $archiveService;
    protected $categoryService;
    protected $modelService;

    public function __construct(
        ArchiveService $archiveService,
        CategoryService $categoryService,
        ModelService $modelService
    ) {
        parent::__construct();
        $this->archiveService = $archiveService;
        $this->categoryService = $categoryService;
        $this->modelService = $modelService;
    }

    public function index()
    {
        $tree = Category::getTrees();
        $category = $this->categoryService->getOption();
        array_shift($category);

        return $this->view(compact('tree', 'category'));
    }

    public function lists()
    {
        $categoryId = (int) request()->get('category_id', 0);
        if (0 !== $categoryId) {
            // 如果当前栏目为单页栏目则返回表单添加
            /** @var Category $cate */
            $cate = Category::query()->findOrFail($categoryId);
            if (1 === $cate->is_single) {
                return view($this->getPrefix().'archive.single', compact('categoryId'));
            }
        }
        $category = $this->categoryService->getOption();
        array_shift($category);

        return $this->view(compact('categoryId', 'category'));
    }

    public function pages(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = $request->only(['category_id', 'title', 'attribute_text']);

        $data = $this->archiveService->page($search);

        return ResultsVo::pages($data);
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        if (request()->expectsJson()) {
            $this->archiveService->store($request->all());

            return ResultsVo::success();
        }

        $categoryId = (int) request()->get('category_id', 0);
        $category = $this->categoryService->getSibling($categoryId);
        $render = $this->modelService->byCategoryIdRender($categoryId);
        $currentCategory = Category::query()->findOrFail($categoryId);
        $currentCategory = $currentCategory->toArray();

        // 栏目选择列表
        $categoryList = Category::getQuestionRelateCategoryOptions();

        $dao = [];

        return $this->view(compact('render', 'currentCategory', 'categoryList', 'category', 'categoryId', 'dao'));
    }

    /**
     * @param mixed $id
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function edit($id, Request $request)
    {
        if (request()->expectsJson()) {
            $this->archiveService->edit($id, $request->all());

            return ResultsVo::success();
        }

        $dao = $this->archiveService->getDetail($id);
        $render = $this->modelService->byCategoryIdRender($dao['category_id'], $dao['extend_id']);
        $category = $this->categoryService->getSibling($dao['category_id']);
        $currentCategory = Category::query()->findOrFail($dao['category_id']);
        $currentCategory = $currentCategory->toArray();

        // 栏目选择列表
        $categoryList = Category::getQuestionRelateCategoryOptions();

        return $this->view(compact('render', 'currentCategory', 'categoryList', 'dao', 'category'));
    }
}
