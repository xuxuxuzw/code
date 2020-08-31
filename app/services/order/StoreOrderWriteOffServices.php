<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/8
 */

namespace app\services\order;


use app\dao\order\StoreOrderDao;
use app\services\activity\StorePinkServices;
use app\services\BaseServices;
use app\services\user\UserServices;
use think\exception\ValidateException;

/**
 * 核销订单
 * Class StoreOrderWriteOffServices
 * @package app\sservices\order
 */
class StoreOrderWriteOffServices extends BaseServices
{

    /**
     * 构造方法
     * StoreOrderWriteOffServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 订单核销
     * @param string $code
     * @param int $confirm
     * @param int $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function writeOffOrder(string $code, int $confirm, int $uid = 0)
    {
        $orderInfo = $this->dao->getOne(['verify_code' => $code, 'paid' => 1, 'refund_status' => 0, 'is_del' => 0]);
        if (!$orderInfo) {
            throw new ValidateException('Write off order does not exist');
        }
        /** @var StoreOrderCartInfoServices $orderCartInfo */
        $orderCartInfo = app()->make(StoreOrderCartInfoServices::class);
        $cartInfo = $orderCartInfo->getOne([
            ['cart_id', '=', $orderInfo['cart_id'][0]]
        ], 'cart_info');
        if ($cartInfo) $orderInfo['image'] = $cartInfo['cart_info']['productInfo']['image'];
        if ($orderInfo->status > 0) {
            throw new ValidateException('Order written off');
        }
        if ($orderInfo->combination_id && $orderInfo->pink_id) {
            /** @var StorePinkServices $services */
            $services = app()->make(StorePinkServices::class);
            $res = $services->getCount([['id', $orderInfo->pink_id], ['status', '<>', 2]]);
            if ($res) throw new ValidateException('Failed to write off the group order');
        }
        if ($confirm == 0) {
            /** @var UserServices $services */
            $services = app()->make(UserServices::class);
            $orderInfo['nickname'] = $services->value(['uid' => $orderInfo['uid']], 'nickname');
            return $orderInfo->toArray();
        }
        $orderInfo->status = 2;
        if ($uid) {
            $orderInfo->clerk_id = $uid;
        }
        if ($orderInfo->save()) {
            /** @var StoreOrderTakeServices $storeOrdeTask */
            $storeOrdeTask = app()->make(StoreOrderTakeServices::class);
            $re = $storeOrdeTask->storeProductOrderUserTakeDelivery($orderInfo);
            if(!$re){
                throw new ValidateException('Write off failure');
            }
            return $orderInfo->toArray();
        } else {
            throw new ValidateException('Write off failure');
        }
    }
}
