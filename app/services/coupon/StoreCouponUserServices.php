<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/6
 */
declare (strict_types=1);

namespace app\services\coupon;

use app\services\BaseServices;
use app\dao\coupon\StoreCouponUserDao;
use app\services\product\product\StoreCategoryServices;
use app\services\product\product\StoreProductCateServices;
use crmeb\utils\Arr;

/**
 *
 * Class StoreCouponUserServices
 * @package app\services\coupon
 * @method useCoupon(int $id) 使用优惠券修改优惠券状态
 */
class StoreCouponUserServices extends BaseServices
{

    /**
     * StoreCouponUserServices constructor.
     * @param StoreCouponUserDao $dao
     */
    public function __construct(StoreCouponUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function systemPage(array $where)
    {
        /** @var StoreCouponUserUserServices $storeCouponUserUserService */
        $storeCouponUserUserService = app()->make(StoreCouponUserUserServices::class);
        return $storeCouponUserUserService->getList($where);
    }

    /**
     * 获取用户优惠券
     * @param int $id
     */
    public function getUserCouponList(int $id)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList(['uid' => $id], $page, $limit);
        foreach ($list as &$item) {
            $item['_add_time'] = date('Y-m-d H:i:s', $item['add_time']);
        }
        $count = $this->dao->count(['uid' => $id]);
        return compact('list', 'count');
    }

    /**
     * 恢复优惠券
     * @param int $id
     * @return bool|mixed
     */
    public function recoverCoupon(int $id)
    {
        $status = $this->dao->value(['id' => $id], 'status');
        if ($status) return $this->dao->update($id, ['status' => 0, 'use_time' => '']);
        else return true;
    }

    /**
     * 过期优惠卷失效
     */
    public function checkInvalidCoupon()
    {
        $this->dao->whereUpdate([['end_time', '<', time()], ['status', '=', '0']], ['status' => 2]);
    }

    /**
     * 获取用户有效优惠劵数量
     * @param int $uid
     * @return int
     */
    public function getUserValidCouponCount(int $uid)
    {
        $this->checkInvalidCoupon();
        return $this->dao->count(['uid' => $uid, 'status' => 0]);
    }

    /**
     * 下单页面显示可用优惠券
     * @param $uid
     * @param $cartGroup
     * @param $price
     * @return array
     */
    public function getUsableCouponList(int $uid, array $cartGroup)
    {
        $cartPrice = $cateIds = [];
        $productId = Arr::getUniqueKey($cartGroup['valid'], 'product_id');
        foreach ($cartGroup['valid'] as $value) {
            $cartPrice[] = bcmul((string)$value['truePrice'], (string)$value['cart_num'], 2);
        }
        $maxPrice = count($cartPrice) ? max($cartPrice) : 0;
        if ($productId) {
            /** @var StoreProductCateServices $productCateServices */
            $productCateServices = app()->make(StoreProductCateServices::class);
            $cateId = $productCateServices->productIdByCateId($productId);
            if ($cateId) {
                /** @var StoreCategoryServices $cateServices */
                $cateServices = app()->make(StoreCategoryServices::class);
                $catePids = $cateServices->cateIdByPid($cateId);
                $cateIds = array_merge($cateId, $catePids);
            } else {
                $cateIds = $cateId;
            }

        }
        $productCouponList = $this->dao->productIdsByCoupon($productId, $uid, (string)$maxPrice);
        $cateCouponList = $this->dao->cateIdsByCoupon($cateIds, $uid, (string)$maxPrice);
        $list = array_merge($productCouponList, $cateCouponList);
        $couponIds = Arr::getUniqueKey($list, 'id');
        $sumCartPrice = array_sum($cartPrice);
        $list1 = $this->dao->getUserCoupon($couponIds, $uid, (string)$sumCartPrice);
        $list = array_merge($list, $list1);
        foreach ($list as &$item) {
            $item['add_time'] = date('Y/m/d', $item['add_time']);
            $item['end_time'] = date('Y/m/d', $item['end_time']);
            $item['title'] = $item['coupon_title'];
            $item['type'] = $item['applicable_type'] ?? 0;
        }
        return $list;
    }

    public function addUserCoupon($uid, $issueCouponInfo, $type = 'get')
    {
        $data = [];
        $data['cid'] = $issueCouponInfo['id'];
        $data['uid'] = $uid;
        $data['coupon_title'] = $issueCouponInfo['title'];
        $data['coupon_price'] = $issueCouponInfo['coupon_price'];
        $data['use_min_price'] = $issueCouponInfo['use_min_price'];
        $data['add_time'] = time();
        $data['end_time'] = $data['add_time'] + $issueCouponInfo['coupon_time'] * 86400;
        $data['type'] = $type;
        return $this->dao->save($data);
    }

    /**
     * 获取用户已领取的优惠卷
     * @param int $uid
     * @param $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCounpon(int $uid, $type)
    {
        $where = [];
        $where['uid'] = $uid;
        switch ($type) {
            case 0:
            case '':
                break;
            case 1:
                $where['status'] = 0;
                break;
            case 2:
                $where['status'] = 1;
                break;
            default:
                $where['status'] = 1;
                break;
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getCouponListByOrder($where, 'is_fail ASC,status ASC,add_time DESC', $page, $limit);
        return $list ? $this->tidyCouponList($list) : [];
    }

    public function tidyCouponList($couponList)
    {
        $time = time();
        foreach ($couponList as &$coupon) {
            $coupon['use_min_price'] = number_format((float)$coupon['use_min_price'], 2);
            $coupon['coupon_price'] = number_format((float)$coupon['coupon_price'], 2);
            if ($coupon['is_fail']) {
                $coupon['_type'] = 0;
                $coupon['_msg'] = '已失效';
            } else if ($coupon['status'] == '已使用') {
                $coupon['_type'] = 0;
                $coupon['_msg'] = '已使用';
            } else if ($coupon['status'] == '已过期') {
                $coupon['_type'] = 0;
                $coupon['_msg'] = '已过期';
            } else if ($coupon['add_time'] > $time || $coupon['end_time'] < $time) {
                $coupon['_type'] = 0;
                $coupon['_msg'] = '已过期';
            } else {
                if ($coupon['add_time'] + 3600 * 24 > $time) {
                    $coupon['_type'] = 2;
                    $coupon['_msg'] = '可使用';
                } else {
                    $coupon['_type'] = 1;
                    $coupon['_msg'] = '可使用';
                }
            }
            $coupon['add_time'] = $coupon['_add_time'] = date('Y/m/d', $coupon['add_time']);
            $coupon['end_time'] = $coupon['_end_time'] = date('Y/m/d', $coupon['end_time']);
        }
        return $couponList;
    }
}
