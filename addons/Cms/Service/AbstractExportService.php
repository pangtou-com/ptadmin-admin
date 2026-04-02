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

use PTAdmin\Addon\Service\DirectivesDTO;

abstract class AbstractExportService
{
    /**
     * 数据处理(将字符串数据转换成数组).
     *
     * @param mixed $data
     * @param array $notNeedProcessing
     *
     * @return array
     */
    protected function dataProcessing($data, array $notNeedProcessing = []): array
    {
        $return = [];
        if (blank($data)) {
            return $return;
        }
        foreach ($data as $key => $value) {
            if (blank($value) || (\count($notNeedProcessing) > 0 && \in_array($key, $notNeedProcessing, true)) || \in_array($key, ['limit', 'order', 'page'], true)) {
                $return[$key] = $value;

                continue;
            }
            if (\is_string($value)) {
                $value = trim($value);
                $value = explode('|', $value);
            }
            if (is_numeric($value)) {
                $value = [(int) $value];
            }
            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * 获取data数据中，苦衷字段存在或不存在数据（true => 存在; false => 不存在）.
     * 不存在中包括空字符串.
     *
     * @param $filterMap
     * @param $data
     * @param $checkFieldIsNullArr
     *
     * @return mixed
     */
    protected function fieldHasOrNot($filterMap, $data, $checkFieldIsNullArr)
    {
        foreach ($data as $key => $value) {
            if (!\in_array($key, $checkFieldIsNullArr, true)) {
                continue;
            }
            if (null !== $value) {
                true === (bool) $value ? $filterMap->whereNotNull($key)->where($key, '<>', '') : $filterMap->whereNull($key);
            }
        }

        return $filterMap;
    }

    /**
     * 按位与查询是否满足某种属性.
     *
     * @param $filterMap
     * @param array $data
     * @param array $fieldArr
     *
     * @return mixed
     */
    protected function bitwiseAnd($filterMap, array $data, array $fieldArr)
    {
        if (0 === \count($fieldArr)) {
            return $filterMap;
        }
        foreach ($fieldArr as $key => $value) {
            if (isset($data[$key]) && \count($data[$key]) > 0) {
                $filterMap->where(function ($q) use ($data, $key, $value): void {
                    foreach ($data[$key] as $flag) {
                        $q->orWhereRaw($value.' & ? > 0', [$flag]);
                    }
                });
            }
        }

        return $filterMap;
    }

    /**
     * 按位或查询排除某种属性.
     *
     * @param $filterMap
     * @param array $data
     * @param array $fieldArr
     *
     * @return mixed
     */
    protected function bitwiseOr($filterMap, array $data, array $fieldArr)
    {
        foreach ($fieldArr as $key => $value) {
            if (isset($data[$key]) && \count($data[$key]) > 0) {
                $filterMap->where(function ($q) use ($data, $key, $value): void {
                    foreach ($data[$key] as $flag) {
                        $q->whereRaw($value.' & ? = 0', [$flag]);
                    }
                });
            }
        }

        return $filterMap;
    }

    /**
     * 获取关联信息.
     *
     * @param $with
     *
     * @return array
     */
    protected function getWithInfo($with): array
    {
        if (0 === \count($with)) {
            return [];
        }
        $return = [];
        foreach ($with as $value) {
            $tableInfo = explode(':', $value);
//            $return[$tableInfo[0]] = $tableInfo[1] ? explode(',', $tableInfo[1]) : null;
            if (!isset($tableInfo[1])) {
                $return[] = $tableInfo[0];

                continue;
            }
            $return[$tableInfo[0]] = function ($query) use ($tableInfo): void {
                $query->select(explode(',', $tableInfo[1]));
            };
        }

        return $return;
    }

    /**
     * 限制条数以及排序.
     *
     * @param $filterMap
     * @param DirectivesDTO $DTO
     *
     * @return mixed
     */
    protected function limitAndOrder($filterMap, DirectivesDTO $DTO)
    {
        // 限制条数以及是否偏移
        $limitArr = is_numeric($DTO->getLimit()) ? [$DTO->getLimit()] : explode(',', $DTO->getLimit());
        \count($limitArr) > 1 ? $filterMap->limit($limitArr[0])->offset($limitArr[1]) : $filterMap->limit($limitArr[0]);
        // 排序
        foreach ($DTO->getOrder() as $key => $value) {
            $filterMap->orderBy($key, $value);
        }

        return $filterMap;
    }
}
