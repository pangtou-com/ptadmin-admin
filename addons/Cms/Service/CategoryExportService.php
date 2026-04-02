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

namespace Addon\Cms\Service;

use Addon\Cms\Enum\ArchiveStatusEnum;
use Addon\Cms\Enum\SEOEnum;
use Addon\Cms\Models\Archive;
use Addon\Cms\Models\Category;
use App\Exceptions\ServiceException;
use PTAdmin\Addon\Service\DirectivesDTO;
use PTAdmin\Admin\Enum\StatusEnum;

class CategoryExportService extends AbstractExportService
{
    private $noProcessing = ['cover'];
    private $checkFieldIsNullArr = ['cover'];

    public function handle(DirectivesDTO $dto): array
    {
        $allow = [
            ['field' => 'id', 'op' => '=', 'query_field' => 'category_id'],
            ['field' => 'dir_name', 'op' => '=', 'query_field' => 'category'],
            'mod_id' => ['op' => 'in'],
            'is_single' => ['op' => '='],
        ];
        $data = $this->dataProcessing($dto->all());
        $filterMap = Category::search($allow, $data);
        if ($dto->getAttribute("pid")) {
            $filterMap->where('parent_id', $dto->getAttribute('pid'));
        }

        $filterMap = $this->fieldHasOrNot($filterMap, $data, $this->checkFieldIsNullArr);
        $filterMap = $this->limitAndOrder($filterMap, $dto);

        return $filterMap->where('status', StatusEnum::ENABLE)->get()->toArray();
    }

    /**
     * 分类列表指令.
     *
     * @param DirectivesDTO $DTO
     *
     * @return array
     */
    public function categoryLists(DirectivesDTO $DTO): array
    {
        $seoService = new SeoService(new SeoUrlService());
        $categoryUrlConfig = $seoService->getConfig()[SEOEnum::LIST]['url'];
        $routeQueryField = $seoService->parserParams($categoryUrlConfig);
        $search = [];
        foreach ($routeQueryField as $value) {
            $search[$value] = request()->route($value) ?? (request()->get($value) ?? null);
        }
        foreach ($DTO->all() as $key => $value) {
            $search[$key] = isset($search[$key]) ? $search[$key].'|'.$value : $value;
        }
        if (isset($search['category_id'])) {
            unset($search['category']);
        }else {
            $search['category'] = explode('|', $search['category']);
        }
        $allow = [
            ['field' => 'id', 'op' => 'in', 'query_field' => 'category_id'],
            ['field' => 'dir_name', 'op' => 'in', 'query_field' => 'category'],
            ['field' => 'id', 'op' => 'not in', 'query_field' => 'no_id'],
        ];

        $data = $this->dataProcessing($search, $this->noProcessing);
        $sons = $DTO->getAttribute('sons') ?? false;
        $withParent = $DTO->getAttribute('with_parent') ?? false;
        $rootCategoryIds = [];
        if (!isset($data['category_id']) && isset($data['category'])) {
            $categoryIds = $this->getCategoryIdByDirName($data['category']);
            if (null === $categoryIds) {
                return $data;
            }
            $data['category_id'] = $categoryIds;
            unset($data['category']);
        }
        foreach ($data['category_id'] as $categoryId) {
            $rootCategoryIds[] = $this->getParentId((int) $categoryId);
        }
        $data['category_id'] = $rootCategoryIds;
        $data = $this->getCid($data,$sons,$withParent);
        $data['category_id'] = array_unique(array_merge($data['category_id'] ?? [],$data['cid'] ?? []));
        $filterMap = Category::search($allow, $data);
        foreach ($DTO->getOrder() as $key => $value) {
            $filterMap->orderBy($key, $value);
        }

        return $filterMap->where('status', StatusEnum::ENABLE)->get()->toArray();
    }

