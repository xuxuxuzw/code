<?php

namespace app\api\controller\v1\user;

use app\Request;
use app\services\user\UserLevelServices;

/**
 * 会员等级类
 * Class UserLevelController
 * @package app\api\controller\user
 */
class UserLevelController
{
    protected $services = NUll;

    /**
     * UserLevelController constructor.
     * @param UserLevelServices $services
     */
    public function __construct(UserLevelServices $services)
    {
        $this->services = $services;
    }

    /**
     * 检测用户是否可以成为会员
     * @param Request $request
     * @return mixed
     */
    public function detection(Request $request)
    {
        return app('json')->successful($this->services->detection((int)$request->uid()));
    }

    /**
     * 会员等级列表
     * @param Request $request
     * @return mixed
     */
    public function grade(Request $request)
    {
        return app('json')->successful(['list'=>$this->services->grade((int)$request->uid()),'task'=>['list'=>[],'task'=>[]]]);
    }

    /**
     * 获取等级任务
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function task(Request $request, $id)
    {
        return app('json')->successful((new SystemUserTask())->getTashList($id, $request->uid()));
    }

    /**
     * 会员详情
     * @param Request $request
     * @return mixed
     */
    public function userLevelInfo(Request $request)
    {
        return app('json')->successful($this->services->getUserLevelInfo((int)$request->uid()));
    }

    /**
     * 经验列表
     * @param Request $request
     * @return mixed
     */
    public function expList(Request $request)
    {
        return app('json')->successful($this->services->expList((int)$request->uid()));
    }

}