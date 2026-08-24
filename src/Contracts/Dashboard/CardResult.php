<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Dashboard;

final class CardResult extends DashboardWidgetResult
{
    public function __construct() { parent::__construct('card', array('items' => array())); }
    public function item(string $id, string $label, string $description = '', string $target = ''): self
    {
        $item = array('id' => $id, 'label' => $label);
        if ('' !== $description) $item['description'] = $description;
        if ('' !== $target) $item['target'] = $target;
        $this->payload['items'][] = $item;
        return $this;
    }
}