    public function getCategoryListByParentId(DirectivesDTO $DTO): array
    {
        $seoService = new SeoService(new SeoUrlService());
        $categoryUrlConfig = $seoService->getConfig()[SEOEnum::TAG]['url'];
        $routeQueryField = $seoService->parserParams($categoryUrlConfig);
        $search = [];
        foreach ($routeQueryField as $value) {
            $search[$value] = request()->route($value) ?? (request()->get($value) ?? null);
        }
        $allow = [
            ['field' => 'id', 'op' => '=', 'query_field' => 'category_id'],
            ['field' => 'dir_name', 'op' => '=', 'query_field' => 'category'],
            'mod_id' => ['op' => 'in'],
            'is_single' => ['op' => '='],
            ['field' => 'parent_id', 'op' => '=', 'query_field' => 'pid'],
            ['field' => 'id', 'op' => 'not in', 'query_field' => 'no_id'],
            ['field' => 'dir_name', 'op' => 'not in', 'query_field' => 'no_category'],
        ];
        $page = request()->route('page') ?? (request()->get('page') ?? null);
        $limit = request()->get('limit') ?? $DTO->getLimit() ?? 8;
        $search = array_merge($search, $DTO->all());

        $data = $this->dataProcessing($search, $this->noProcessing);
        $filterMap = Category::search($allow, $data);
        if (isset($data['fields']) && \count($data['fields']) > 0) {
            $filterMap->select($data['fields']);
            if (!\in_array('parent_id', $data['fields'], true)) {
                $filterMap->addSelect('parent_id');
            }
        }
        $filterMap = $this->limitAndOrder($filterMap, $DTO);
//        $return = $filterMap->where('status', StatusEnum::ENABLE)->paginate($limit, $data['fields'] ?? ['*'], 'page', $page)->toArray();
        $return = $filterMap->where('status', StatusEnum::ENABLE)->get()->toArray();

        return $return;
    }

    /**
     * 获取父id.
     *
     * @param int $id
     *
     * @return int
     */
    public function getParentId(int $id): int
    {
        // 使用递归查询父级ID
        while (true) {
            $category = Category::query()->find($id);
            if (null === $category) {
                throw new ServiceException("Category with ID {$id} not found");
            }
            if (0 === $category->parent_id) {
                return $category->id;
            }
            $id = $category->parent_id;
        }
    }

    /**
     * 列表指令.
     *
     * @param DirectivesDTO $DTO
     *
     * @return array
     */
    public function lists(DirectivesDTO $DTO): array
    {
        $seoService = new SeoService(new SeoUrlService());
        $categoryUrlConfig = $seoService->getConfig()[SEOEnum::LIST]['url'];
        $routeQueryField = $seoService->parserParams($categoryUrlConfig);
        $search = [];
        $fromRoute = (bool) request()->route('page');
        foreach ($routeQueryField as $value) {
            $search[$value] = request()->route($value) ?? (request()->get($value) ?? null);
        }
        $page = request()->route('page') ?? (request()->get('page') ?? null);
        $limit = request()->get('limit') ?? $DTO->getLimit() ?? 8;
        foreach ($DTO->all() as $key => $value) {
            if (\array_key_exists($key, $search)) {
                $search[$key] = $search[$key] ? $search[$key].'|'.$value : $value;
            }
        }
        $data = $this->dataProcessing($search, $this->noProcessing);
        $sons = $DTO->getAttribute('sons') ?? false;
        $data = $this->getCid($data,$sons);
        $data['category_id'] = array_unique(array_merge($data['category_id'],$data['cid']));
        $filterMap = Archive::query()->whereIn('category_id', $data['category_id']);
        if (isset($data['fields']) && \count($data['fields']) > 0) {
            $filterMap->select($data['fields']);
            if (!\in_array('parent_id', $data['fields'], true)) {
                $filterMap->addSelect('parent_id');
            }
        }
        // 关联信息
        $with = $DTO->getAttribute("with");
        $with = isset($with) ? $this->dataProcessing([$with])[0] : [];
        if (count($with) > 0) {
            $filterMap->with($this->getWithInfo($with));
        }
        $filterMap = $filterMap->where('status', ArchiveStatusEnum::PUBLISHED);
        $filterMap = $this->limitAndOrder($filterMap, $DTO);
        $return = $filterMap->paginate($limit, $data['fields'] ?? ['*'], 'page', $page)->toArray();

        $returnData = $return['data'];
        unset($return['data']);
        $return['pageFromRoute'] = $fromRoute;
        $return['page'] = $page;
        view()->share('__current_page__', $return);

        return $returnData;
    }


