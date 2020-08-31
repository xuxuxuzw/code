<?php

namespace app\adminapi\controller;

use app\services\system\SystemAuthServices;
use app\services\order\StoreOrderServices;
use app\services\product\product\StoreProductServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\user\UserExtractServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\system\SystemMenusServices;
use app\services\user\UserServices;

/**
 * 公共接口基类 主要存放公共接口
 * Class Common
 * @package app\adminapi\controller
 */
class Common extends AuthController
{
    /**
     * @return mixed
     */
    public function check_auth()
    {
        return $this->checkAuthDecrypt();
    }

    /**
     * @return mixed
     */
    public function auth()
    {
        return $this->getAuth();
    }

    /**
     * 申请授权
     * @return mixed
     */
    public function auth_apply(SystemAuthServices $services)
    {
        $data = $this->request->postMore([
            ['company_name', ''],
            ['domain_name', ''],
            ['order_id', ''],
            ['phone', ''],
            ['label', 1],
            ['captcha', ''],
        ]);
        if (!$data['company_name']) {
            return $this->fail('请填写公司名称');
        }
        if (!$data['domain_name']) {
            return $this->fail('请填写授权域名');
        }

        if (!$data['phone']) {
            return $this->fail('请填写手机号码');
        }
        if (!$data['order_id']) {
            return $this->fail('请填写订单id');
        }
        if (!$data['captcha']) {
            return $this->fail('请填写验证码');
        }

        $services->authApply($data);
        return $this->success("申请授权成功!");

    }

    /**
     * 首页头部统计数据
     * @return mixed
     */
    public function homeStatics()
    {
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $info = $orderServices->homeStatics();
        return $this->success(compact('info'));
    }

    //增长率
    public function growth($left, $right)
    {
        if ($right)
            $ratio = bcmul(bcdiv(bcsub($left, $right, 2), $right, 4), 100, 2);
        else {
            if ($left)
                $ratio = 100;
            else
                $ratio = 0;
        }
        return $ratio;
    }

    /**
     * 订单图表
     */
    public function orderChart()
    {
        $cycle = $this->request->param('cycle') ?: 'thirtyday';//默认30天
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $chartdata = $orderServices->orderCharts($cycle);
        return $this->success($chartdata);
    }

    /**
     * 用户图表
     */
    public function userChart()
    {
        /** @var UserServices $uServices */
        $uServices = app()->make(UserServices::class);
        $chartdata = $uServices->userChart();
        return $this->success($chartdata);
    }

    /**
     * 交易额排行
     * @return mixed
     */
    public function purchaseRanking()
    {
        /** @var StoreProductAttrValueServices $valueServices */
        $valueServices = app()->make(StoreProductAttrValueServices::class);
        $list = $valueServices->purchaseRanking();
        return $this->success(compact('list'));
    }

    /**
     * 待办事统计
     * @return mixed
     */
    public function jnotice()
    {
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $data['ordernum'] = $orderServices->storeOrderCount();
        $store_stock = sys_config('store_stock');
        if ($store_stock < 0) $store_stock = 2;
        /** @var StoreProductServices $storeServices */
        $storeServices = app()->make(StoreProductServices::class);
        $data['inventory'] = $storeServices->count(['type' => 5, 'store_stock' => $store_stock]);//警戒库存
        $store_stock = sys_config('replenishment_num');
        if ($store_stock < 0) $store_stock = 20;
        $data['replenishmentnum'] = $storeServices->count(['type' => 5, 'store_stock' => $store_stock]);//待补货库存
        /** @var StoreProductReplyServices $replyServices */
        $replyServices = app()->make(StoreProductReplyServices::class);
        $data['commentnum'] = $replyServices->replyCount();
        /** @var UserExtractServices $extractServices */
        $extractServices = app()->make(UserExtractServices::class);
        $data['reflectnum'] = $extractServices->userExtractCount();//提现
        $data['msgcount'] = intval($data['ordernum']) + intval($data['inventory']) + intval($data['commentnum']) + intval($data['reflectnum']);
        $data['newOrderId'] = $orderServices->newOrderId(1);
        if (count($data['newOrderId'])) $orderServices->newOrderUpdate($data['newOrderId']);
        $value = [];
        if ($data['ordernum'] != 0) {
            $value[] = [
                'title' => "您有$data[ordernum]个待发货的订单",
                'type' => 'bulb',
                'url' => '/admin/product/product_list'
            ];
        }
        if ($data['replenishmentnum'] != 0) {
            $value[] = [
                'title' => "您有$data[replenishmentnum]个待补货的商品",
                'type' => 'bulb',
                'url' => '/admin/product/product_list'
            ];
        }
        if ($data['inventory'] != 0) {
            $value[] = [
                'title' => "您有$data[inventory]个商品库存预警",
                'type' => 'information',
                'url' => '/admin/product/product_list'
            ];
        }
        if ($data['commentnum'] != 0) {
            $value[] = [
                'title' => "您有$data[commentnum]条评论待回复",
                'type' => 'bulb',
                'url' => '/admin/product/product_reply'
            ];
        }
        if ($data['reflectnum'] != 0) {
            $value[] = [
                'title' => "您有$data[reflectnum]个提现申请待审核",
                'type' => 'bulb',
                'url' => '/admin/finance/user_extract/index'
            ];
        }
        return $this->success($this->noticeData($value));
    }

    /**
     * 消息返回格式
     * @param array $data
     * @return array
     */
    public function noticeData(array $data): array
    {
        // 消息图标
        $iconColor = [
            // 邮件 消息
            'mail' => [
                'icon' => 'md-mail',
                'color' => '#3391e5'
            ],
            // 普通 消息
            'bulb' => [
                'icon' => 'md-bulb',
                'color' => '#87d068'
            ],
            // 警告 消息
            'information' => [
                'icon' => 'md-information',
                'color' => '#fe5c57'
            ],
            // 关注 消息
            'star' => [
                'icon' => 'md-star',
                'color' => '#ff9900'
            ],
            // 申请 消息
            'people' => [
                'icon' => 'md-people',
                'color' => '#f06292'
            ],
        ];
        // 消息类型
        $type = array_keys($iconColor);
        // 默认数据格式
        $default = [
            'icon' => 'md-bulb',
            'iconColor' => '#87d068',
            'title' => '',
            'url' => '',
            'type' => 'bulb',
            'read' => 0,
            'time' => 0
        ];
        $value = [];
        foreach ($data as $item) {
            $val = array_merge($default, $item);
            if (isset($item['type']) && in_array($item['type'], $type)) {
                $val['type'] = $item['type'];
                $val['iconColor'] = $iconColor[$item['type']]['color'] ?? '';
                $val['icon'] = $iconColor[$item['type']]['icon'] ?? '';
            }
            $value[] = $val;
        }
        return $value;
    }

    /**
     * 格式化菜单
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function menusList()
    {
        /** @var SystemMenusServices $menusServices */
        $menusServices = app()->make(SystemMenusServices::class);
        $list = $menusServices->getSearchList();
        $counts = $menusServices->getColumn([
            ['is_show', '=', 1],
            ['auth_type', '=', 1],
            ['is_del', '=', 0],
            ['is_show_path', '=', 0],
        ], 'pid');
        $data = [];
        foreach ($list as $key => $item) {
            $pid = $item->getData('pid');
            $data[$key] = json_decode($item, true);
            $data[$key]['pid'] = $pid;
            if (in_array($item->id, $counts)) {
                $data[$key]['type'] = 1;
            } else {
                $data[$key]['type'] = 0;
            }
        }
        return app('json')->success(sort_list_tier($data));
    }
}
