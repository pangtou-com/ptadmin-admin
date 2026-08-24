<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Dashboard;

final class StatResult extends DashboardWidgetResult
{
    public function __construct() { parent::__construct('stats', array('items' => array())); }
    public function metric(string $code, string $label, $value, string $unit = '', string $tone = 'default', array $action = array()): self
    {
        $item = array('code' => $code, 'label' => $label, 'value' => $value);
        if ('' !== $unit) $item['unit'] = $unit;
        if ('default' !== $tone) $item['tone'] = $tone;
        if ([] !== $action) $item['action'] = $action;
        $this->payload['items'][] = $item;
        return $this;
    }
}
