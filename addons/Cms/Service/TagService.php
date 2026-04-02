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

use Addon\Cms\Enum\SEOEnum;
use Addon\Cms\Models\Archive;
use Addon\Cms\Models\Tag;
use Addon\Cms\Models\TagHasArchive;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Exceptions\ServiceException;

class TagService
{
    protected $seoUrlService;

    public function __construct(SeoUrlService $seoUrlService)
    {
        $this->seoUrlService = $seoUrlService;
    }

    public function page(array $search = [], string $order = 'id', string $asc = 'desc'): array
    {
        $allow = [
            'title' => ['op' => 'like'],
            'status' => ['op' => '='],
        ];
        $model = Tag::search($allow, $search);

        return $model->orderBy($order, $asc)->paginate()->toArray();
    }

    public function store($data): void
    {
        DB::beginTransaction();

        try {
            $model = new Tag();
            $model->fill($data);
            $model->status = $data['status'];
            $model->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
        $this->updateSeoRoute();
        Cache::put('cms_category', Tag::query()->get()->toArray());
    }

    public function edit($id, $data): void
    {
        DB::beginTransaction();

        try {
            /** @var Tag $model */
            $model = Tag::query()->findOrFail($id);
            $model->fill($data);
            $model->status = $data['status'];
            $model->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
        $this->updateSeoRoute();
        Cache::put('cms_category', Tag::query()->get()->toArray());
    }

    /**
     * 删除.
     *
     * @param $id
     */
    public function delete($id): void
    {
        $model = Tag::query()->findOrFail($id);
        DB::beginTransaction();

        try {
            $model->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
        $this->updateSeoRoute();
        Cache::put('cms_category', Tag::query()->get()->toArray());
    }

    /**
     * 关联文章.
     *
     * @param array $data
     */
    public function association(array $data): void
    {
        $saveAssociation = [];
        if (!isset($data['tag_id']) || blank($data['tag_id'])) {
            throw new ServiceException('请选择需要关联的标签');
        }
        if (0 === Tag::query()->where('id', $data['tag_id'])->count()) {
            throw new ServiceException('标签不存在');
        }
        $associationArchiveIdArr = explode(',', $data['ids']);
        // 检测待关联文章是否真实存在
        $archiveIdArr = Archive::query()->whereIn('id', $associationArchiveIdArr)->pluck('id')->toArray();
        $diff = array_diff($associationArchiveIdArr, $archiveIdArr);
        if (\count($diff) > 0) {
            throw new ServiceException('id为:【'.implode(',', $diff).'】的关联文章不存在');
        }
        foreach ($associationArchiveIdArr as $archiveId) {
            $saveAssociation[] = [
                'tag_id' => $data['tag_id'],
                'archive_id' => $archiveId,
            ];
        }
        DB::beginTransaction();

        try {
            TagHasArchive::query()->insert($saveAssociation);
            Tag::query()->where('id', $data['tag_id'])->update(['has_archive_num' => \count($saveAssociation)]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    /**
     * 删除关联.
     *
     * @param array $data
     */
    public function delAssociation(array $data): void
    {
        if (!isset($data['tag_id']) || blank($data['tag_id'])) {
            throw new ServiceException('请选择选择正确的标签！');
        }
        if (!isset($data['archive_ids']) || blank($data['archive_ids'])) {
            throw new ServiceException('请选择需要删除的关联文章！');
        }

        $archiveIdArr = explode(',', $data['archive_ids']);
        DB::beginTransaction();

        try {
            TagHasArchive::query()->where('tag_id', $data['tag_id'])->whereIn('archive_id', $archiveIdArr)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    /**
     * 在新增或编辑文章内容时，保存标签信息.
     *
     * @param $tag
     * @param $archiveId
     */
    public function saveTagWhenSaveArchive($tag, $archiveId): void
    {
        // 1、查看是否存在新的标签
        $tag = '1232,sdf,xcvcx,kjj,jghjlk,标签测试,5656,再来一个';
        $newTagArr = explode(',', $tag);
        $newTagArr = array_unique(array_filter($newTagArr));
        $saveTag = [];
        $hadTagTitleArr = Tag::query()->whereIn('title', $newTagArr)->get(['id', 'title'])->toArray();
        $hadTagTitleArr = array_column($hadTagTitleArr, 'title');

        // 2、存在新标签，保存新标签
        $diff = array_diff($newTagArr, $hadTagTitleArr);
        if (\count($diff) > 0) {
            foreach ($diff as $item) {
                $saveTag[] = [
                    'title' => $item,
                    'status' => 1,
                ];
            }
        }

        DB::beginTransaction();

        try {
            if (\count($saveTag) > 0) {
                Tag::query()->insert($saveTag);
            }
            // 3、保存关联
            $tagIdArr = Tag::query()->whereIn('title', $newTagArr)->pluck('id')->toArray();
            $this->archiveAssociationTag($archiveId, $tagIdArr);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    /**
     * 文章关联标签.
     *
     * @param $archiveId
     * @param array $tagIdArr
     */
    public function archiveAssociationTag($archiveId, array $tagIdArr): void
    {
        $saveAssociation = [];
        foreach ($tagIdArr as $tagId) {
            $saveAssociation[] = [
                'tag_id' => $tagId,
                'archive_id' => $archiveId,
            ];
        }
        TagHasArchive::query()->insert($saveAssociation);
    }

    public function getArchiveByTagId($id)
    {
        $archiveIds = TagHasArchive::query()->where('tag_id', $id)->pluck('archive_id')->toArray();

        return Archive::query()->whereIn('id', $archiveIds)->get()->toArray();
    }

    /**
     * 更新标签SEO路由.
     */
    private function updateSeoRoute(): void
    {
        $this->seoUrlService->getUrlArr(SEOEnum::LIST);
    }
}
