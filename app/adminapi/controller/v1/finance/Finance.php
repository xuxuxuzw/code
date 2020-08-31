<?php
/**
 * Created by PhpStorm.
 * User: lofate
 * Date: 2019/11/28
 * TIME: 10:16
 */

namespace app\adminapi\controller\v1\finance;

use app\services\user\UserBillServices;
use think\facade\App;
use app\adminapi\controller\AuthController;

class Finance extends AuthController
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
     * 筛选类型
     */
    public function bill_type()
    {
        return $this->success($this->services->bill_type());
    }

    /**
     * 资金记录
     */
    public function list()
    {
        $where = $this->request->getMore([
            ['start_time', ''],
            ['end_time', ''],
            ['nickname', ''],
            ['limit', 20],
            ['page', 1],
            ['type', ''],
        ]);
        return $this->success($this->services->getBillList($where));
    }

    /**
     * 佣金记录
     * @return mixed
     */
    public function get_commission_list()
    {
        $where = $this->request->getMore([
            ['nickname', ''],
            ['price_max', ''],
            ['price_min', ''],
        ]);
        return $this->success($this->services->getCommissionList($where));
    }

    /**
     * 佣金详情用户信息
     * @param $id
     * @return mixed
     */
    public function user_info($id)
    {
        return $this->success($this->services->user_info((int)$id));
    }

    /**
     * 佣金提现记录个人列表
     */
    public function get_extract_list($id = '')
    {
        if ($id == '') return $this->fail('缺少参数');
        $where = $this->request->getMore([
            ['start_time', ''],
            ['end_time', ''],
            ['nickname', '']
        ]);
        return $this->success($this->services->getBillOneList((int)$id, $where));
    }

}
