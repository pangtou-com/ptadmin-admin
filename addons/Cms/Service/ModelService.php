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

use Addon\Cms\Models\Archive;
use Addon\Cms\Models\Category;
use PTAdmin\Build\Layui;
use PTAdmin\Easy\Easy;

class ModelService
{
    public const MOD_NAME = 'cms';

    public function getModOptions(): array
    {
        $results = Easy::mod()->newQuery()
            ->select('id', 'title')
            ->where('mod_name', self::MOD_NAME)
            ->where('status', 1)
            ->where('is_publish', 1)
            ->get()->toArray();

        return array_to_options($results);
    }

    /**
     * 通过栏目ID获取渲染的表单页面.
     *
     * @param int $categoryId
     * @param int $id
     * @return \PTAdmin\Build\Service\Kernel
     */
    public function byCategoryIdRender(int $categoryId, int $id = 0): ?\PTAdmin\Build\Service\Kernel
    {
        /** @var Category $cate */
        $cate = Category::query()->findOrFail($categoryId);
        if (0 === $cate->mod_id) {
            return null;
        }
        $form = $this->getFormBuildRender($cate->mod_id);
        $form['mod_id'] = ['type' => 'hidden', 'field' => 'mod_id', 'value' => $cate->mod_id];
        $model = ['category_id' => $categoryId];
        if ($id) {
            $model = Easy::handler($cate->mod_id)->show($id);
        }
        $render = Layui::make($model, $form);
        $render->setMethod('post');

        return $render;
    }

    /**
     * 通过ID获取渲染的表单页面.
     *
     * @param $id
     *
     * @return \PTAdmin\Build\Service\Kernel
     */
    public function byIdRender($id): \PTAdmin\Build\Service\Kernel
    {
        /** @var Archive $archive */
        $archive = Archive::query()->findOrFail($id);
        $form = $this->getFormBuildRender($archive->mod_id);
        $handle = Easy::handler($archive->mod_id);
        $data = $handle->cancelFormatting()->show($archive->extend_id);

        $data = array_merge($data, $archive->toArray());

        return Layui::make($data, $form);
    }

    /**
     * 返回表单页面构建规则.
     *
     * @param int   $modId
     * @param mixed $force
     *
     * @return array
     */
    public function getFormBuildRender(int $modId, $force = false): array
    {
        $results = Easy::handler(Easy::mod()->find($modId), $force)->getFormBuildRender();
        // 需要将部分自定义的组件和系统组件进行替换。以免影响表单构建
        $category = Category::getLevels();
        $data = $category_options = [];
        foreach ($category as $item) {
            if ($item['mod_id'] !== $modId) {
                continue;
            }
            $line = $item['lv'] > 0 ? '| '.str_repeat('--', $item['lv']) : '';
            $category_options[] = [
                'label' => $line.' '.$item['title'],
                'value' => $item['id'],
            ];
        }

        foreach ($results as $key => $item) {
            // 归档数据ID 不参与表单构建
            if ('archive_id' === $key) {
                continue;
            }
            if ('category_id' === $key) {
                $item['type'] = 'select';
                $item['options'] = $category_options;
            }
            $data[$key] = $item;
        }

        return $data;
    }
}
