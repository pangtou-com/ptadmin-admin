<?php

declare(strict_types=1);

namespace PTAdmin\Contracts\Dashboard;

interface DashboardWidget
{
    public function definition(): DashboardWidgetDefinition;

    public function query(DashboardWidgetQuery $query, DashboardWidgetContext $context): DashboardWidgetResult;
}
