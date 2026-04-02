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

use Addon\Cms\Enum\AttributeEnum;
use Addon\Cms\Models\Archive;
use Addon\Cms\Models\Category;
use Illuminate\Support\Facades\DB;
use PTAdmin\Addon\Service\DirectivesDTO;
use PTAdmin\Easy\Easy;

/**
 * 文章导出模版调用.
 */
class ArchiveExportService extends AbstractExportService
{
    private $checkFieldIsNullArr = ['cover'];

    public function handle(DirectivesDTO $DTO): array
    {
        $allow = [
            ['field' => 'id', 'query_field' => 'ids', 'op' => 'in'],
            ['field' => 'category_id', 'query_field' => 'no_cid', 'op' => 'not in'],
            'mod_id' => ['op' => 'in'],
            ['field' => 'category_id', 'query_field' => 'cid', 'op' => 'in'],
            ['field' => 'related_category_id', 'query_field' => 'related_id', 'op' => '='],
        ];
        $data = $this->dataProcessing($DTO->all());
        if (!isset($data['cid']) && isset($data['category'])) {
            $category = Category::query()->where('dir_name', $data['category'])->first();
            $data['cid'] = [$category->id];
        }

        $sons = $DTO->getAttribute('sons', false);
        $withParent = $DTO->getAttribute('with_parent', false);

        $data = $this->getCid($data, $sons, $withParent);
        $archiveFilterMap = Archive::search($allow, $data);
        if (isset($data['fields']) && \count($data['fields']) > 0) {
            $archiveFilterMap->select($data['fields']);
        }
        $title = $DTO->getAttribute('title');
        if (!blank($title)) {
            $archiveFilterMap->where('title','like', "%{$title}%");
        }
        $archiveFilterMap = $this->bitwiseAnd($archiveFilterMap, $data, ['flag' => 'attribute']);
        $archiveFilterMap = $this->bitwiseOr($archiveFilterMap, $data, ['no_flag' => 'attribute']);
        // 是否存在封面图
        $archiveFilterMap = $this->fieldHasOrNot($archiveFilterMap, $data, $this->checkFieldIsNullArr);
        $archiveFilterMap = $this->limitAndOrder($archiveFilterMap, $DTO);

        $results = $archiveFilterMap->get()->toArray();
        // 拉取扩展表数据
        if (isset($data['with']) && count($results) > 0) {
            // 提取存在扩展表的数据
            $extend = [];
            foreach ($results as $result) {
                if ($result['extend_id'] && $result['extend_table_name']) {
                    $extend[$result['extend_table_name']][] = $result['extend_id'];
                }
            }
            // 提取表数据
            $extendData = [];
            foreach ($extend as $table => $ids) {
                $temp = Easy::handler($table)->newQuery()->whereIn("id", $ids)->get()->toArray();
                $extendData[$table] = array_to_map($temp);
            }
            // 合并数据
            foreach ($results as &$result) {
                $result['extend'] = [];
                if (!$result['extend_table_name']) {
                    continue;
                }
                if (!isset($extendData[$result['extend_table_name']])) {
                    continue;
                }
                $result['extend'] = $extendData[$result['extend_table_name']][$result['extend_id']] ?? [];
            }
            unset($result);
        }

        return $results;
    }

    /**
     * 解析栏目.
     *
     * @param array $params
     *
     * @return null|array
     */
    public function resolveCategory(array $params): ?array
    {
        if (isset($params['category'])) {
            $category = Category::query()->where('dir_name', $params['category'])->first();

            return isset($category) ? $category->toArray() : null;
        }

        if (isset($params['category_id'])) {
            return Category::query()->find($params['category_id']);
        }

        return null;
    }

    public function single_detail(DirectivesDTO $DTO): ?array
    {
        $data = $DTO->all();

        $category = $this->resolveCategory($data);
        if (!$category || 1 !== (int) $category['is_single']) {
            return null;
        }

        $archiveFilterMap = Archive::query()->where('category_id', $category['id'])->with(['content']);
        if (isset($data['with']) && \count($data['with']) > 0) {
            $archiveFilterMap->with($this->getWithInfo($data['with']));
        }

        $detail = $archiveFilterMap->first();

        return $detail ? [$detail->toArray()] : null;
    }

    public function detail(DirectivesDTO $DTO): ?array
    {
        $data = $DTO->all();
        $archiveFilterMap = Archive::query()->where('id', $data['ids'])->with(['content']);
        if (isset($data['with']) && \count($data['with']) > 0) {
            $archiveFilterMap->with($this->getWithInfo($data['with']));
        }

        $detail = $archiveFilterMap->first();

        return $detail ? [$detail->toArray()] : null;
    }