    /**
     * 分页指令.
     *
     * @param DirectivesDTO $DTO
     *
     * @return string
     */
    public function page(DirectivesDTO $DTO): string
    {
        $data = $DTO->all();
        $pageInfo = app('view')->shared('__current_page__') ?? $DTO->getAttribute('data');
        if (!isset($pageInfo)) {
            return '';
        }
        if (!isset($pageInfo['current_page'])) {
            throw new ServiceException('分页数据不存在');
        }
        // 总页码
        $totalPages = (int) ceil($pageInfo['total'] / $pageInfo['per_page']);
        $layouts = explode(',', $data['layouts']);
        $return = [
            'active' => $data['active'] ?? 'active',
            'class' => $data['class'] ?? 'page',
            'align' => $data['align'] ?? 'center',
        ];

//        $layoutMappings = ['home', 'prev', 'page', 'next', 'last', 'limit', 'to'];
        $layoutMappings = ['home', 'prev', 'page', 'next', 'last'];
        $limits = $data['limit'] ?? ['10', '20', '50', '100'];
        $html = '';
        foreach ($layouts as $value) {
            if (!\in_array($value, $layoutMappings, true)) {
                continue;
            }

            if ('page' === $value) {
                $html .= $this->numPage($pageInfo, $totalPages, $return);

                continue;
            }

            $html .= $this->{$value}($pageInfo, $limits);
        }

        return "<div class='{$return['class']}' align='{$return['align']}'>{$html}</div>";
    }

    /**
     * 根据栏目名称获取栏目id.
     *
     * @param $categories
     *
     * @return null|array
     */
    protected function getCategoryIdByDirName($categories): ?array
    {
        $allCategory = Category::getLevels();
        if (0 === \count($allCategory)) {
            return null;
        }
        $allCategory = array_to_map($allCategory, 'dir_name', 'id');
        $categoryIds = [];
        $searchCategory = \is_array($categories) ? $categories : [$categories];
        foreach ($searchCategory as $category) {
            $categoryIds[] = $allCategory[$category];
        }

        return $categoryIds;
    }

    /**
     * 首页.
     *
     * @param $pageInfo
     *
     * @return string
     */
    private function home($pageInfo): string
    {
        // 匹配最后一个数字的正则表达式，允许后缀为任意字符，并清空后面的内容
        // preg_replace('/(\d+)(\.\w+).*$/', '1$2', $pageInfo['path']).'?limit='.$pageInfo['per_page']
        // http://ttt.com/tag/1/9.html?page=1  =>  http://ttt.com/tag/1/1.html
        $url = $pageInfo['pageFromRoute'] ? preg_replace('/(\d+)(\.\w+).*$/', '1$2', $pageInfo['path']).'?limit='.$pageInfo['per_page'] : $pageInfo['first_page_url'].'&limit='.$pageInfo['per_page'];

        return "<a href='{$url}'>首页</a>";
    }

