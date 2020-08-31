<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/6
 */
declare (strict_types=1);

namespace app\services\coupon;

use app\services\BaseServices;
use app\dao\coupon\StoreCouponProductDao;

/**
 *
 * Class StoreCouponProductServices
 * @package app\services\coupon
 */
class StoreCouponProductServices extends BaseServices
{

    /**
     * StoreCouponProductServices constructor.
     * @param StoreCouponProductDao $dao
     */
    public function __construct(StoreCouponProductDao $dao)
    {
        $this->dao = $dao;
    }

}
