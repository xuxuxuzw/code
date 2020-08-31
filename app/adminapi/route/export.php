<?php

use think\facade\Route;

/**
 * 导出excel相关路由
 */
Route::group('export', function () {
    //用户资金监控
    Route::get('userFinance', 'v1.export.ExportExcel/userFinance');
    //用户佣金
    Route::get('userCommission', 'v1.export.ExportExcel/userCommission');
    //用户积分
    Route::get('userPoint', 'v1.export.ExportExcel/userPoint');
    //用户充值
    Route::get('userRecharge', 'v1.export.ExportExcel/userRecharge');
    //分销用户推广列表
    Route::get('userAgent', 'v1.export.ExportExcel/userAgent');
    //微信用户
    Route::get('wechatUser', 'v1.export.ExportExcel/wechatUser');
    //商铺砍价活动
    Route::get('storeBargain', 'v1.export.ExportExcel/storeBargain');
    //商铺拼团
    Route::get('storeCombination', 'v1.export.ExportExcel/storeCombination');
    //商铺秒杀
    Route::get('storeSeckill', 'v1.export.ExportExcel/storeSeckill');
    //商铺产品
    Route::get('storeProduct', 'v1.export.ExportExcel/storeProduct');
    //商铺订单
    Route::get('storeOrder', 'v1.export.ExportExcel/storeOrder');
    //商铺提货点
    Route::get('storeMerchant', 'v1.export.ExportExcel/storeMerchant');
})->middleware([
    \app\http\middleware\AllowOriginMiddleware::class,
    \app\adminapi\middleware\AdminAuthTokenMiddleware::class,
    \app\adminapi\middleware\AdminCkeckRoleMiddleware::class,
    \app\adminapi\middleware\AdminLogMiddleware::class
]);
