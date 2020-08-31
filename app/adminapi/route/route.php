<?php

use think\facade\Route;
use think\facade\Config;
use think\Response;
use app\http\middleware\AllowOriginMiddleware;

/**
 * 无需授权的接口
 */
Route::group(function () {
    //用户名密码登录
    Route::post('login', 'Login/login')->name('AdminLogin');
    //后台登录页面数据
    Route::get('login/info', 'Login/info');
    //验证码
    Route::get('captcha_pro', 'Login/captcha');

})->middleware(AllowOriginMiddleware::class);

/**
 * miss 路由
 */
Route::miss(function () {
    if (app()->request->isOptions()) {
        $header = Config::get('cookie.header');
        $header['Access-Control-Allow-Origin'] = app()->request->header('origin');
        return Response::create('ok')->code(200)->header($header);
    } else
        return Response::create()->code(404);
});