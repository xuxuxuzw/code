<?php

namespace app\adminapi\controller\v1\system;

use think\facade\App;
use app\services\system\log\ClearServices;
use app\adminapi\controller\AuthController;

/**
 * 首页控制器
 * Class Clear
 * @package app\admin\controller
 *
 */
class Clear extends AuthController
{
    public function __construct(App $app, ClearServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 刷新数据缓存
     */
    public function refresh_cache()
    {
        $this->services->refresCache();
        return $this->success('数据缓存刷新成功!');
    }


    /**
     * 删除日志
     */
    public function delete_log()
    {
        $this->services->deleteLog();
        return $this->success('数据缓存刷新成功!');
    }
}


