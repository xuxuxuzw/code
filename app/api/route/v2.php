<?php

use think\facade\Route;

/**
 * v1.1 版本路由
 */
Route::group('v2', function () {
    //无需授权接口
    Route::group(function () {
        //小程序静默授权
        Route::get('wechat/silence_auth', 'v2.wechat.AuthController/silenceAuth');
        //公众号静默授权
        Route::get('wechat/wx_silence_auth', 'v2.wechat.WechatController/silenceAuth');
        //DIY接口
        Route::get('diy/get_diy', 'v2.PublicController/getDiy');
        //是否强制绑定手机号
        Route::get('bind_status','v2.PublicController/bindPhoneStatus');
        //小程序授权绑定手机号
        Route::post('auth_bindind_phone','v2.wechat.AuthController/authBindindPhone');
    });
    //需要授权
    Route::group(function () {

        Route::post('reset_cart','v2.store.StoreCartController/resetCart')->name('resetCart');
        Route::get('new_coupon', 'v2.store.StoreCouponsController/getNewCoupon')->name('getNewCoupon');//获取新人券
        Route::post('user/user_update','v2.wechat.AuthController/updateInfo');
        Route::post('order/product_coupon/:orderId','v2.store.StoreCouponsController/getOrderProductCoupon');

    })->middleware(\app\api\middleware\AuthTokenMiddleware::class, true);
    //授权不通过,不会抛出异常继续执行
    Route::group(function () {
        //公共类
        Route::get('index', 'v2.PublicController/index')->name('index');//首页
    })->middleware(\app\api\middleware\AuthTokenMiddleware::class, false);

})->middleware(\app\http\middleware\AllowOriginMiddleware::class);
