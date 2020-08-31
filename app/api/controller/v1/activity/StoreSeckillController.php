<?php

namespace app\api\controller\v1\activity;


use app\Request;
use app\services\activity\StoreSeckillServices;
use app\services\other\QrcodeServices;
use crmeb\services\GroupDataService;


/**
 * 秒杀商品类
 * Class StoreSeckillController
 * @package app\api\controller\activity
 */
class StoreSeckillController
{

    protected $services;

    public function __construct(StoreSeckillServices $services)
    {
        $this->services = $services;
    }

    /**
     * 秒杀商品时间区间
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //秒杀时间段
        $seckillTime = GroupDataService::getData('routine_seckill_time') ?? [];
        $seckillTimeIndex = 0;
        if (count($seckillTime)) {
            foreach ($seckillTime as $key => &$value) {
                $currentHour = date('H');
                $activityEndHour = bcadd((int)$value['time'], (int)$value['continued'], 0);
                if ($activityEndHour > 24) {
                    $value['time'] = strlen((int)$value['time']) == 2 ? (int)$value['time'] . ':00' : '0' . (int)$value['time'] . ':00';
                    $value['state'] = '即将开始';
                    $value['status'] = 2;
                    $value['stop'] = (int)bcadd(strtotime(date('Y-m-d')), bcmul($activityEndHour, 3600, 0));
                } else {
                    if ($currentHour >= (int)$value['time'] && $currentHour < $activityEndHour) {
                        $value['time'] = strlen((int)$value['time']) == 2 ? (int)$value['time'] . ':00' : '0' . (int)$value['time'] . ':00';
                        $value['state'] = '抢购中';
                        $value['stop'] = (int)bcadd(strtotime(date('Y-m-d')), bcmul($activityEndHour, 3600, 0));
                        $value['status'] = 1;
                        if (!$seckillTimeIndex) $seckillTimeIndex = $key;
                    } else if ($currentHour < (int)$value['time']) {
                        $value['time'] = strlen((int)$value['time']) == 2 ? (int)$value['time'] . ':00' : '0' . (int)$value['time'] . ':00';
                        $value['state'] = '即将开始';
                        $value['status'] = 2;
                        $value['stop'] = (int)bcadd(strtotime(date('Y-m-d')), bcmul($activityEndHour, 3600, 0));
                    } else if ($currentHour >= $activityEndHour) {
                        $value['time'] = strlen((int)$value['time']) == 2 ? (int)$value['time'] . ':00' : '0' . (int)$value['time'] . ':00';
                        $value['state'] = '已结束';
                        $value['status'] = 0;
                        $value['stop'] = (int)bcadd(strtotime(date('Y-m-d')), bcmul($activityEndHour, 3600, 0));
                    }
                }
            }
        }
        $data['lovely'] = sys_config('seckill_header_banner');
        if (strstr($data['lovely'], 'http') === false && strlen(trim($data['lovely']))) $data['lovely'] = sys_config('site_url') . $data['lovely'];
        $data['lovely'] = str_replace('\\', '/', $data['lovely']);
        $data['seckillTime'] = $seckillTime;
        $data['seckillTimeIndex'] = $seckillTimeIndex;
        return app('json')->successful($data);
    }

    /**
     * 秒杀商品列表
     * @param Request $request
     * @param $time
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lst($time)
    {
        if (!$time) return app('json')->fail('参数错误');
        $seckillInfo = $this->services->getListByTime($time);
        return app('json')->successful($seckillInfo);
    }

    /**
     * 秒杀商品详情
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function detail(Request $request, $id)
    {
        $data = $this->services->seckillDetail($request, $id);
        return app('json')->successful($data);
    }

    /**
     * 获取秒杀小程序二维码
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function code(Request $request, $id, $stop_time = '')
    {
        /** @var QrcodeServices $qrcodeService */
        $qrcodeService = app()->make(QrcodeServices::class);
        $url = $qrcodeService->getRoutineQrcodePath($id, $request->uid(), 2, compact('stop_time'));
        if ($url) {
            return app('json')->success(['code' => $url]);
        } else {
            return app('json')->success(['code' => '']);
        }
    }
}
