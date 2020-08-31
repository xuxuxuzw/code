<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-15
 */

use think\facade\Route;

/**
 * 分销管理 相关路由
 */
Route::group('diy', function () {

    //DIY列表
    Route::get('get_list', 'v1.diy.Diy/getList');
    //DIY列表
    Route::get('get_info/:id', 'v1.diy.Diy/getInfo');
    //删除DIY模板
    Route::delete('del/:id', 'v1.diy.Diy/del');
    //使用DIY模板
    Route::put('set_status/:id', 'v1.diy.Diy/setStatus');
    //保存DIY模板
    Route::post('save/[:id]', 'v1.diy.Diy/saveData');
    //获取路径
    Route::get('get_url','v1.diy.Diy/getUrl');
    //获取商品分类
    Route::get('get_category','v1.diy.Diy/getCategory');
    //获取商品
    Route::get('get_product','v1.diy.Diy/getProduct');

})->middleware([
    \app\http\middleware\AllowOriginMiddleware::class,
    \app\adminapi\middleware\AdminAuthTokenMiddleware::class,
    \app\adminapi\middleware\AdminCkeckRoleMiddleware::class,
    \app\adminapi\middleware\AdminLogMiddleware::class
]);
