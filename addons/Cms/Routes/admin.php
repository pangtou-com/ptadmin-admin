<?php

declare(strict_types=1);

/**
 *  PTAdmin
 *  ============================================================================
 *  版权所有 2022-2026 重庆胖头网络技术有限公司，并保留所有权利。
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

use Addon\Cms\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => admin_route_prefix().'/cms', 'middleware' => ['auth:'.\PTAdmin\Admin\Utils\SystemAuth::getGuard()]], function (): void {
    // 导航管理
    Route::get('menus', [Admin\MenuController::class, 'index']);
    Route::get('menus-lists', [Admin\MenuController::class, 'lists']);
    Route::post('menus', [Admin\MenuController::class, 'store']);
    Route::put('menus/{id}', [Admin\MenuController::class, 'edit']);
    Route::delete('menus/{id?}', [Admin\MenuController::class, 'delete']);
    Route::put('menus-status/{id}', [Admin\MenuController::class, 'status']);
    // 导航选项
    Route::get('menus-items', [Admin\MenuItemController::class, 'index']);
    Route::get('menus-items/{id}', [Admin\MenuItemController::class, 'detail']);
    Route::post('menus-item', [Admin\MenuItemController::class, 'store']);
    Route::put('menus-item/{id}', [Admin\MenuItemController::class, 'edit']);
    Route::delete('menus-item/{id}', [Admin\MenuItemController::class, 'delete']);
    Route::put('menus-item-status/{id}', [Admin\MenuItemController::class, 'status']);

    // 广告管理
    Route::get('ads', [Admin\AdController::class, 'index'])->where('ad_position_id', '[1-9][0-9]*');
    Route::put('ads/{id}', [Admin\AdController::class, 'edit']);
    Route::post('ads', [Admin\AdController::class, 'store']);
    Route::delete('ad/{id?}', [Admin\AdController::class, 'delete']);
    Route::put('ad-status/{id?}', [Admin\AdController::class, 'status']);

    // 广告位置管理
    Route::get('ad-spaces', [Admin\AdSpaceController::class, 'index']);
    Route::post('ad-spaces', [Admin\AdSpaceController::class, 'store']);
    Route::put('ad-spaces/{id}', [Admin\AdSpaceController::class, 'edit']);
    Route::delete('ad-spaces/{id}', [Admin\AdSpaceController::class, 'delete']);
    Route::put('ad-spaces-status/{id?}', [Admin\AdSpaceController::class, 'status']);

    // 内容管理
    Route::get('/archives', [Admin\ArchiveController::class, 'index']);
    Route::get('/archive-lists', [Admin\ArchiveController::class, 'lists']);
    Route::get('/archive-pages', [Admin\ArchiveController::class, 'pages']);
    Route::post('/archive', [Admin\ArchiveController::class, 'store']);
    Route::put('/archive/{id}', [Admin\ArchiveController::class, 'edit'])->where('id', '[1-9][0-9]*');
    Route::put('/archive-one/{id}', [Admin\ArchiveController::class, 'onePageEdit']);
    Route::post('/archive-one', [Admin\ArchiveController::class, 'onePageStore']);
    Route::put('/archive-change/{id}', [Admin\ArchiveController::class, 'change']);
    Route::delete('/archive/{id}', [Admin\ArchiveController::class, 'delete'])->where('id', '[1-9][0-9]*');

    // 内容分类管理
    Route::get('/category', [Admin\CategoryController::class, 'index']);
    Route::get('/category/{id}', [Admin\CategoryController::class, 'detail']);
    Route::get('/category-tree', [Admin\CategoryController::class, 'tree']);
    Route::post('/category', [Admin\CategoryController::class, 'store']);
    Route::put('/category/{id}', [Admin\CategoryController::class, 'edit']);
    Route::delete('/category/{id}', [Admin\CategoryController::class, 'delete']);

    // 模型管理
    Route::get('/models', [Admin\ModelController::class, 'index']);
    Route::get('/model-preview/{id}', [Admin\ModelController::class, 'preview']);
    Route::match(['get', 'post'], '/model', [Admin\ModelController::class, 'store']);
    Route::match(['get', 'put'], '/model/{id}', [Admin\ModelController::class, 'edit'])->where('id', '[1-9][0-9]*');
    Route::put('/model-restore/{id}', [Admin\ModelController::class, 'restore']);
    Route::put('/model-status/{id}', [Admin\ModelController::class, 'status']);
    Route::put('/model-publish/{id}', [Admin\ModelController::class, 'publish']);
    Route::put('/model-cancel/{id}', [Admin\ModelController::class, 'cancel']);
    Route::delete('/model/{id}', [Admin\ModelController::class, 'del'])->where('id', '[1-9][0-9]*');
    Route::delete('/model-thorough/{id}', [Admin\ModelController::class, 'thoroughDel']);

    Route::get('/models/field/{id}', [Admin\ModelFieldController::class, 'index'])->where('id', '[1-9][0-9]*');
    Route::match(['get', 'post'], '/model/field', [Admin\ModelFieldController::class, 'store']);
    Route::match(['get', 'put'], '/model/field/{id}', [Admin\ModelFieldController::class, 'edit'])->where('id', '[1-9][0-9]*');
    Route::put('/model/field-status/{id}', [Admin\ModelFieldController::class, 'status']);
    Route::put('/model/field-restore/{id}', [Admin\ModelFieldController::class, 'restore']);
    Route::delete('/model/field/{id}', [Admin\ModelFieldController::class, 'del'])->where('id', '[1-9][0-9]*');
    Route::delete('/model/field-thorough/{id}', [Admin\ModelFieldController::class, 'thoroughDel']);

    // 标签管理
    Route::get('/tags', [Admin\TagController::class, 'index']);
    Route::match(['get', 'post'], '/tag', [Admin\TagController::class, 'store']);
    Route::match(['get', 'put'], '/tag/{id}', [Admin\TagController::class, 'edit'])->where('id', '[1-9][0-9]*');
    Route::put('/tag-status/{id}', [Admin\TagController::class, 'status']);
    Route::delete('/tag/{id}', [Admin\TagController::class, 'delete'])->where('id', '[1-9][0-9]*');
    Route::get('/tag-archive-list', [Admin\TagController::class, 'archiveList']);
    Route::put('/tag-association', [Admin\TagController::class, 'association']);
    Route::put('/tag-del-association', [Admin\TagController::class, 'delAssociation']);

    // SEO管理
    Route::get('/seo', [Admin\SeoController::class, 'index']);
    Route::put('/seo-update', [Admin\SeoController::class, 'update']);
    Route::put('/sitemap-save', [Admin\SeoController::class, 'sitemap_save']);

    // 专题管理
    Route::get('/topics', [Admin\TopicController::class, 'index']);
    Route::match(['get', 'post'], '/topic', [Admin\TopicController::class, 'store']);
    Route::match(['get', 'put'], '/topic/{id}', [Admin\TopicController::class, 'edit'])->where('id', '[1-9][0-9]*');
    Route::delete('/topic/{id}', [Admin\TopicController::class, 'delete'])->where('id', '[1-9][0-9]*');
    Route::get('/topic/detail/{id}', [Admin\TopicController::class, 'detail']);
    Route::put('/topic/status/{type}/{id}', [Admin\TopicController::class, 'topicStatus']); //修改状态
    //专题-导航管理
    Route::match(['get'], '/topic/navigations/{id}', [Admin\TopicController::class, 'navigations']);
    Route::match(['get', 'post'], '/topic/navForm/{topicId}', [Admin\TopicController::class, 'navigationStore']);
    Route::match(['get', 'put'], '/topic/navForm/{topicId}/{id}', [Admin\TopicController::class, 'navigationEdit'])->where('id', '[1-9][0-9]*');
    Route::delete('/topic/navForm/{id}', [Admin\TopicController::class, 'navigationDelete'])->where('id', '[1-9][0-9]*');
    //专题-专题分类
    Route::match(['get'], '/topic/associations/{id}', [Admin\TopicController::class, 'associations']);
    Route::match(['get', 'post'], '/topic/association/{topicId}', [Admin\TopicController::class, 'associationStore']);
    Route::match(['get', 'put'], '/topic/association/{id}/{topicId}', [Admin\TopicController::class, 'associationEdit'])->where('id', '[1-9][0-9]*');
    Route::delete('/topic/association/{id}', [Admin\TopicController::class, 'associationDelete'])->where('id', '[1-9][0-9]*');
    Route::match(['get', 'post'], '/topic/association/about/{id}', [Admin\TopicController::class, 'associationAbout']);
    //标签生成器
    Route::get('/tag-generations', [Admin\TagGenerationsController::class, 'index']);
    //评分检测列表
    Route::get('/score-detection', [Admin\ScoreDetectionController::class, 'index']);
    //评分检测
    Route::post('/score-detection', [Admin\ScoreDetectionController::class, 'pathScore']);
    Route::put('/score-detection', [Admin\ScoreDetectionController::class, 'saveScore']);
    // 成功页面
    Route::get('/score-detection/details', [Admin\ScoreDetectionController::class, 'details']);
});
