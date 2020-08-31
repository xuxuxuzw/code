<?php

namespace app\adminapi\controller\v1\marketing;


use app\adminapi\controller\AuthController;
use app\services\user\UserBillServices;
use think\facade\App;


/**
 * 积分控制器
 * Class StoreCategory
 * @package app\admin\controller\system
 */
class UserPoint extends AuthController
{

    /**
     * Finance constructor.
     * @param App $app
     * @param UserBillServices $services
     */
    public function __construct(App $app, UserBillServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['start_time', ''],
            ['end_time', ''],
            ['nickname', ''],
            ['page', 1],
            ['limit', 10],
        ]);
        return $this->success($this->services->getPointList($where));
    }

    /**
     * 获取积分日志头部信息
     * @return mixed
     */
    public function integral_statistics()
    {
        $where = $this->request->getMore([
            ['start_time', ''],
            ['end_time', ''],
            ['nickname', ''],
        ]);
        return $this->success(['res' => $this->services->getUserPointBadgelist($where)]);
    }

}