    public function prev(DirectivesDTO $DTO): array
    {
        $detail = app('view')->shared('d');
        $prev = Archive::query()->where('category_id', $detail['category_id'])->where('id', '<', $detail['id'])->orderBy('id', 'desc')->first();

        return [
            $prev ? $prev->toArray() : [],
        ];
    }

    public function next(DirectivesDTO $DTO): array
    {
        $detail = app('view')->shared('d');
        $next = Archive::query()->where('category_id', $detail['category_id'])->where('id', '>', $detail['id'])->orderBy('id', 'asc')->first();

        return [
            $next ? $next->toArray() : [],
        ];
    }

    /**
     * 热门.
     *
     * @param DirectivesDTO $DTO
     *
     * @return array
     */
    public function hot(DirectivesDTO $DTO): array
    {
        $data = $DTO->all();
        $data['flag'] = AttributeEnum::HOT;
        if (isset($data['no_flag'])) {
            unset($data['no_flag']);
        }

        return $this->handle(DirectivesDTO::build($data));
    }

    /**
     * 推荐.
     *
     * @param DirectivesDTO $DTO
     *
     * @return array
     */
    public function recommended(DirectivesDTO $DTO): array
    {
        $data = $DTO->all();
        $data['flag'] = AttributeEnum::RECOMMENDED;
        if (isset($data['no_flag'])) {
            unset($data['no_flag']);
        }

        return $this->handle(DirectivesDTO::build($data));
    }

    /**
     * 头条.
     *
     * @param DirectivesDTO $DTO
     *
     * @return array
     */
    public function headlines(DirectivesDTO $DTO): array
    {
        $data = $DTO->all();
        $data['flag'] = AttributeEnum::HEADLINES;
        if (isset($data['no_flag'])) {
            unset($data['no_flag']);
        }

        return $this->handle(DirectivesDTO::build($data));
    }

    /**
     * 最新.
     *
     * @param DirectivesDTO $DTO
     *
     * @return array
     */
    public function last(DirectivesDTO $DTO): array
    {
        $data = $DTO->all();
        $data['flag'] = AttributeEnum::LAST;
        if (isset($data['no_flag'])) {
            unset($data['no_flag']);
        }

        return $this->handle(DirectivesDTO::build($data));
    }

    /**
     * 精选.
     *
     * @param DirectivesDTO $DTO
     *
     * @return array
     */
    public function featured(DirectivesDTO $DTO): array
    {
        $data = $DTO->all();
        $data['flag'] = AttributeEnum::FEATURED;
        if (isset($data['no_flag'])) {
            unset($data['no_flag']);
        }

        return $this->handle(DirectivesDTO::build($data));
    }

    /**
     * 独家.
     *
     * @param DirectivesDTO $DTO
     *
     * @return array
     */
    public function exclusive(DirectivesDTO $DTO): array
    {
        $data = $DTO->all();
        $data['flag'] = AttributeEnum::EXCLUSIVE;
        if (isset($data['no_flag'])) {
            unset($data['no_flag']);
        }

        return $this->handle(DirectivesDTO::build($data));
    }

    /**
     * 有图.
     *
     * @param DirectivesDTO $DTO
     *
     * @return array
     */
    public function image(DirectivesDTO $DTO): array
    {
        $data = $DTO->all();
        $data['flag'] = AttributeEnum::IMAGE;
        if (isset($data['no_flag'])) {
            unset($data['no_flag']);
        }

        return $this->handle(DirectivesDTO::build($data));
    }

    /**
     * 首页推荐.
     *
     * @param DirectivesDTO $DTO
     * @return array
     */
    public function index(DirectivesDTO $DTO): array
    {
        $data = $DTO->all();
        $data['flag'] = AttributeEnum::INDEX;
        if (isset($data['no_flag'])) {
            unset($data['no_flag']);
        }

        return $this->handle(DirectivesDTO::build($data));
    }

    /**
     * 获取所有满足条件的下级id.
     *
     * @param $data
     * @param $sons
     * @param $withParent
     * @return mixed
     */
    private function getCid($data, $sons, $withParent)
    {
        if (!isset($data['cid']) || !\count($data['cid']) > 0) {
            return $data;
        }
        if ($sons) {
            $data['cid'] = array_map(function ($id) {
                return (int) $id;
            }, $data['cid']);
            $cids = $this->getChildrenIds($data['cid'], [], Category::getLevels());

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
