<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/9
 */

namespace app\services\coupon;


use app\dao\coupon\StoreCouponUserCouponDao;
use app\services\BaseServices;

/**
 * 根据下单金额获取用户能使用的优惠卷
 * Class StoreCouponUserCouponServices
 * @package app\services\coupon
 * @method getUidCouponList(int $uid, string $truePrice, int $productId)
 * @method getUidCouponMinList($uid, $price, $value = '', int $type = 1) 获取购买金额最小使用范围内的优惠卷
 */
class StoreCouponUserCouponServices extends BaseServices
{
    /**
     * StoreCouponUserCouponServices constructor.
     * @param StoreCouponUserCouponDao $dao
     */
    public function __construct(StoreCouponUserCouponDao $dao)
    {
        $this->dao = $dao;
    }

}