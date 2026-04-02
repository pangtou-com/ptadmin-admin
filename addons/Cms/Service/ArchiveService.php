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
use Addon\Cms\Models\ArchiveContent;
use Addon\Cms\Models\Category;
use Addon\Cms\Models\TagHasArchive;
use Addon\Cms\Service\Extend\ArchiveLinkHandle;
use Addon\Cms\Service\Extend\InsideLinkReplaceHandle;
use Addon\Cms\Service\Extend\SensitiveReplaceHandle;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PTAdmin\Admin\Exceptions\ServiceException;
use PTAdmin\Easy\Easy;

class ArchiveService
{
    protected $seoUrlService;

    public function __construct(SeoUrlService $seoUrlService)
    {
        $this->seoUrlService = $seoUrlService;
    }

    public function page(array $search = [], string $order = 'id', string $asc = 'desc'): array
    {
        $allow = [
            'title' => ['op' => 'like', 'fields' => ['title', 'views']],
            'status' => ['op' => '='],
            'category_id' => ['op' => 'in'],
            'ids' => ['op' => 'in', 'field' => 'id'],
            ['op' => 'not in', 'field' => 'id', 'query_field' => 'not_ids'],
        ];
        $model = Archive::search($allow, $search);

        if (isset($search['attribute_text']['value']) && '' !== $search['attribute_text']['value']) {
            $model->whereRaw("attribute & {$search['attribute_text']['value']}");
        }
        if (isset($search['checked'])) {
            $archiveIds = TagHasArchive::query()->where('tag_id', $search['tag_id'])->pluck('archive_id')->toArray();
            if (1 === (int) $search['checked']) {
                $model->whereIn('id', $archiveIds);
            } else {
                $model->whereNotIn('id', $archiveIds);
            }
        }
        $model->with(['category', 'mod']);

        return $model->orderBy($order, $asc)->paginate()->toArray();
    }

    public function parserPage(): void
    {
        $model = Archive::query();
    }

    /**
     * @param mixed $data
     *
     */
    public function store($data): void
    {
        DB::beginTransaction();

        try {
            if (isset($data['content']) && '' !== $data['content']) {
                $data = (new Pipeline(app()))->send($data)->through([
                    // InsideLinkReplaceHandle::class,
                    // SensitiveReplaceHandle::class,
                    // ArchiveLinkHandle::class,
                ])->thenReturn();
            }
            $model = new Archive();
            $model->fill($data);
            $model->status = ArchiveStatusEnum::PUBLISHED;

            $model->save();
            $model->content()->save((new ArchiveContent())->fill(['content' => $data['content'] ?? '']));

            $data['archive_id'] = $model->id;
            if (isset($data['mod_id']) && $data['mod_id'] > 0) {
                $handle = Easy::handler($data['mod_id']);
                $easy = $handle->store($data, false);
                $handle->validate($data);
                $model->extend_id = $easy->id ?? 0;
                $model->extend_table_name = $handle->getModTableName();
                $model->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
        // $this->updateSeoRoute();
    }

    /**
     * 返回内容详情信息.
     *
     * @param $id
     *
     * @return array
     */
    public function getDetail($id): array
    {
        /** @var Archive $model */
        $model = Archive::query()->with('content')->findOrFail($id);

        $model = $model->toArray();
        Archive::addViews($id);
        if ($model['mod_id'] > 0) {
            $handle = Easy::handler($model['mod_id']);
            $model['extend'] = $handle->show($model['extend_id']);
        }
        $model['content'] = null !== $model['content'] ? $model['content']['content'] : '';

        return $model;
    }

    /**
     * 获取页面详情信息，包含上一个数据，下一个数据.
     *
     * @param $id
     *
     * @return array
     */
    public function getViewDetail($id): array
    {
        $data = $this->getDetail($id);

        return [];
    }

    /**
     * @param mixed $id
     * @param mixed $data
     *
     * @throws ValidationException
     */
    public function edit($id, $data): bool
    {
        /** @var Archive $model */
        $model = Archive::query()->findOrFail($id);
        if (!isset($data['attribute']) || count($data['attribute']) === 0) {
            $data['attribute'] = 0;
        }

        if ($model->mod_id > 0) {
            $handle = Easy::handler($model->mod_id);
            $handle->validate($data);

            $handle->edit($data, $model->extend_id, false);
        }

        if ($model->category_id !== (int)$data['category_id']) {
            $newCategory = Category::query()->findOrFail($data['category_id']);;
            $data['related_category_id'] = $newCategory->is_related ? $newCategory->id : 0;
        }
        if (!isset($data['related_category_id'])) {
            $data['related_category_id'] = 0;
        }

        $model->fill($data);
        $model->content()->updateOrCreate(
            ['archive_id' => $model->id],
            ['content' => $data['content'] ?? '']
        );
        $return = $model->save();
        $this->updateSeoRoute();

        return $return;
    }

    public function del($id): void
    {
        /** @var Archive $model */
        $model = Archive::query()->findOrFail($id);
        $model->delete();

        if ($model->mod_id > 0) {
            $handle = Easy::handler($model->mod_id);
            $handle->delete($model->extend_id);
        }
        $this->updateSeoRoute();
    }

    public function getTagList($id): void
    {
    }

    private function updateSeoRoute(): void
    {
//        $this->seoUrlService->getUrlArr(SEOEnum::DETAIL);
        // $this->seoUrlService->getAllPhp();
    }
}
