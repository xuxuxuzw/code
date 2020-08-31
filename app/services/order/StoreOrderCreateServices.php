<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/9
 */

namespace app\services\order;


use app\dao\order\StoreOrderDao;
use app\services\activity\StoreBargainServices;
use app\services\activity\StoreCombinationServices;
use app\services\activity\StoreSeckillServices;
use app\services\BaseServices;
use app\services\product\product\StoreProductServices;
use app\services\system\store\SystemStoreServices;
use app\services\user\UserAddressServices;
use app\services\user\UserBillServices;
use app\services\user\UserServices;
use crmeb\jobs\UnpaidOrderCancelJob;
use crmeb\jobs\UnpaidOrderSend;
use crmeb\services\CacheService;
use crmeb\services\SystemConfigService;
use crmeb\utils\Arr;
use crmeb\utils\Queue;
use think\exception\ValidateException;

/**
 * 订单创建
 * Class StoreOrderCreateServices
 * @package app\services\order
 */
class StoreOrderCreateServices extends BaseServices
{
    /**
     * StoreOrderCreateServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 使用雪花算法生成订单ID
     * @return string
     * @throws \Exception
     */
    public function getNewOrderId()
    {
        $snowflake = new \Godruoyi\Snowflake\Snowflake();
        //32位
        if (PHP_INT_SIZE == 4) {
            $id = abs($snowflake->id());
        } else {
            $id = $snowflake->setStartTimeStamp(strtotime('2020-06-05') * 1000)->id();
        }
        return 'wx' . $id;
    }

    /**
     * 核销订单生成核销码
     * @return false|string
     */
    public function getStoreCode()
    {
        list($msec, $sec) = explode(' ', microtime());
        $num = time() + mt_rand(10, 999999) . '' . substr($msec, 2, 3);//生成随机数
        if (strlen($num) < 12)
            $num = str_pad((string)$num, 12, 0, STR_PAD_RIGHT);
        else
            $num = substr($num, 0, 12);
        if ($this->dao->count(['verify_code' => $num])) {
            return $this->getStoreCode();
        }
        return $num;
    }

    /**
     * 创建订单
     * @param $uid
     * @param $key
     * @param $cartGroup
     * @param $userInfo
     * @param $addressId
     * @param $payType
     * @param bool $useIntegral
     * @param int $couponId
     * @param string $mark
     * @param int $combinationId
     * @param int $pinkId
     * @param int $seckillId
     * @param int $bargainId
     * @param int $isChannel
     * @param int $shippingType
     * @param string $real_name
     * @param string $phone
     * @param int $storeId
     * @param bool $news
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createOrder($uid, $key, $cartGroup, $userInfo, $addressId, $payType, $useIntegral = false, $couponId = 0, $mark = '', $combinationId = 0, $pinkId = 0, $seckillId = 0, $bargainId = 0, $isChannel = 0, $shippingType = 1, $real_name = '', $phone = '', $storeId = 0, $news = false)
    {
        /** @var StoreOrderComputedServices $computedServices */
        $computedServices = app()->make(StoreOrderComputedServices::class);
        $priceData = $computedServices->computedOrder($uid, $key, $cartGroup, $addressId, $payType, $useIntegral, $couponId, true, $shippingType);

        /** @var UserAddressServices $addressServices */
        $addressServices = app()->make(UserAddressServices::class);
        if ($shippingType === 1) {
            if (!$addressId) {
                throw new ValidateException('请选择收货地址!');
            }
            if (!$addressInfo = $addressServices->getOne(['uid' => $uid, 'id' => $addressId, 'is_del' => 0]))
                throw new ValidateException('地址选择有误!');
            $addressInfo = $addressInfo->toArray();
        } else {
            if ((!$real_name || !$phone)) {
                throw new ValidateException('请填写姓名和电话');
            }
            $addressInfo['real_name'] = $real_name;
            $addressInfo['phone'] = $phone;
            $addressInfo['province'] = '';
            $addressInfo['city'] = '';
            $addressInfo['district'] = '';
            $addressInfo['detail'] = '';
        }
        $cartInfo = $cartGroup['cartInfo'];
        $priceGroup = $cartGroup['priceGroup'];
        $cartIds = [];
        $totalNum = 0;
        $gainIntegral = 0;
        foreach ($cartInfo as $cart) {
            $cartIds[] = $cart['id'];
            $totalNum += $cart['cart_num'];
            if (!$seckillId) $seckillId = $cart['seckill_id'];
            if (!$bargainId) $bargainId = $cart['bargain_id'];
            if (!$combinationId) $combinationId = $cart['combination_id'];
            $cartInfoGainIntegral = isset($cart['productInfo']['give_integral']) ? bcmul((string)$cart['cart_num'], (string)$cart['productInfo']['give_integral'], 2) : 0;
            $gainIntegral = bcadd((string)$gainIntegral, (string)$cartInfoGainIntegral, 2);
        }
        $deduction = $seckillId || $bargainId || $combinationId;
        if ($deduction) {
            $couponId = 0;
            $useIntegral = false;
            unset($computedServices->payType['offline']);
            if (!array_key_exists($payType, $computedServices->payType)) {
                throw new ValidateException('营销商品不能使用线下支付!');
            }
        }
        //$shipping_type = 1 快递发货 $shipping_type = 2 门店自提
        $storeSelfMention = sys_config('store_self_mention') ?? 0;
        if (!$storeSelfMention) $shippingType = 1;

