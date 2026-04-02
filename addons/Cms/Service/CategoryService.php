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

namespace Addon\Cms\Service;

use Addon\Cms\Models\Category;
use Illuminate\Support\Facades\DB;
use Overtrue\Pinyin\Pinyin;
use PTAdmin\Admin\Exceptions\ServiceException;

class CategoryService
{
    protected $seoUrlService;

    public function __construct(SeoUrlService $seoUrlService)
    {
        $this->seoUrlService = $seoUrlService;
    }

    public function getAll(): array
    {
        return Category::query()->with('mod')->get()->toArray();
    }

    public function store($data): void
    {
        DB::beginTransaction();
        if ($data['parent_ids'] && \is_array($data['parent_ids'])) {
            $data['parent_id'] = end($data['parent_ids']);
        }

        try {
            $model = new Category();
            $model->fill($data);
            $model->alias = (isset($data['alias']) && $data['alias']) ? $data['alias'] : $this->generateDirName($data);
            $model->status = (int) $data['status'];
            $model->mod_id = (int) $data['mod_id'];
            $model->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    public function edit($id, $data): void
    {
        DB::beginTransaction();
        if ($data['parent_ids'] && \is_array($data['parent_ids'])) {
            $data['parent_id'] = end($data['parent_ids']);
        }

        try {
            /** @var Category $model */
            $model = Category::query()->findOrFail($id);
            if (isset($data['alias']) && blank($data['alias'])) {
                unset($data['alias']);
            }
            $model->fill($data);
            if (!isset($data['template_list']) || '' === $data['template_list']) {
                $model->template_list = null;
            }
            if (!isset($data['template_detail']) || '' === $data['template_detail']) {
                $model->template_detail = null;
            }
            if (!isset($data['template_channel']) || '' === $data['template_channel']) {
                $model->template_channel = null;
            }
            $model->status = $data['status'];
            $model->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
        $this->updateSeoRoute();
    }

    public function del($id): void
    {
        $model = Category::query()->findOrFail($id);
        if (Category::query()->where('parent_id', $id)->exists()) {
            throw new ServiceException('请先删除子分类');
        }
        $model->delete();
    }

    /**
     * 返回下拉菜单选项格式数据.
     *
     * @return array[]
     */
    public function getOption(): array
    {
        $data = Category::getLevels();
        $res = [['label' => '顶级栏目', 'value' => 0]];
        foreach ($data as $datum) {
            $line = '';
            if ($datum['lv'] > 0) {
                $line = '| '.str_repeat('--', $datum['lv']);
            }
            $res[] = [
                'label' => $line.' '.$datum['title'],
                'value' => $datum['id'],
            ];
        }

        return $res;
    }

    /**
     * 获取支持的栏目.
     *
     * @param $id
     *
     * @return array
     */
    public function getSibling($id): array
    {
        /** @var Category $cate */
        $cate = Category::query()->findOrFail($id);
        $data = Category::getLevels();
        $res = [];
        foreach ($data as $datum) {
            $line = '';
            if ($datum['lv'] > 0) {
                $line = '| '.str_repeat('--', $datum['lv']);
            }
            $res[] = [
                'label' => $line.' '.$datum['title'],
                'value' => $datum['id'],
                'disabled' => !($cate->mod_id === $datum['mod_id']),
            ];
        }

        return $res;
    }

    /**
     * 获取栏目详情.
     *
     * @param $id
     *
     * @return array
     */
    public function byId($id): array
    {
        return Category::query()->findOrFail($id)->toArray();
    }

    /**
     * 获取模版信息.
     *
     * @param $id
     *
     * @return string
     */
    public function getTemplate($id): string
    {
        $cate = Category::query()->findOrFail($id)->toArray();

        return '';
    }

    /**
     * 生成目录名称.
     *
     * @param $data
     *
     * @return string
     */
    private function generateDirName($data): string
    {
        $dirNameMap = array_to_map(Category::getLevels(), 'id', 'alias');
        $pinyin = new Pinyin();
        $dir_name = preg_replace_callback('/[\x{4e00}-\x{9fa5}]+/u', function ($matches) use ($pinyin) {
            return $pinyin->abbr($matches[0]);
        }, $data['title']);

        if (\in_array($dir_name, $dirNameMap, true)) {
            return $this->getRandomDirName($dir_name, $dirNameMap);
        }

        return $dir_name;
    }

    /**
     * 随机生成目录名称.
     *
     * @param $dirName
     * @param array $dirNameArr
     *
     * @return string
     */
    private function getRandomDirName($dirName, array $dirNameArr): string
    {
        $newDirName = $dirName.'_'.substr(uniqid(), -4);
        if (\in_array($newDirName, $dirNameArr, true)) {
            $this->getRandomDirName($dirName, $dirNameArr);
        }

        return $newDirName;
    }

    /**
     * 更新栏目SEO路由.
     */
    private function updateSeoRoute(): void
    {
        // $this->seoUrlService->getUrlArr(SEOEnum::CHANNEL);
        // $this->seoUrlService->getUrlArr(SEOEnum::LIST);
    }
}
