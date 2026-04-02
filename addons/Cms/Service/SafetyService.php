<?php

namespace Addon\Cms\Service;

use Addon\Cms\Models\Safety;

class SafetyService
{
    /**
     * 将安全服务内容放在导航中
     * @return array
     */
    public static function nav(): array
    {
        $data = Safety::query()
            ->select(['type', 'title', 'id'])
            ->where('status', 1)
            ->orderByDesc("weight")
            ->get()->toArray();
        $results  = [];
        foreach ($data as $datum) {
            if (!isset($results[$datum['type']])) {
                $results[$datum['type']] = ["title" => $datum['type_text'], "url" => "", "children" => []];
            }
            $datum['url'] = "/safety/{$datum['id']}.html";
            $results[$datum['type']]['children'][] = $datum;
        }

        $data = [];
        foreach (config("cms.safety_type") as $item) {
            $data[] = $results[$item['value']] ?? [];
        }

        return $data;
    }
}
