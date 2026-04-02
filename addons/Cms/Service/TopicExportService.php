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

use Addon\Cms\Models\Topic;
use PTAdmin\Addon\Service\DirectivesDTO;
use PTAdmin\Admin\Enum\StatusEnum;

class TopicExportService extends AbstractExportService
{
    private $checkFieldIsNullArr = ['banners'];

    public function handle(DirectivesDTO $DTO): array
    {
        $data = $this->dataProcessing($DTO->all());
        $filterMap = Topic::query();
        if (isset($data['fields']) && \count($data['fields']) > 0) {
            $filterMap->select($data['fields']);
        }
        $filterMap = $this->fieldHasOrNot($filterMap, $data, $this->checkFieldIsNullArr);
        $filterMap = $this->limitAndOrder($filterMap, $DTO);

        return $filterMap->where('status', StatusEnum::ENABLE)->get()->toArray();
    }
}
