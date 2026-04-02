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
use Addon\Cms\Models\Category;
use Addon\Cms\Models\Tag;
use Addon\Cms\Models\TopicAssociation;
use Addon\Cms\Models\TopicAssociationCorrelation;
use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Exceptions\ServiceException;

class TopicAssociationService
{
    public function page(array $search = [], string $order = 'id', string $asc = 'desc'): array
    {
        $allow = [
            'topic_id' => ['op' => '='],
        ];
        $model = TopicAssociation::search($allow, $search);

        return $model->orderBy($order, $asc)->paginate()->toArray();
    }

    public function store($data): void
    {
        DB::beginTransaction();

        try {
            $model = new TopicAssociation();
            $model->fill($data);
            $model->save();
            $this->saveCorrelation($model->id, $data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    public function edit($id, $data): void
    {
        DB::beginTransaction();

        try {
            /** @var TopicAssociation $model */
            $model = TopicAssociation::query()->findOrFail($id);
            $model->fill($data);
            $model->save();
            $this->saveCorrelation($model->id, $data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    public function status($id): void
    {
        DB::beginTransaction();

        try {
            /** @var TopicAssociation $model */
            $model = TopicAssociation::query()->findOrFail($id);
            $model->status = !$model->status;
            $model->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    public function saveCorrelation(int $id, array $data): void
    {
        $typeArr = [
            1 => [
                'tags' => 1,
                'recommend' => 2,
                'column' => 3,
            ],
            2 => [
                'selected_ids' => 4,
            ],
        ];
        $types = $typeArr[$data['association_type']];
        TopicAssociationCorrelation::query()->where('association_id', $id)->delete();
        $saveData = [];
        foreach ($types as $key => $type) {
            if (isset($data[$key])) {
                $valueArr = \is_array($data[$key]) ? $data[$key] : explode(',', $data[$key]);
                foreach ($valueArr as $value) {
                    preg_match_all('/\d+/', $value, $matches);
                    $saveData[] = [
                        'type' => $type,
                        'association_id' => $id,
                        'correlation_id' => (int) $matches[0][0],
                    ];
                }
            }
        }
        if (\count($saveData) > 0) {
            TopicAssociationCorrelation::query()->insert($saveData);
        }
    }

    public function getAttribute(): array
    {
        $data = [
            'attribute' => [],
        ];
        foreach (AttributeEnum::getAllDescription() as $key => $value) {
            $data['attribute'][] = [
                'label' => $value,
                'value' => $key,
            ];
        }

        $data['tag'] = Tag::query()
            ->selectRaw('title as label, id as value, id, title')
            ->get()
            ->toArray()
        ;

        $categories = Category::query()
            ->selectRaw('title as label, id as value, id, title, parent_id')
            ->get()->toArray();
        $data['categories'] = infinite_tree($categories, 0, 'parent_id', 'value');

        return $data;
    }

    public function correlation($id)
    {
        $data = [
            1 => [],
            2 => [],
            3 => [],
            4 => [],
        ];
        $correlations = TopicAssociationCorrelation::query()
            ->where('association_id', $id)
            ->get()
            ->toArray()
        ;
        foreach ($correlations as $correlation) {
            $data[$correlation['type']][] = $correlation['correlation_id'];
        }

        return [
            1 => implode(',', $data[1]),
            2 => implode(',', $data[2]),
            3 => implode(',', $data[3]),
            4 => implode(',', $data[4]),
        ];
    }
}
