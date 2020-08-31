<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/6
 */
declare (strict_types=1);

namespace app\services\coupon;

use app\services\BaseServices;
use app\dao\coupon\StoreCouponIssueDao;
use app\services\product\product\StoreProductServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder;
use think\exception\ValidateException;

/**
 *
 * Class StoreCouponIssueServices
 * @package app\services\coupon
 * @method getUserIssuePrice(string $price) 获取金大于额的优惠卷金额
 * @method getNewCoupon() 获取新人券
 * @method getCouponInfo($id)
 * @method getColumn(array $where, string $field, ?string $key)
 * @method productCouponList(array $where, string $field)
 */
class StoreCouponIssueServices extends BaseServices
{

    /**
     * StoreCouponIssueServices constructor.
     * @param StoreCouponIssueDao $dao
     */
    public function __construct(StoreCouponIssueDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取已发布列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponIssueList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_del'] = 0;
        $list = $this->dao->getList($where, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 修改状态
     * @param int $id
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function createForm(int $id)
    {
        $issueInfo = $this->dao->get($id);
        if (-1 == $issueInfo['status'] || 1 == $issueInfo['is_del']) return $this->fail('状态错误,无法修改');
        $f = [FormBuilder::radio('status', '是否开启', $issueInfo['status'])->options([['label' => '开启', 'value' => 1], ['label' => '关闭', 'value' => 0]])];
        return create_form('状态修改', $f, $this->url('/marketing/coupon/released/status/' . $id), 'PUT');
    }

    /**
     * 领取记录
     * @param int $id
     * @return array
     */
    public function issueLog(int $id)
    {
        /** @var StoreCouponIssueUserServices $storeCouponIssueUserService */
        $storeCouponIssueUserService = app()->make(StoreCouponIssueUserServices::class);
        return $storeCouponIssueUserService->issueLog(['issue_coupon_id' => $id]);
    }

    /**
     * 关注送优惠券
     * @param int $uid
     */
    public function userFirstSubGiveCoupon(int $uid)
    {
        $couponList = $this->dao->getGiveCoupon(['is_give_subscribe' => 1]);
        if ($couponList) {
            $couponData = [];
            $issueUserData = [];
            $time = time();
            $ids = array_column($couponList, 'id');
            /** @var StoreCouponIssueUserServices $issueUser */
            $issueUser = app()->make(StoreCouponIssueUserServices::class);
            $userCouponIds = $issueUser->getColumn([['uid', '=', $uid], ['issue_coupon_id', 'in', $ids]], 'issue_coupon_id') ?? [];
            foreach ($couponList as $item) {
                if (!$userCouponIds || !in_array($item['id'], $userCouponIds)) {
                    $data['cid'] = $item['id'];
                    $data['uid'] = $uid;
                    $data['coupon_title'] = $item['title'];
                    $data['coupon_price'] = $item['coupon_price'];
                    $data['use_min_price'] = $item['use_min_price'];
                    $data['add_time'] = $time;
                    $data['end_time'] = $data['add_time'] + $item['coupon_time'] * 86400;
                    $data['type'] = 'get';
                    $issue['uid'] = $uid;
                    $issue['issue_coupon_id'] = $item['id'];
                    $issue['add_time'] = $time;
                    $issueUserData[] = $issue;
                    $couponData[] = $data;
                    unset($data);
                    unset($issue);
                }
            }
            if ($couponData) {
                /** @var StoreCouponUserServices $storeCouponUser */
                $storeCouponUser = app()->make(StoreCouponUserServices::class);
                if (!$storeCouponUser->saveAll($couponData)) {
                    throw new AdminException('发劵失败');
                }
            }
            if ($issueUserData) {
                if (!$issueUser->saveAll($issueUserData)) {
                    throw new AdminException('发劵失败');
                }
            }
        }
        return true;
    }

    /**
     * 订单金额达到预设金额赠送优惠卷
     * @param $uid
     */
    public function userTakeOrderGiveCoupon($uid, $total_price)
    {
        $couponList = $this->dao->getGiveCoupon([['is_full_give', '=', 1], ['full_reduction', '<=', $total_price]]);
        if ($couponList) {
            $couponData = $issueUserData = [];
            $time = time();
            $ids = array_column($couponList, 'id');
            /** @var StoreCouponIssueUserServices $issueUser */
            $issueUser = app()->make(StoreCouponIssueUserServices::class);
            $userCouponIds = $issueUser->getColumn([['uid', '=', $uid], ['issue_coupon_id', 'in', $ids]], 'issue_coupon_id') ?? [];
            foreach ($couponList as $item) {
                if ($total_price >= $item['full_reduction'] && (!$userCouponIds || !in_array($item['id'], $userCouponIds))) {
                    $data['cid'] = $item['id'];
                    $data['uid'] = $uid;
                    $data['coupon_title'] = $item['title'];
                    $data['coupon_price'] = $item['coupon_price'];
                    $data['use_min_price'] = $item['use_min_price'];
                    $data['add_time'] = $time;
                    $data['end_time'] = $data['add_time'] + $item['coupon_time'] * 86400;
                    $data['type'] = 'get';
                    $issue['uid'] = $uid;
                    $issue['issue_coupon_id'] = $item['id'];
                    $issue['add_time'] = $time;
                    $issueUserData[] = $issue;
                    $couponData[] = $data;
                    unset($data);
                    unset($issue);
                }
            }
            if ($couponData) {
                /** @var StoreCouponUserServices $storeCouponUser */
                $storeCouponUser = app()->make(StoreCouponUserServices::class);
                if (!$storeCouponUser->saveAll($couponData)) {
                    throw new AdminException('发劵失败');
                }
            }
            if ($issueUserData) {
                if (!$issueUser->saveAll($issueUserData)) {
                    throw new AdminException('发劵失败');
                }
            }
        }
        return true;
    }

    /**
     * 下单之后赠送
     * @param $uid
     */
    public function orderPayGiveCoupon($uid, $coupon_issue_ids)
    {
        if (!$coupon_issue_ids) return [];
        $couponList = $this->dao->getGiveCoupon([['id', 'IN', $coupon_issue_ids]]);
        $couponData = $issueUserData = [];
        if ($couponList) {
            $time = time();
            $ids = array_column($couponList, 'id');
            /** @var StoreCouponIssueUserServices $issueUser */
            $issueUser = app()->make(StoreCouponIssueUserServices::class);
            $userCouponIds = $issueUser->getColumn([['uid', '=', $uid], ['issue_coupon_id', 'in', $ids]], 'issue_coupon_id') ?? [];
            foreach ($couponList as $item) {
                if (!$userCouponIds || !in_array($item['id'], $userCouponIds)) {
                    $data['cid'] = $item['id'];
                    $data['uid'] = $uid;
                    $data['coupon_title'] = $item['title'];
                    $data['coupon_price'] = $item['coupon_price'];
                    $data['use_min_price'] = $item['use_min_price'];
                    $data['add_time'] = $time;
                    $data['end_time'] = $data['add_time'] + $item['coupon_time'] * 86400;
                    $data['type'] = 'get';
                    $issue['uid'] = $uid;
                    $issue['issue_coupon_id'] = $item['id'];
                    $issue['add_time'] = $time;
                    $issueUserData[] = $issue;
                    $couponData[] = $data;
                    unset($data);
                    unset($issue);
                }
            }
            if ($couponData) {
                /** @var StoreCouponUserServices $storeCouponUser */
                $storeCouponUser = app()->make(StoreCouponUserServices::class);
                if (!$storeCouponUser->saveAll($couponData)) {
                    throw new AdminException('发劵失败');
                }
            }
            if ($issueUserData) {
                if (!$issueUser->saveAll($issueUserData)) {
                    throw new AdminException('发劵失败');
                }
            }
        }
        return $couponData;
    }

    /**
     * 获取优惠券列表
     * @param int $uid
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getIssueCouponList(int $uid, array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $lst1 = $lst2 = $lst3 = [];
        $lst1 = $this->dao->getIssueCouponList($uid, 0, 0, $page, $limit);
        if ($where['type'] == 1 && $where['product_id'] != 0) {
            /** @var StoreProductServices $storeProductService */
            $storeProductService = app()->make(StoreProductServices::class);
            $cateId = $storeProductService->value(['id' => $where['product_id']], 'cate_id');
            $lst2 = $this->dao->getIssueCouponList($uid, 1, $cateId, $page, $limit);
            $lst3 = $this->dao->getIssueCouponList($uid, 2, $where['product_id'], $page, $limit);
        }
        $list = array_merge($lst1, $lst2, $lst3);
        $list = array_unique_fb($list);

        foreach ($list as &$v) {
            $v['coupon_price'] = floatval($v['coupon_price']);
            $v['use_min_price'] = floatval($v['use_min_price']);
            $v['is_use'] = $uid ? isset($v['used']) : false;
            if (!$v['end_time']) {
                $v['start_time'] = '';
                $v['end_time'] = '不限时';
            } else {
                $v['start_time'] = date('Y/m/d', $v['start_time']);
                $v['end_time'] = $v['end_time'] ? date('Y/m/d', $v['end_time']) : date('Y/m/d', time() + 86400);
            }
        }
        if ($list)
            return $list;
        else
            return [];
    }

