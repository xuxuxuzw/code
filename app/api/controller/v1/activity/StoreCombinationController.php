<?php

namespace app\api\controller\v1\activity;

use app\Request;
use app\services\activity\StoreCombinationServices;
use app\services\activity\StorePinkServices;
use app\services\other\QrcodeServices;

/**
 * 拼团类
 * Class StoreCombinationController
 * @package app\api\controller\activity
 */
class StoreCombinationController
{
    protected $services;

    public function __construct(StoreCombinationServices $services)
    {
        $this->services = $services;
    }

    /**
     * 拼团列表
     * @return mixed
     */
    public function lst()
    {
        $list = $this->services->getCombinationList();
        return app('json')->successful($list);
    }


    /**
     * 拼团商品详情
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function detail(Request $request, $id)
    {
        $data = $this->services->combinationDetail($request, $id);
        return app('json')->successful($data);
    }

    /**
     * 拼团 开团
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function pink(Request $request, $id)
    {
        $data = $this->services->getPinkInfo($request, $id);
        return app('json')->successful($data);
    }

    /**
     * 拼团 取消开团
     * @param Request $request
     * @return mixed
     */
    public function remove(Request $request)
    {
        list($id, $cid) = $request->postMore([
            ['id', 0],
            ['cid', 0],
        ], true);
        if (!$id || !$cid) return app('json')->fail('缺少参数');
        /** @var StorePinkServices $pinkService */
        $pinkService = app()->make(StorePinkServices::class);
        $pinkService->removePink($request->uid(), $cid, $id);
        return app('json')->successful('取消成功');
    }


    /**
     * 拼团海报
     * @param Request $request
     * @return mixed
     */
    public function poster(Request $request)
    {
        list($pinkId, $from) = $request->postMore([
            ['id', 0],
            ['from', 'wechat']
        ], true);
        if (!$pinkId) return app('json')->fail('参数错误');
        $user = $request->user();
        /** @var StorePinkServices $pinkService */
        $pinkService = app()->make(StorePinkServices::class);
        $res = $pinkService->getPinkPoster($pinkId, $from, $user);
        return app('json')->successful(['url' => $res]);
    }

    /**
     * 获取秒杀小程序二维码
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function code(Request $request, $id)
    {
        /** @var QrcodeServices $qrcodeService */
        $qrcodeService = app()->make(QrcodeServices::class);
        $url = $qrcodeService->getRoutineQrcodePath($id, $request->uid(), 1);
        if ($url) {
            return app('json')->success(['code' => $url]);
        } else {
            return app('json')->success(['code' => '']);
        }
    }
}