        $orderInfo = [
            'uid' => $uid,
            'order_id' => $this->getNewOrderId(),
            'real_name' => $addressInfo['real_name'],
            'user_phone' => $addressInfo['phone'],
            'user_address' => $addressInfo['province'] . ' ' . $addressInfo['city'] . ' ' . $addressInfo['district'] . ' ' . $addressInfo['detail'],
            'cart_id' => $cartIds,
            'total_num' => $totalNum,
            'total_price' => $priceGroup['totalPrice'],
            'total_postage' => $priceGroup['storePostage'],
            'coupon_id' => $couponId,
            'coupon_price' => $priceData['coupon_price'],
            'pay_price' => $priceData['pay_price'],
            'pay_postage' => $priceData['pay_postage'],
            'deduction_price' => $priceData['deduction_price'],
            'paid' => 0,
            'pay_type' => $payType,
            'use_integral' => $priceData['usedIntegral'],
            'gain_integral' => $gainIntegral,
            'mark' => htmlspecialchars($mark),
            'combination_id' => $combinationId,
            'pink_id' => $pinkId,
            'seckill_id' => $seckillId,
            'bargain_id' => $bargainId,
            'cost' => $priceGroup['costPrice'],
            'is_channel' => $isChannel,
            'add_time' => time(),
            'unique' => $key,
            'shipping_type' => $shippingType,
        ];
        if ($shippingType === 2) {
            $orderInfo['verify_code'] = $this->getStoreCode();
            /** @var SystemStoreServices $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $orderInfo['store_id'] = $storeServices->getStoreDispose($storeId, 'id');
            if (!$orderInfo['store_id']) {
                throw new ValidateException('暂无门店无法选择门店自提');
            }
        }
        /** @var StoreOrderCartInfoServices $cartServices */
        $cartServices = app()->make(StoreOrderCartInfoServices::class);
        /** @var StoreSeckillServices $seckillServices */
        $seckillServices = app()->make(StoreSeckillServices::class);
        $order = $this->transaction(function () use ($cartIds, $orderInfo, $cartInfo, $key, $userInfo, $useIntegral, $priceData, $combinationId, $seckillId, $bargainId, $cartServices, $seckillServices) {
            //创建订单
            $order = $this->dao->save($orderInfo);
            if (!$order) {
                throw new ValidateException('订单生成失败!');
            }
            //占用库存
            $seckillServices->occupySeckillStock($cartInfo, $key);
            //积分抵扣
            $this->deductIntegral($userInfo, $useIntegral, $priceData, (int)$userInfo['uid'], $key);
            //扣库存
            $this->decGoodsStock($cartInfo, $combinationId, $seckillId, $bargainId);
            //保存购物车商品信息
            $cartServices->setCartInfo($order['id'], $cartInfo);
            return $order;
        });
        $this->orderCreateAfter($addressServices, $order, compact('cartInfo', 'addressId', 'cartIds', 'news'));
        CacheService::redisHandler()->delete('user_order_' . $uid . $key);
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $statusService->save([
            'oid' => $order['id'],
            'change_type' => 'cache_key_create_order',
            'change_message' => '订单生成',
            'change_time' => time()
        ]);
        $this->pushJob($order['id'], $combinationId, $seckillId, $bargainId);
        return $order;
    }

