<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/13
 */

namespace app\services\order;


use app\dao\order\StoreOrderDao;
use app\services\BaseServices;

/**
 * Class StoreTaskServices
 * @package app\services\order
 */
class StoreTaskServices extends BaseServices
{
    /**
     * StoreTaskServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    public function orderUnpaidCancel()
    {

    }

    public function startTakeOrder()
    {

    }
}