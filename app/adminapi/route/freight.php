<?php

use think\facade\Route;

/**
 * 商户管理 相关路由
 */
Route::group('freight', function () {
    //物流公司资源路由
    Route::resource('express', 'v1.freight.Express')->name('ExpressResource');
    //修改状态
    Route::put('express/set_status/:id/:status', 'v1.freight.Express/set_status');

})->middleware([
    \app\http\middleware\AllowOriginMiddleware::class,
    \app\adminapi\middleware\AdminAuthTokenMiddleware::class,
    \app\adminapi\middleware\AdminCkeckRoleMiddleware::class,
    \app\adminapi\middleware\AdminLogMiddleware::class
]);