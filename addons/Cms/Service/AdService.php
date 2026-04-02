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

use Addon\Cms\Models\Ad;
use Addon\Cms\Models\AdSpace;
use App\Exceptions\ServiceException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdService
{
    public function find($id): array
    {
        $dao = Ad::query()->findOrFail($id);
        $dao->click = $dao->click + 1;
        $dao->save();

        return $dao->toArray();
    }

    /**
     * 根据广告位获取广告信息.
     *
     * @param $position
     * @param int $limit
     *
     * @return array
     */
    public function byPosition($position, int $limit = 5): array
    {
        $all = Ad::getAllCacheData();
        if (0 === \count($all)) {
            return [];
        }
        $all = $all[$position] ?? [];
        if (0 === \count($all)) {
            return [];
        }
        if (0 === $limit) {
            return $all;
        }
        if (1 === $limit) {
            return reset($all);
        }

        return \array_slice($all, 0, $limit);
    }

    public function lists($search = []): array
    {
        $allow = [
            'title' => ['op' => 'like'],
            ['field' => 'title', 'query_field' => 'keywords', 'op' => 'like'],
            'ad_position_id' => '=',
        ];
        $filterMap = Ad::search($allow, $search);

        $results = $filterMap->orderBy('id', 'desc')->paginate()->toArray();
        foreach ($results['data'] as &$result) {
//            $result['ad_position_text'] = AdPosition::getCacheKeyMap($result['ad_position_id'], 'title');
            $result['url'] = url("/ad-detail/{$result['id']}");
        }
        unset($result);

        return $results;
    }

    public function store($data): void
    {
        DB::beginTransaction();

        try {
            $dao = new Ad();
            $dao->fill($data);
            $dao->save();
            // Ad::cacheAllData();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();

            throw new ServiceException($exception->getMessage());
        }
    }

    public function edit($data, $id): void
    {
        if (!AdSpace::query()->where('id', $data['ad_position_id'])->exists()) {
            throw new ServiceException('广告位不存在！');
        }
        $ad = Ad::query()->findOrFail($id);
        $time = $this->AdTimeDeal($data);
        $data['start_at'] = $time['start_at'];
        $data['end_at'] = $time['end_at'];
        $data = $this->setLinkManInfo($data);

        $ad->update($data);
        Ad::cacheAllData();
    }

    /** 广告开始、结束时间处理.
     * @param $data
     *
     * @return int[]
     */
    public function AdTimeDeal($data): array
    {
        $start_at = isset($data['start_at']) && $data['start_at'] ? (int) strtotime($data['start_at']) : 0;
        $end_at = isset($data['end_at']) && $data['end_at'] ? (int) strtotime($data['end_at']) : 0;
        if ($end_at - $start_at < 0) {
            throw new ServiceException('结束时间不能小于开始时间！');
        }

        return [
            'start_at' => $start_at,
            'end_at' => $end_at,
        ];
    }

    public function setLinkManInfo($data)
    {
        $user = Auth::guard('admin')->user();
        $data['link_man'] = $user['nickname'];
        $data['link_email'] = $user['email'];
        $data['link_phone'] = $user['mobile'];

        return $data;
    }

    public function status($id, $val): void
    {
        $ad = Ad::query()->findOrFail($id);
        $ad->status = $val;

        $ad->update(['status' => $val]);
        Ad::cacheAllData();
    }
}
