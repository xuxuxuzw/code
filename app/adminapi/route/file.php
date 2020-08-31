<?php

use think\facade\Route;

/**
 * 附件相关路由
 */
Route::group('file', function () {
    //附件列表
    Route::get('file', 'v1.file.SystemAttachment/index');
    //删除图片和数据记录
    Route::post('file/delete', 'v1.file.SystemAttachment/delete');
    //移动图片分来表单
    Route::get('file/move', 'v1.file.SystemAttachment/move');
    //移动图片分类
    Route::put('file/do_move', 'v1.file.SystemAttachment/moveImageCate');
    //上传图片
    Route::post('upload/[:upload_type]', 'v1.file.SystemAttachment/upload');
    //附件分类管理资源路由
    Route::resource('category', 'v1.file.SystemAttachmentCategory');

})->middleware([
    \app\http\middleware\AllowOriginMiddleware::class,
    \app\adminapi\middleware\AdminAuthTokenMiddleware::class,
    \app\adminapi\middleware\AdminCkeckRoleMiddleware::class,
    \app\adminapi\middleware\AdminLogMiddleware::class
]);