    public function issueUserCoupon($id, $uid)
    {
        $issueCouponInfo = $this->dao->getInfo($id);
        if (!$issueCouponInfo) throw new ValidateException('领取的优惠劵已领完或已过期!');
        /** @var StoreCouponIssueUserServices $issueUserService */
        $issueUserService = app()->make(StoreCouponIssueUserServices::class);
        /** @var StoreCouponUserServices $couponUserService */
        $couponUserService = app()->make(StoreCouponUserServices::class);
        if ($issueUserService->getOne(['uid' => $uid, 'issue_coupon_id' => $id])) throw new ValidateException('已领取过该优惠劵!');
        if ($issueCouponInfo->remain_count <= 0 && !$issueCouponInfo->is_permanent) throw new ValidateException('抱歉优惠券已经领取完了！');
        $this->transaction(function () use ($issueUserService, $uid, $id, $couponUserService, $issueCouponInfo) {
            $issueUserService->save(['uid' => $uid, 'issue_coupon_id' => $id, 'add_time' => time()]);
            $couponUserService->addUserCoupon($uid, $issueCouponInfo);
            if ($issueCouponInfo['total_count'] > 0) {
                $issueCouponInfo['remain_count'] -= 1;
                $issueCouponInfo->save();
            }
        });
    }