    /**
     * 上一页.
     *
     * @param $pageInfo
     *
     * @return string
     */
    private function prev($pageInfo): string
    {
        // 不存在上一页时
        if (null === $pageInfo['prev_page_url']) {
            return "<li class='item previous'><a href='#'>上一页</a></li>";
        }
        $url = $pageInfo['pageFromRoute']
            ? preg_replace('/(\d+)(\.\w+).*$/', ($pageInfo['current_page'] - 1).'$2', $pageInfo['path']).'?limit='.$pageInfo['per_page']
            : $pageInfo['prev_page_url'].'&limit='.$pageInfo['per_page'];

        return "<li class='item previous'><a href='{$url}'>上一页</a></li>";
    }

    /**
     * 生成分页HTML.
     *
     * @param array $pageInfo
     * @param int   $totalPages
     * @param array $return
     *
     * @return string
     */
    private function numPage(array $pageInfo, int $totalPages, array $return): string
    {
        // 当前仅返回 当前页码/总页码
        $totalPages = 0 !== $totalPages ? $totalPages : 1;

        return "<li class='item'><a href='javascript:void(0)'>{$pageInfo['current_page']}/{$totalPages}</a></li>";
        $html = '';
        $currentPage = $pageInfo['current_page'] ?? 1; // 默认当前页为1
        $perPage = $pageInfo['per_page'] ?? 10; // 默认每页条数为10
        $pageFromRoute = $pageInfo['pageFromRoute'] ?? false;

        if ($totalPages <= 5) {
            for ($i = 1; $i <= $totalPages; ++$i) {
                $html .= $this->generatePageLink($i, $currentPage, $pageInfo['path'], $return['active'], (string) $perPage, $pageFromRoute);
            }

            return $html;
        }
        if ($currentPage >= 5) {
            // 处理第一页链接和省略号
            $html .= "<a href='".($pageInfo['pageFromRoute'] ? preg_replace('/(\d+)(\.\w+).*$/', '1$2', $pageInfo['path']).'?limit='.$pageInfo['per_page'] : $pageInfo['first_page_url'].'&limit='.$pageInfo['per_page'])."'> 1 </a>";
            $html .= '<label>...</label>';

            // 页码范围的确定
            $start = $totalPages - $currentPage < 4 ? max($totalPages - 4, 1) : $currentPage - 2;
            $end = $totalPages - $currentPage < 4 ? $totalPages : $currentPage + 2;

            for ($i = $start; $i <= $end; ++$i) {
                if ($i >= 1 && $i <= $totalPages) {
                    $html .= $this->generatePageLink($i, $currentPage, $pageInfo['path'], $return['active'], (string) $perPage, $pageFromRoute);
                }
            }

            // 添加最后一页链接
            if ($end < $totalPages) {
                $html .= '<label>...</label>';
                $html .= "<a href='".($pageInfo['pageFromRoute'] ? preg_replace('/(\d+)(\.\w+).*$/', $pageInfo['last_page'].'$2', $pageInfo['path']).'?limit='.$pageInfo['per_page'] : $pageInfo['last_page_url'].'&limit='.$pageInfo['per_page'])."'> {$totalPages} </a>";
            }

            return $html;
        }
        // 当前页少于5，显示第一页到第五页
        for ($i = 1; $i <= 5; ++$i) {
            $html .= $this->generatePageLink($i, $currentPage, $pageInfo['path'], $return['active'], (string) $perPage, $pageFromRoute);
        }
        $html .= '<label>...</label>';
        $html .= "<a href='{$pageInfo['path']}?page={$totalPages}'>{$totalPages}</a>";

        return $html;
    }

    /**
     * 生成页码链接.
     *
     * @param int    $pageNumber    当前页码
     * @param int    $currentPage   当前页
     * @param string $path          基本链接路径
     * @param string $activeClass   当前页样式
     * @param string $limit
     * @param bool   $pageFromRoute
     *
     * @return string
     */
    private function generatePageLink(int $pageNumber, int $currentPage, string $path, string $activeClass, string $limit, bool $pageFromRoute): string
    {
        $url = $pageFromRoute
            ? preg_replace('/(\d+)(\.\w+).*$/', $pageNumber.'$2', $path).'?limit='.$limit
            : $path.'?page='.$pageNumber.'&limit='.$limit;

        return $pageNumber === $currentPage
        ? "<span class='{$activeClass}'> {$pageNumber} </span>"
        : "<a href='{$url}'> {$pageNumber} </a>";
    }

