<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Dashboard;

final class ListResult extends DashboardWidgetResult
{
    public function __construct() { parent::__construct('list', array('items' => array())); }
    public function item(string $id, string $title, string $meta = '', string $status = 'default'): self
    {
        $item = array('id' => $id, 'title' => $title);
        if ('' !== $meta) $item['meta'] = $meta;
        if ('default' !== $status) $item['status'] = $status;
        $this->payload['items'][] = $item;
        return $this;
    }
}
