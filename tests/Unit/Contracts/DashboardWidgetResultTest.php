<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Tests\Unit\Contracts;

use PHPUnit\Framework\TestCase;
use PTAdmin\Contracts\Dashboard\CardResult;
use PTAdmin\Contracts\Dashboard\ListResult;
use PTAdmin\Contracts\Dashboard\RankingResult;
use PTAdmin\Contracts\Dashboard\StatResult;
use PTAdmin\Contracts\Dashboard\TrendResult;

final class DashboardWidgetResultTest extends TestCase
{
    public function test_result_objects_serialize_to_console_widget_protocol(): void
    {
        self::assertSame(array(
            'type' => 'stats',
            'items' => array(array('code' => 'records', 'label' => '记录', 'value' => 3, 'unit' => '条')),
        ), (new StatResult())->metric('records', '记录', 3, '条')->toArray());

        self::assertSame(array(
            'type' => 'trend',
            'categories' => array('周一'),
            'series' => array(3),
            'chart' => 'line',
        ), (new TrendResult(array('周一'), array(3)))->toArray());

        self::assertSame(array(
            'type' => 'ranking',
            'items' => array(array('id' => 'cms', 'label' => 'CMS', 'value' => 3, 'change' => '+1')),
        ), (new RankingResult())->item('cms', 'CMS', 3, '+1')->toArray());

        self::assertSame(array(
            'type' => 'list',
            'items' => array(array('id' => 'todo', 'title' => '待办', 'meta' => '今天', 'status' => 'warning')),
        ), (new ListResult())->item('todo', '待办', '今天', 'warning')->toArray());

        self::assertSame(array(
            'type' => 'card',
            'items' => array(array('id' => 'cms', 'label' => 'CMS', 'description' => '内容管理', 'target' => '/cms')),
        ), (new CardResult())->item('cms', 'CMS', '内容管理', '/cms')->toArray());
    }
}