    /**
     * 下一页.
     *
     * @param $pageInfo
     *
     * @return string
     */
    private function next($pageInfo): string
    {
        // 不存在下一页时
        if (null === $pageInfo['next_page_url']) {
            return "<li class='item next'><a href='#'>下一页</a></li>";
        }
        $url = $pageInfo['pageFromRoute']
            ? preg_replace('/(\d+)(\.\w+).*$/', ($pageInfo['current_page'] + 1).'$2', $pageInfo['path']).'?limit='.$pageInfo['per_page']
            : $pageInfo['next_page_url'].'&limit='.$pageInfo['per_page'];

        return "<li class='item next'><a href='{$url}'>下一页</a></li>";
    }

    /**
     * 末页.
     *
     * @param $pageInfo
     *
     * @return string
     */
    private function last($pageInfo): string
    {
        $url = $pageInfo['pageFromRoute'] ? preg_replace('/(\d+)(\.\w+).*$/', $pageInfo['last_page'].'$2', $pageInfo['path']).'?limit='.$pageInfo['per_page'] : $pageInfo['last_page_url'].'&limit='.$pageInfo['per_page'];

        return "<a href='{$url}'>末页</a>";
    }

    /**
     * 跳转至.
     *
     * @param $pageInfo
     *
     * @return string
     */
    private function to($pageInfo): string
    {
        return "<form><input type='text' name='page' value='".$pageInfo['current_page']."'><button>提交</button><form>";
    }

    /**
     * 限制条数下拉框.
     *
     * @param $pageInfo
     * @param $limits
     *
     * @return string
     */
    private function limit($pageInfo, $limits): string
    {
        $form = '';
        foreach ($limits as $item) {
            $form .= "<option value='{$item}' ".($item === $pageInfo['per_page'] ? 'selected' : '').'>'.$item.'条/页</option>';
        }

        return "<select name='limit'>{$form}</select>";
    }

    /**
     * 获取所有满足条件的下级id.
     *
     * @param $data
     * @param bool $sons
     * @param bool $withParent
     * @return mixed
     */
    private function getCid($data,bool $sons = false,bool $withParent = false)
    {
        if (!isset($data['category_id']) || !\count($data['category_id']) > 0) {
            if (!isset($data['category'])) {
                return $data;
            }
            $categoryIds = $this->getCategoryIdByDirName($data['category']);
            if (null === $categoryIds) {
                return $data;
            }
            $data['category_id'] = $categoryIds;
            unset($data['category']);
        }
        $data['category_id'] = array_map(function ($id) {
            return (int) $id;
        }, $data['category_id']);
        if ($sons) {
            $cids = $this->getChildrenIds($data['category_id'], [], Category::getLevels());
            $data['cid'] = $withParent ? array_unique(array_merge($data['cid'], $cids)) : array_unique($cids);
        }

        return $data;
    }

    /**
     * 获取所有子栏目.
     *
     * @param array $ids
     * @param array $all
     * @param mixed $data
     *
     * @return array
     */
    private function getChildrenIds(array $ids, array $all, $data): array
    {
        $childrenIds = [];
        // 筛选出当前 ID 的所有子级 ID
        foreach ($data as $datum) {
            if (\in_array($datum['parent_id'], $ids, true)) {
                $childrenIds[] = $datum['id'];
            }
        }
        // 递归调用直到没有新的子级 ID
        if (\count($childrenIds) > 0) {
            return $this->getChildrenIds($childrenIds, array_merge($childrenIds, $ids), $data);
        }

        return array_merge($all, $ids);
    }
}