    /**
     * 订单自动取消加入延迟消息队列
     * @param int $orderId
     * @param int $combinationId
     * @param int $seckillId
     * @param int $bargainId
     * @return mixed
     */
    public function pushJob(int $orderId, int $combinationId, int $seckillId, int $bargainId)
    {
        //系统预设取消订单时间段
        $keyValue = ['order_cancel_time', 'order_activity_time', 'order_bargain_time', 'order_seckill_time', 'order_pink_time'];
        //获取配置
        $systemValue = SystemConfigService::more($keyValue);
        //格式化数据
        $systemValue = Arr::setValeTime($keyValue, is_array($systemValue) ? $systemValue : []);
        if ($combinationId) {
            $secs = $systemValue['order_pink_time'] ? $systemValue['order_pink_time'] : $systemValue['order_activity_time'];
        } elseif ($seckillId) {
            $secs = $systemValue['order_seckill_time'] ? $systemValue['order_seckill_time'] : $systemValue['order_activity_time'];
        } elseif ($bargainId) {
            $secs = $systemValue['order_bargain_time'] ? $systemValue['order_bargain_time'] : $systemValue['order_activity_time'];
        } else {
            $secs = $systemValue['order_cancel_time'];
        }
        //未支付10分钟后发送短信
        Queue::instance()->job(UnpaidOrderSend::class)->secs(600)->data($orderId)->push();
        //未支付根据系统设置事件取消订单
        Queue::instance()->job(UnpaidOrderCancelJob::class)->secs((int)($secs * 3600))->data($orderId)->push();
    }

    /**
     * 抵扣积分
     * @param array $userInfo
     * @param bool $useIntegral
     * @param array $priceData
     * @param int $uid
     * @param string $key
     */
    public function deductIntegral(array $userInfo, bool $useIntegral, array $priceData, int $uid, string $key)
    {
        $res2 = true;
        if ($useIntegral && $userInfo['integral'] > 0) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            if (!$priceData['SurplusIntegral']) {
                $res2 = false !== $userServices->update($uid, ['integral' => 0]);
            } else {
                $res2 = false !== $userServices->bcDec($userInfo['uid'], 'integral', $priceData['usedIntegral'], 'uid');
            }
            /** @var UserBillServices $userBillServices */
            $userBillServices = app()->make(UserBillServices::class);
            $res3 = $userBillServices->income('deduction', $uid, [
                'number' => $priceData['usedIntegral'],
                'deductionPrice' => $priceData['deduction_price']
            ], $userInfo['integral'], $key);

            $res2 = $res2 && false != $res3;
        }
        if (!$res2) {
            throw new ValidateException('使用积分抵扣失败!');
        }
    }

    /**
     * 扣库存
     * @param array $cartInfo
     * @param int $combinationId
     * @param int $seckillId
     * @param int $bargainId
     */
    public function decGoodsStock(array $cartInfo, int $combinationId, int $seckillId, int $bargainId)
    {
        $res5 = true;
        /** @var StoreProductServices $services */
        $services = app()->make(StoreProductServices::class);
        /** @var StoreSeckillServices $seckillServices */
        $seckillServices = app()->make(StoreSeckillServices::class);
        /** @var StoreCombinationServices $pinkServices */
        $pinkServices = app()->make(StoreCombinationServices::class);
        /** @var StoreBargainServices $bargainServices */
        $bargainServices = app()->make(StoreBargainServices::class);
        foreach ($cartInfo as $cart) {
            //减库存加销量
            if ($combinationId) $res5 = $res5 && $pinkServices->decCombinationStock((int)$cart['cart_num'], $combinationId, isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '');
            else if ($seckillId) $res5 = $res5 && $seckillServices->decSeckillStock((int)$cart['cart_num'], $seckillId, isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '');
            else if ($bargainId) $res5 = $res5 && $bargainServices->decBargainStock((int)$cart['cart_num'], $bargainId, isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '');
            else $res5 = $res5 && $services->decProductStock((int)$cart['cart_num'], (int)$cart['productInfo']['id'], isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '');
        }
        if (!$res5) {
            throw new ValidateException('扣库存失败!');
        }
    }

    /**
     * 订单创建后的后置事件
     * @param UserAddressServices $addressServices
     * @param $order
     * @param array $group
     */
    public function orderCreateAfter($addressServices, $order, array $group)
    {
        //设置用户默认地址
        if (!$addressServices->be(['is_default' => 1, 'uid' => $order['uid']])) {
            $addressServices->setDefaultAddress($group['addressId'], $order['uid']);
        }
        //删除购物车
        if ($group['news']) {
            array_map(function ($key) {
                CacheService::redisHandler()->delete($key);
            }, $group['cartIds']);
        } else {
            /** @var StoreCartServices $cartServices */
            $cartServices = app()->make(StoreCartServices::class);
            $cartServices->deleteCartStatus($group['cartIds']);
        }
    }

}
