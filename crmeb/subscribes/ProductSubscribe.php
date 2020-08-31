<?php

namespace crmeb\subscribes;

use app\services\product\product\StoreVisitServices;

/**
 * 商品事件
 * Class ProductSubscribe
 * @package crmeb\subscribes
 */
class ProductSubscribe
{


    public function handle()
    {

    }

    /**
     * 记录商品浏览记录
     * @param $event
     */
    public function onSetProductView($event)
    {
        [$uid, $id, $cate_id, $productType] = $event;
        app()->make(StoreVisitServices::class)->setView($uid, $id, $productType, $cate_id, 'view');
    }
}