    /**
     * 用户优惠劵列表
     * @param int $uid
     * @param $types
     * @return array
     */
    public function getUserCouponList(int $uid, $types)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (!$userServices->getUserInfo($uid)) {
            throw new ValidateException('数据不存在');
        }
        /** @var StoreCouponUserServices $storeConponUser */
        $storeConponUser = app()->make(StoreCouponUserServices::class);
        return $storeConponUser->getUserCounpon($uid, $types);
    }

    /**
     * 后台发送优惠券
     * @param $coupon
     * @param $user
     * @return bool
     */
    public function setCoupon($coupon, $user)
    {
        $data = [];
        $issueData = [];
        /** @var StoreCouponUserServices $storeCouponUser */
        $storeCouponUser = app()->make(StoreCouponUserServices::class);
        /** @var StoreCouponIssueUserServices $storeCouponIssueUser */
        $storeCouponIssueUser = app()->make(StoreCouponIssueUserServices::class);
        $uids = $storeCouponIssueUser->getColumn(['issue_coupon_id' => $coupon['id']], 'uid');
        foreach ($user as $k => $v) {
            if (in_array($v, $uids)) {
                continue;
            } else {
                $data[$k]['cid'] = $coupon['id'];
                $data[$k]['uid'] = $v;
                $data[$k]['coupon_title'] = $coupon['title'];
                $data[$k]['coupon_price'] = $coupon['coupon_price'];
                $data[$k]['use_min_price'] = $coupon['use_min_price'];
                $data[$k]['add_time'] = time();
                $data[$k]['end_time'] = $data[$k]['add_time'] + $coupon['coupon_time'] * 86400;
                $data[$k]['type'] = 'send';
                $issueData[$k]['uid'] = $v;
                $issueData[$k]['issue_coupon_id'] = $coupon['id'];
                $issueData[$k]['add_time'] = time();
            }
        }
        if (!empty($data)) {
            if (!$storeCouponUser->saveAll($data)) {
                throw new AdminException('发劵失败');
            }
            if (!$storeCouponIssueUser->saveAll($issueData)) {
                throw new AdminException('发劵失败');
            }
            return true;
        } else {
            throw new AdminException('选择用户已拥有该优惠券，请勿重复发放');
        }
    }
}
