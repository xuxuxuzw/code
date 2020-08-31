<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/8
 */
declare (strict_types=1);

namespace app\services\order;

use app\services\activity\StoreBargainServices;
use app\services\activity\StoreBargainUserServices;
use app\services\activity\StoreCombinationServices;
use app\services\activity\StoreSeckillServices;
use app\services\BaseServices;
use app\dao\order\StoreCartDao;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use crmeb\services\CacheService;
use think\exception\ValidateException;

/**
 *
 * Class StoreCartServices
 * @package app\services\order
 * @method updateCartStatus($cartIds) 修改购物车状态
 * @method getUserCartNum(int $uid, string $type, int $numType) 购物车数量
 * @method deleteCartStatus(array $cartIds) 修改购物车状态
 */
class StoreCartServices extends BaseServices
{

    /**
     * StoreCartServices constructor.
     * @param StoreCartDao $dao
     */
    public function __construct(StoreCartDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取某个用户下的购物车数量
     * @param array $unique
     * @param int $productId
     * @param int $uid
     * @return array
     */
    public function getUserCartNums(array $unique, int $productId, int $uid)
    {
        $where['is_pay'] = 0;
        $where['is_del'] = 0;
        $where['is_new'] = 0;
        $where['type'] = 'product';
        $where['product_id'] = $productId;
        $where['uid'] = $uid;
        return $this->dao->getUserCartNums($where, $unique);
    }

    /**
     * 获取用户下的购物车列表
     * @param $uid
     * @param string $cartIds
     * @param bool $new
     * @param int $status
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserProductCartListV1($uid, $cartIds = '', bool $new, $status = 0)
    {
        if ($new) {
            $cartIds = explode(',', $cartIds);
            $cartInfo = [];
            foreach ($cartIds as $key) {
                $info = CacheService::redisHandler()->get($key);
                if ($info) {
                    $cartInfo[] = $info;
                }
            }
            if (!$cartInfo) {
                throw new ValidateException('获取购物车信息失败');
            }
            /** @var StoreProductServices $productServices */
            $productServices = app()->make(StoreProductServices::class);
            foreach ($cartInfo as &$item) {
                $productInfo = $item['productInfo'];
                if (isset($productInfo['attrInfo']['product_id']) && $item['product_attr_unique']) {
                    $item['costPrice'] = $productInfo['attrInfo']['cost'] ?? 0;
                    $item['trueStock'] = $productInfo['attrInfo']['stock'] ?? 0;
                    if ($item['seckill_id'] == 0 && $item['bargain_id'] == 0 && $item['combination_id'] == 0) {
                        $item['truePrice'] = $productServices->setLevelPrice($productInfo['attrInfo']['price'] ?? 0, $uid, true);
                        $item['vip_truePrice'] = (float)$productServices->setLevelPrice($productInfo['attrInfo']['price'], $uid);
                    } else {
                        $item['truePrice'] = $productInfo['attrInfo']['price'];
                        $item['vip_truePrice'] = 0;
                    }
                } else {
                    $item['costPrice'] = $item['productInfo']['cost'] ?? 0;
                    $item['trueStock'] = $item['productInfo']['stock'] ?? 0;
                    if ($item['seckill_id'] == 0 && $item['bargain_id'] == 0 && $item['combination_id'] == 0) {
                        $item['truePrice'] = $productServices->setLevelPrice($item['productInfo']['price'] ?? 0, $uid, true);
                        $item['vip_truePrice'] = (float)$productServices->setLevelPrice($item['productInfo']['price'], $uid);
                    } else {
                        $item['truePrice'] = $productInfo['productInfo']['price'];
                        $item['vip_truePrice'] = 0;
                    }
                }
            }
            $seckillIds = array_unique(array_column($cartInfo, 'seckill_id'));
            $bargainIds = array_unique(array_column($cartInfo, 'bargain_id'));
            $combinationId = array_unique(array_column($cartInfo, 'combination_id'));
            $deduction = ['seckill_id' => $seckillIds[0] ?? 0, 'bargain_id' => $bargainIds[0] ?? 0, 'combination_id' => $combinationId[0] ?? 0];
            return ['valid' => $cartInfo, 'invalid' => [], 'deduction' => $deduction];
        } else {
            return $this->getUserCartList($uid, $status, $cartIds);
        }
    }

    /**
     * 使用雪花算法生成订单ID
     * @return string
     * @throws \Exception
     */
    public function getCartId($prefix)
    {
        $snowflake = new \Godruoyi\Snowflake\Snowflake();
        //32位
        if (PHP_INT_SIZE == 4) {
            $id = abs($snowflake->id());
        } else {
            $id = $snowflake->setStartTimeStamp(strtotime('2020-06-05') * 1000)->id();
        }
        return $prefix . $id;
    }

    /**
     * 验证库存
     * @param int $uid
     * @param int $cartNum
     * @param string $unique
     * @param $productId
     * @param int $seckillId
     * @param int $bargainId
     * @param int $combinationId
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkProductStock(int $uid, int $cartNum, string $unique, $productId, int $seckillId, int $bargainId, int $combinationId)
    {
        if ($cartNum < 1) $cartNum = 1;
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);
        if ($seckillId) {
            if ($unique == '') {
                $unique = $attrValueServices->value(['product_id' => $seckillId, 'type' => 1], 'unique');
            }
            /** @var StoreSeckillServices $seckillService */
            $seckillService = app()->make(StoreSeckillServices::class);
            if (!$seckillService->getSeckillCount($seckillId)) {
                throw new ValidateException('活动已结束');
            }
            $StoreSeckillinfo = $seckillService->getValidProduct($seckillId);
            if (!$StoreSeckillinfo) {
                throw new ValidateException('该商品已下架或删除');
            }
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            $userBuyCount = $orderServices->count(['uid' => $uid, 'paid' => 1, 'seckill_id' => $seckillId]);
            if ($StoreSeckillinfo['num'] <= $userBuyCount || $StoreSeckillinfo['num'] < $cartNum) {
                throw new ValidateException('每人限购' . $StoreSeckillinfo['num'] . '件');
            }
            $res = $attrValueServices->getOne(['product_id' => $seckillId, 'unique' => $unique, 'type' => 1]);
            if ($cartNum > $res['quota']) {
                throw new ValidateException('该商品库存不足' . $cartNum);
            }
            $product_stock = $attrValueServices->value(['product_id' => $StoreSeckillinfo['product_id'], 'suk' => $res['suk'], 'type' => 0], 'stock');
            if ($product_stock < $cartNum) {
                throw new ValidateException('该商品库存不足' . $cartNum);
            }
            if (!$seckillService->isSeckillStock($unique, 1, (int)$cartNum)) {
                throw new ValidateException('商品库存不足' . $cartNum . ',无法购买请选择其他商品!');
            }
        } elseif ($bargainId) {
            /** @var StoreBargainServices $bargainService */
            $bargainService = app()->make(StoreBargainServices::class);
            if (!$bargainService->validBargain($bargainId)) {
                throw new ValidateException('该商品已下架或删除');
            }
            /** @var StoreBargainUserServices $bargainUserService */
            $bargainUserService = app()->make(StoreBargainUserServices::class);
            $bargainUserInfo = $bargainUserService->getOne(['uid' => $uid, 'bargain_id' => $bargainId, 'status' => 1, 'is_del' => 0]);
            if ($bargainUserInfo['bargain_price_min'] < bcsub((string)$bargainUserInfo['bargain_price'], (string)$bargainUserInfo['price'], 2)) {
                throw new ValidateException('砍价未成功');
            }
            $StoreBargainInfo = $bargainService->get($bargainId);
            $res = $attrValueServices->getOne(['product_id' => $bargainId, 'type' => 2]);
            $unique = $res['unique'];
            if ($cartNum > $res['quota']) {
                throw new ValidateException('该商品库存不足' . $cartNum);
            }
            $product_stock = $attrValueServices->value(['product_id' => $StoreBargainInfo['product_id'], 'suk' => $res['suk'], 'type' => 0], 'stock');
            if ($product_stock < $cartNum) {
                throw new ValidateException('该商品库存不足' . $cartNum);
            }
        } elseif ($combinationId) {//拼团
            if ($unique == '') {
                $unique = $attrValueServices->value(['product_id' => $combinationId, 'type' => 3], 'unique');
            }
            /** @var StoreCombinationServices $combinationService */
            $combinationService = app()->make(StoreCombinationServices::class);
            $StoreCombinationInfo = $combinationService->getCombinationOne($combinationId);
            if (!$StoreCombinationInfo) {
                throw new ValidateException('该商品已下架或删除');
            }
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            $userBuyCount = $orderServices->count(['uid' => $uid, 'paid' => 1, 'combination_id' => $combinationId]);
            if ($StoreCombinationInfo['num'] <= $userBuyCount || $StoreCombinationInfo['num'] < $cartNum) {
                throw new ValidateException('每人限购' . $StoreCombinationInfo['num'] . '件');
            }
            $res = $attrValueServices->getOne(['product_id' => $combinationId, 'unique' => $unique, 'type' => 3]);
            if ($cartNum > $res['quota']) {
                throw new ValidateException('该商品库存不足' . $cartNum);
            }
            $product_stock = $attrValueServices->value(['product_id' => $StoreCombinationInfo['product_id'], 'suk' => $res['suk'], 'type' => 0], 'stock');
            if ($product_stock < $cartNum) {
                throw new ValidateException('该商品库存不足' . $cartNum);
            }
        } else {
            if ($unique == '') {
                $unique = $attrValueServices->value(['product_id' => $productId, 'type' => 0], 'unique');
            }
            /** @var StoreProductServices $productServices */
            $productServices = app()->make(StoreProductServices::class);
            $res = $attrValueServices->getOne(['unique' => $unique, 'type' => 0]);
            if (!$productServices->isValidProduct($productId)) {
                throw new ValidateException('该商品已下架或删除');
            }
            if (!($unique && $attrValueServices->getAttrvalueCount($productId, $unique, 0))) {
                throw new ValidateException('请选择有效的商品属性');
            }
            if ($productServices->getProductStock($productId, $unique) < $cartNum) {
                throw new ValidateException('该商品库存不足' . $cartNum);
            }
        }
        return [$res, $unique, $bargainUserInfo['bargain_price_min'] ?? 0, $cartNum];
    }

    /**
     * 添加购物车
     * @param $uid
     * @param $product_id
     * @param int $cart_num
     * @param string $product_attr_unique
     * @param string $type
     * @param bool $new
     * @param int $combination_id
     * @param int $seckill_id
     * @param int $bargain_id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setCart($uid, $product_id, $cart_num = 1, $product_attr_unique = '', $type = 'product', $new = false, $combination_id = 0, $seckill_id = 0, $bargain_id = 0)
    {
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        /** @var StoreSeckillServices $seckillService */
        $seckillService = app()->make(StoreSeckillServices::class);
        /** @var StoreBargainServices $bargainService */
        $bargainService = app()->make(StoreBargainServices::class);
        /** @var StoreCombinationServices $combinationService */
        $combinationService = app()->make(StoreCombinationServices::class);
        [$res, $product_attr_unique, $bargainPriceMin, $cart_num] = $this->checkProductStock($uid, $cart_num, $product_attr_unique, (int)$product_id, $seckill_id, $bargain_id, $combination_id);
        if ($new) {
            $key = $this->getCartId($uid);
            $info['id'] = $key;
            $info['type'] = $type;
            $info['seckill_id'] = $seckill_id;
            $info['bargain_id'] = $bargain_id;
            $info['combination_id'] = $combination_id;
            $info['product_id'] = $product_id;
            $info['product_attr_unique'] = $product_attr_unique;
            $info['cart_num'] = $cart_num;
            $productInfoField = ['id', 'image', 'price', 'ot_price', 'vip_price', 'postage', 'give_integral', 'sales', 'stock', 'store_name', 'unit_name', 'is_show', 'is_del', 'is_postage', 'cost', 'is_sub', 'temp_id'];
            $seckillInfoField = ['id', 'image', 'price', 'ot_price', 'postage', 'give_integral', 'sales', 'stock', 'title as store_name', 'unit_name', 'is_show', 'is_del', 'is_postage', 'cost', 'temp_id', 'weight', 'volume', 'start_time', 'stop_time', 'time_id'];
            $bargainInfoField = ['id', 'image', 'min_price as price', 'price as ot_price', 'postage', 'give_integral', 'sales', 'stock', 'title as store_name', 'unit_name', 'status as is_show', 'is_del', 'is_postage', 'cost', 'temp_id', 'weight', 'volume'];
            $combinationInfoField = ['id', 'image', 'price', 'postage', 'sales', 'stock', 'title as store_name', 'is_show', 'is_del', 'is_postage', 'cost', 'temp_id', 'weight', 'volume'];
            if ($seckill_id) {
                $info['productInfo'] = $seckillService->get($seckill_id, $seckillInfoField)->toArray();
            } elseif ($bargain_id) {
                $info['productInfo'] = $bargainService->get($bargain_id, $bargainInfoField)->toArray();
            } elseif ($combination_id) {
                $info['productInfo'] = $combinationService->get($combination_id, $combinationInfoField)->toArray();
            } else {
                $info['productInfo'] = $productServices->get($product_id, $productInfoField)->toArray();
            }
            $info['productInfo']['attrInfo'] = $res->toArray();
            //砍价
            if ($bargain_id) {
                $info['truePrice'] = $bargainPriceMin;
                $info['productInfo']['attrInfo']['price'] = $bargainPriceMin;
            } else {
                $info['truePrice'] = $info['productInfo']['attrInfo']['price'] ?? $info['productInfo']['price'] ?? 0;
            }
            //拼团砍价秒杀不参与会员价
            if (!$bargain_id || !$combination_id || !$seckill_id) {
                $info['truePrice'] = $productServices->setLevelPrice($info['truePrice'], $uid, true);
                $info['vip_truePrice'] = (float)$productServices->setLevelPrice($info['truePrice'], $uid);
            } else {
                $info['vip_truePrice'] = 0;
            }
            $info['trueStock'] = $info['productInfo']['attrInfo']['stock'];
            $info['costPrice'] = $info['productInfo']['attrInfo']['cost'];
            try {
                CacheService::redisHandler()->set($key, $info, 24 * 3600);
            } catch (\Throwable $e) {
                throw new ValidateException($e->getMessage());
            }
            return $key;
        } else {
            $cart = $this->dao->getOne(['type' => $type, 'uid' => $uid, 'product_id' => $product_id, 'product_attr_unique' => $product_attr_unique, 'is_del' => 0, 'is_new' => 0, 'is_pay' => 0]);
            if ($cart) {
                $cart->cart_num = $cart_num + $cart->cart_num;
                $cart->add_time = time();
                $cart->save();
                return $cart->id;
            } else {
                $add_time = time();
                return $this->dao->save(compact('uid', 'product_id', 'cart_num', 'product_attr_unique', 'type', 'add_time'))->id;
            }
        }

    }

    /**移除购物车商品
     * @param int $uid
     * @param array $ids
     * @return StoreCartDao|bool
     */
    public function removeUserCart(int $uid, array $ids)
    {
        if (!$uid || !$ids) return false;
        return $this->dao->removeUserCart($uid, $ids);
    }

    /**购物车 修改商品数量
     * @param $id
     * @param $number
     * @param $uid
     * @return bool|\crmeb\basic\BaseModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeUserCartNum($id, $number, $uid)
    {
        if (!$id || !$number || !$uid) return false;
        $where = ['uid' => $uid, 'id' => $id];
        $carInfo = $this->dao->getOne($where, 'product_id,combination_id,seckill_id,bargain_id,product_attr_unique,cart_num');
        /** @var StoreProductServices $StoreProduct */
        $StoreProduct = app()->make(StoreProductServices::class);
        $stock = $StoreProduct->getProductStock($carInfo->product_id, $carInfo->product_attr_unique);
        if (!$stock) throw new ValidateException('暂无库存');
        if (!$number) throw new ValidateException('库存错误');
        if ($stock < $number) throw new ValidateException('库存不足' . $number);
        if ($carInfo->cart_num == $number) return true;
        return $this->dao->changeUserCartNum(['uid' => $uid, 'id' => $id], $number);
    }

    /**
     * 修改购物车状态
     * @param int $productId
     * @param int $type 1 商品下架
     */
    public function changeStatus(int $productId)
    {
        $this->dao->update($productId, ['status' => 0], 'product_id');
    }

    /**
     * 获取购物车列表
     * @param int $uid
     * @param int $status
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCartList(int $uid, int $status, string $cartIds = '')
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getCartList(['uid' => $uid, 'status' => $status, 'id' => $cartIds], $page, $limit, ['productInfo', 'attrInfo']);
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        foreach ($list as &$item) {
            $item['attrStatus'] = $item['attrInfo']['stock'] ? true : false;
            $item['productInfo']['attrInfo'] = $item['attrInfo'] ?? [];
            $item['productInfo']['attrInfo']['image'] = $item['attrInfo']['image'] ?? $item['productInfo']['image'];
            $item['productInfo']['attrInfo']['suk'] = $item['attrInfo']['suk'] ?? '已失效';
            $productInfo = $item['productInfo'];
            if (isset($productInfo['attrInfo']['product_id']) && $item['product_attr_unique']) {
                $item['costPrice'] = $productInfo['attrInfo']['cost'] ?? 0;
                $item['trueStock'] = $productInfo['attrInfo']['stock'] ?? 0;
                $item['truePrice'] = $productServices->setLevelPrice($productInfo['attrInfo']['price'] ?? 0, $uid, true);
                $item['vip_truePrice'] = (float)$productServices->setLevelPrice($productInfo['attrInfo']['price'], $uid);
            } else {
                $item['costPrice'] = $item['productInfo']['cost'] ?? 0;
                $item['trueStock'] = $item['productInfo']['stock'] ?? 0;
                $item['truePrice'] = $productServices->setLevelPrice($item['productInfo']['price'] ?? 0, $uid, true);
                $item['vip_truePrice'] = (float)$productServices->setLevelPrice($item['productInfo']['price'], $uid);
            }
            unset($item['attrInfo']);
        }
        $seckillIds = array_unique(array_column($list, 'seckill_id'));
        $bargainIds = array_unique(array_column($list, 'bargain_id'));
        $combinationId = array_unique(array_column($list, 'combination_id'));
        $deduction = ['seckill_id' => $seckillIds[0] ?? 0, 'bargain_id' => $bargainIds[0] ?? 0, 'combination_id' => $combinationId[0] ?? 0];
        if ($status == 1) {
            return ['valid' => $list, 'invalid' => [], 'deduction' => $deduction];
        } else {
            return ['valid' => [], 'invalid' => $list, 'deduction' => $deduction];
        }
    }

    /**
     * 购物车重选
     * @param int $cart_id
     * @param int $product_id
     * @param string $unique
     */
    public function modifyCart(int $cart_id, int $product_id, string $unique)
    {
        /** @var StoreProductAttrValueServices $attrService */
        $attrService = app()->make(StoreProductAttrValueServices::class);
        $stock = $attrService->value(['product_id' => $product_id, 'unique' => $unique, 'type' => 0], 'stock');
        if ($stock > 0) {
            $this->dao->update($cart_id, ['product_attr_unique' => $unique, 'cart_num' => 1]);
        } else {
            throw new ValidateException('选择的规格库存不足');
        }
    }

    /**
     * 重选购物车
     * @param $id
     * @param $uid
     * @param $productId
     * @param $unique
     * @param $num
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function resetCart($id, $uid, $productId, $unique, $num)
    {
        $res = $this->dao->getOne(['uid' => $uid, 'product_id' => $productId, 'product_attr_unique' => $unique]);
        if ($res) {
            $res->cart_num = $res->cart_num + $num;
            $res->save();
            $this->dao->delete($id);
        } else {
            $this->dao->update($id, ['product_attr_unique' => $unique, 'cart_num' => $num]);
        }
    }
}
