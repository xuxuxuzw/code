<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/10
 */

namespace app\services\order;


use app\dao\order\StoreOrderDao;
use app\services\activity\StorePinkServices;
use app\services\activity\StoreSeckillServices;
use app\services\BaseServices;
use app\services\user\UserBillServices;
use app\services\user\UserServices;
use crmeb\utils\Queue;
use think\exception\ValidateException;

/**
 * Class StoreOrderSuccessServices
 * @package app\services\order
 * @method getOne(array $where, ?string $field = '*', array $with = []) 获取去一条数据
 */
class StoreOrderSuccessServices extends BaseServices
{
    /**
     *
     * StoreOrderSuccessServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 0元支付
     * @param array $orderInfo
     * @param int $uid
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function zeroYuanPayment($orderInfo, $uid)
    {
        if ($orderInfo['paid']) {
            throw new ValidateException('该订单已支付!');
        }
        /** @var UserServices $services */
        $services = app()->make(UserServices::class);
        $userInfo = $services->getUserInfo($uid);
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        $res = $userBillServices->income('pay_product', $userInfo['uid'], $orderInfo['pay_price'], $userInfo['now_money'], $orderInfo['id']);
        $res = $res && $this->paySuccess($orderInfo, 'yue');//余额支付成功
        return $res;
    }

    /**
     * 支付成功
     * @param array $orderInfo
     * @param string $paytype
     * @return bool
     */
    public function paySuccess(array $orderInfo, string $paytype = 'weixin')
    {
        $res1 = $this->dao->update($orderInfo['id'], ['paid' => 1, 'pay_type' => $paytype, 'pay_time' => time()]);
        $resPink = true;
        if ($orderInfo['combination_id'] && $res1 && !$orderInfo['refund_status']) {
            /** @var StorePinkServices $pinkServices */
            $pinkServices = app()->make(StorePinkServices::class);
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            $resPink = $pinkServices->createPink($orderServices->tidyOrder($orderInfo, true));//创建拼团
        }
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $statusService->save([
            'oid' => $orderInfo['id'],
            'change_type' => 'pay_success',
            'change_message' => '用户付款成功',
            'change_time' => time()
        ]);
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $now_money = $userServices->value(['uid' => $orderInfo['uid']], 'now_money');
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        $userBillServices->income('pay_money', $orderInfo['uid'], $orderInfo['pay_price'], $now_money, $orderInfo['id']);
        //回退库存占用
        /** @var StoreOrderCartInfoServices $cartServices */
        $cartServices = app()->make(StoreOrderCartInfoServices::class);
        $cartInfo = $cartServices->getOrderCartInfo($orderInfo['id']);
        /** @var StoreSeckillServices $seckiiServices */
        $seckiiServices = app()->make(StoreSeckillServices::class);
        $seckiiServices->cancelOccupySeckillStock($cartInfo, $orderInfo['unique']);
        //支付成功后发送消息
        Queue::instance()->job(\crmeb\jobs\OrderJob::class)->data($orderInfo)->push();
        $res = $res1 && $resPink;
        return false !== $res;
    }

}
