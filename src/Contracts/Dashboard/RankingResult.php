<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Dashboard;

final class RankingResult extends DashboardWidgetResult
{
    public function __construct() { parent::__construct('ranking', array('items' => array())); }
    public function item(string $id, string $label, $value, string $change = ''): self
    {
        $item = array('id' => $id, 'label' => $label, 'value' => $value);
        if ('' !== $change) $item['change'] = $change;
        $this->payload['items'][] = $item;
        return $this;
    }
}
