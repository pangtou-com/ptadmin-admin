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
use Addon\Cms\Models\Topic;
use Exception;
use Illuminate\Support\Facades\DB;
use Overtrue\Pinyin\Pinyin;
use PTAdmin\Admin\Exceptions\ServiceException;

class TopicService
{
    protected $seoUrlService;

    public function __construct(SeoUrlService $seoUrlService)
    {
        $this->seoUrlService = $seoUrlService;
    }

    public function page(array $search = [], string $order = 'id', string $asc = 'desc'): array
    {
        $allow = [];
        $model = Topic::search($allow, $search);

        return $model->orderBy($order, $asc)->paginate()->toArray();
    }

    public function store($data): void
    {
        DB::beginTransaction();
        $model = new Topic();
        $data['banners'] = $data['banners'] ?? [];
        $data['banners'] = json_encode($this->saveImage($data['banners']));
        $model->fill($data);
        $model->save();

        try {
            if (!isset($data['url'])) {
                $model->url = $this->getUrl($model);
                $model->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
        $this->seoUrlService->getUrlArr(SEOEnum::TOPIC);
    }

    public function edit($id, $data): void
    {
        DB::beginTransaction();

        try {
            /** @var Topic $model */
            $model = Topic::query()->findOrFail($id);
            $data['banners'] = $data['banners'] ?? [];
            $data['banners'] = json_encode($this->saveImage($data['banners']));
            $model->fill($data);
            $model->save();
            if (!isset($data['url'])) {
                $model->url = $this->getUrl($model);
                $model->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
        $this->seoUrlService->getUrlArr(SEOEnum::TOPIC);
    }

    public function getUrl($topic)
    {
        $pinyin = new Pinyin();
        $code = $pinyin->abbr($topic->title);
        $url = $code.crc32($code.'_'.time().'_'.$topic->id);
        if (Topic::query()->where('url', $url)->count() > 0) {
            $topic->title = $url;
            $url = $this->getUrl($topic);
        }

        return $url;
    }

    public function status($id): void
    {
        DB::beginTransaction();

        try {
            /** @var Topic $model */
            $model = Topic::query()->findOrFail($id);
            $model->status = !$model->status;
            $model->save();
            $this->seoUrlService->getUrlArr(SEOEnum::TOPIC);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw new ServiceException($e->getMessage());
        }
    }

    private function saveImage(array $images): array
    {
        $data = [];
        foreach ($images as $image) {
            $data[] = [
                'img' => $image,
            ];
        }

        return $data;
    }
}
