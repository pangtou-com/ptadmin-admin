<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Dashboard;

final class TrendResult extends DashboardWidgetResult
{
    public function __construct(array $categories = array(), array $series = array(), string $chart = 'line')
    {
        parent::__construct('trend', array('categories' => array_values($categories), 'series' => array_values($series), 'chart' => $chart));
    }
    /** @param array<int, string> $categories */
    public function categories(array $categories): self { $this->payload['categories'] = array_values($categories); return $this; }
    /** @param array<int, int|float> $series */
    public function series(array $series): self { $this->payload['series'] = array_values($series); return $this; }
    public function chart(string $chart): self { $this->payload['chart'] = $chart; return $this; }
}
