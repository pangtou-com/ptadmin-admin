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

use App\Http\Controllers\Home;
use Illuminate\Support\Facades\Route;

Route::get('/', [Home\HomeController::class, 'index']);

// 用户相关
Route::group(['prefix' => 'user'], function (): void {
    Route::get('/logout', [Home\UserController::class, 'logout']);
    Route::post('/login', [Home\UserController::class, 'login']);
    Route::post('/register', [Home\UserController::class, 'register']);
    Route::post('/reset', [Home\UserController::class, 'reset']);
    // 第三方授权登录
    Route::get('/oauth/{tag}', [Home\UserController::class, 'otherOauth']);
    // 第三方授权登录回调
    Route::get('/oauth/callback/{tag}', [Home\UserController::class, 'oauthCallback']);
    // 注册
    Route::match(['get', 'post'], '/register', [Home\UserController::class, 'register']);
    //手机发送验证码
    Route::post('/send-code', [Home\UserController::class, 'sendCode']);
    //邮箱发送验证码
    Route::post('/send-email-code', [Home\UserController::class, 'sendEmailCode']);
    // 重置密码
    Route::match(['get', 'post'], '/reset', [Home\UserController::class, 'reset']);
});

// 需要登录的路由
Route::group(['prefix' => 'user', 'middleware' => 'auth:web'], function (): void {
    //个人中心
    Route::get('/', [Home\UserController::class, 'index']);
    //个人设置
    Route::match(['get', 'post'], '/setting', [Home\UserController::class, 'setting']);
});
