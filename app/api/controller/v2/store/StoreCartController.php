<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-16
 */

namespace app\api\controller\v2\store;


use app\services\order\StoreCartServices;
use app\Request;

class StoreCartController
{
    protected $services;

    public function __construct(StoreCartServices $services)
    {
        $this->services = $services;
    }

    /**
     * 购物车重选
     * @param Request $request
     * @return mixed
     */
    public function resetCart(Request $request)
    {
        list($id, $unique, $num, $product_id) = $request->postMore([
            ['id', 0],
            ['unique', ''],
            ['num', 1],
            ['product_id', 0]
        ], true);
        $this->services->resetCart($id, $request->uid(), $product_id, $unique, $num);
        return app('json')->successful('修改成功');
    }
}
