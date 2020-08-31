<?php


namespace app\services\order;

use app\dao\order\StoreOrderCartInfoDao;
use app\services\BaseServices;
use crmeb\services\CacheService;
use crmeb\utils\Str;

/**
 * Class StoreOrderCartInfoServices
 * @package app\services\order
 * @method array getCartColunm(array $where, string $field, ?string $key) 获取购物车信息以数组返回
 * @method array getCartInfoList(array $where, array $field) 获取购物车详情列表
 * @method getOne(array $where, ?string $field = '*', array $with = []) 根据条件获取一条数据
 */
class StoreOrderCartInfoServices extends BaseServices
{
    /**
     * StorePinkServices constructor.
     * @param StorePinkDao $dao
     */
    public function __construct(StoreOrderCartInfoDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取指定订单下的商品详情
     * @param int $oid
     * @return array|mixed
     */
    public function getOrderCartInfo(int $oid)
    {
        return CacheService::get(md5('store_order_cart_info_' . $oid), function () use ($oid) {
            $cart_info = $this->dao->getColumn(['oid' => $oid], 'cart_info', 'cart_id');
            $info = [];
            foreach ($cart_info as $k => $v) {
                $_info = is_string($v) ? json_decode($v, true) : $v;
                if (!isset($_info['productInfo'])) $_info['productInfo'] = [];
                $info[$k]['cart_info'] = $_info;
                unset($_info);
            }
            return $info;
        }) ?: [];
    }

    /**
     * 查找购物车里的所有商品标题
     * @param $cartId
     * @return bool|string
     */
    public function getCarIdByProductTitle($cartId)
    {
        $title = '';
        $orderCart = $this->dao->getCartInfoList(['cart_id' => $cartId], ['cart_info']);
        foreach ($orderCart as $item) {
            if (isset($item['cart_info']['productInfo']['store_name'])) {
                $title .= $item['cart_info']['productInfo']['store_name'] . '|';
            }
        }
        if ($title) {
            $title = substr($title, 0, strlen($title) - 1);
        }
        return $title;
    }

    /**
     * 获取打印订单的商品信息
     * @param array $cartId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCartInfoPrintProduct(array $cartId)
    {
        $cartInfo = $this->dao->getCartInfoList(['cart_id' => $cartId], ['cart_info']);
        $product = [];
        foreach ($cartInfo as $item) {
            $value = is_string($item['cart_info']) ? json_decode($item['cart_info'], true) : $item['cart_info'];
            $value['productInfo']['store_name'] = $value['productInfo']['store_name'] ?? "";
            $value['productInfo']['store_name'] = Str::substrUTf8($value['productInfo']['store_name'], 10, 'UTF-8', '');
            $product[] = $value;
        }
        return $product;
    }

    /**
     * 获取产品返佣金额
     * @param array $cartId
     * @param bool $type true = 一级返佣, fasle = 二级返佣
     * @return string
     */
    public function getProductBrokerage(array $cartId, bool $type = true)
    {
        $cartInfo = $this->dao->getCartInfoList(['cart_id' => $cartId], ['cart_info']);
        $oneBrokerage = '0';//一级返佣金额
        $twoBrokerage = '0';//二级返佣金额
        $sumProductPrice = '0';//非指定返佣商品总金额
        foreach ($cartInfo as $value) {
            $cartNum = $value['cart_info']['cart_num'] ?? 0;
            if (isset($value['cart_info']['productInfo'])) {
                $productInfo = $value['cart_info']['productInfo'];
                //指定返佣金额
                if (isset($productInfo['is_sub']) && $productInfo['is_sub'] == 1) {
                    $oneBrokerage = bcadd($oneBrokerage, bcmul($cartNum, $productInfo['attrInfo']['brokerage'] ?? 0, 2), 2);
                    $twoBrokerage = bcadd($twoBrokerage, bcmul($cartNum, $productInfo['attrInfo']['brokerage_two'] ?? 0, 2), 2);
                } else {
                    //比例返佣
                    if (isset($productInfo['attrInfo'])) {
                        $sumProductPrice = bcadd($sumProductPrice, bcmul($cartNum, $productInfo['attrInfo']['price'] ?? 0, 2), 2);
                    } else {
                        $sumProductPrice = bcadd($sumProductPrice, bcmul($cartNum, $productInfo['price'] ?? 0, 2), 2);
                    }
                }
            }
        }
        if ($type) {
            //获取后台一级返佣比例
            $storeBrokerageRatio = sys_config('store_brokerage_ratio');
            //一级返佣比例 小于等于零时直接返回 不返佣
            if ($storeBrokerageRatio <= 0) {
                return $oneBrokerage;
            }
            //计算获取一级返佣比例
            $brokerageRatio = bcdiv($storeBrokerageRatio, 100, 2);
            $brokeragePrice = bcmul($sumProductPrice, $brokerageRatio, 2);
            //固定返佣 + 比例返佣 = 一级总返佣金额
            return bcadd($oneBrokerage, $brokeragePrice, 2);
        } else {
            //获取二级返佣比例
            $storeBrokerageTwo = sys_config('store_brokerage_two');
            //二级返佣比例小于等于0 直接返回
            if ($storeBrokerageTwo <= 0) {
                return $twoBrokerage;
            }
            //计算获取二级返佣比例
            $brokerageRatio = bcdiv($storeBrokerageTwo, 100, 2);
            $brokeragePrice = bcmul($sumProductPrice, $brokerageRatio, 2);
            //固定返佣 + 比例返佣 = 二级总返佣金额
            return bcadd($twoBrokerage, $brokeragePrice, 2);
        }
    }

    /**
     * 保存购物车info
     * @param $oid
     * @param array $cartInfo
     * @return int
     */
    public function setCartInfo($oid, array $cartInfo)
    {
        $group = [];
        foreach ($cartInfo as $cart) {
            $group[] = [
                'oid' => $oid,
                'cart_id' => $cart['id'],
                'product_id' => $cart['productInfo']['id'],
                'cart_info' => json_encode($cart),
                'unique' => md5($cart['id'] . '' . $oid)
            ];
        }
        return $this->dao->saveAll($group);
    }

    /**
     * 商品编号
     * @param $cartId
     * @return array
     */
    public function getCartIdsProduct($cartId)
    {
        return $this->dao->getColumn(['cart_id' => ['in' => $cartId]], 'product_id', 'id');
    }
}
