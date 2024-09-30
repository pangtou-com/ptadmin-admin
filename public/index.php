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

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('PTADMIN_START', microtime(true));
const PTADMIN_FRAME_VERSION = 'v0.0.7';

if (version_compare(PHP_VERSION, '7.3', '<')) {
    $version = PHP_VERSION;
    echo '<div style="font-size: 16px; color: #333; font-family: Arial, sans-serif; text-align: center; margin-top: 100px;">';
    echo "<p>当前PHP版本【{$version}】,无法满足系统要求, 请至少升级至 7.3 版本</p>";
    echo "<p>The current PHP version【{$version}】does not meet the system requirements. Please upgrade to at least version 7.3.</p>";
    echo '</div>';

    exit;
}

if (!file_exists(__DIR__.'/../storage/installed')
    && !(isset($_SERVER['REQUEST_URI']) && '/install' === substr($_SERVER['REQUEST_URI'], 0, 8))) {
    header('Location: /install');

    exit;
}

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request = Request::capture())->send();
$kernel->terminate($request, $response);
