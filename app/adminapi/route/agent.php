<?php

use think\facade\Route;

/**
 * 分销管理 相关路由
 */
Route::group('agent', function () {
    //推销员列表
    Route::get('index', 'v1.agent.AgentManage/index');
    //头部统计
    Route::get('statistics', 'v1.agent.AgentManage/get_badge');
    //推广人列表
    Route::get('stair', 'v1.agent.AgentManage/get_stair_list');
    //推广人头部统计
    Route::get('stair/statistics', 'v1.agent.AgentManage/get_stair_badge');
    //统计推广订单列表
    Route::get('stair/order', 'v1.agent.AgentManage/get_stair_order_list');
    //统计推广订单列表头部
    Route::get('stair/order/statistics', 'v1.agent.AgentManage/get_stair_order_badge');
    //清除上级推广人
    Route::put('stair/delete_spread/:uid', 'v1.agent.AgentManage/delete_spread');
    //查看公众号推广二维码
    Route::get('look_code', 'v1.agent.AgentManage/look_code');
    //查看小程序推广二维码
    Route::get('look_xcx_code', 'v1.agent.AgentManage/look_xcx_code');
    //查看H5推广二维码
    Route::get('look_h5_code', 'v1.agent.AgentManage/look_h5_code');

})->middleware([
    \app\http\middleware\AllowOriginMiddleware::class,
    \app\adminapi\middleware\AdminAuthTokenMiddleware::class,
    \app\adminapi\middleware\AdminCkeckRoleMiddleware::class,
    \app\adminapi\middleware\AdminLogMiddleware::class
]);