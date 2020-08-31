<?php

namespace app\adminapi\controller\v1\agent;


use app\adminapi\controller\AuthController;
use app\services\agent\AgentManageServices;
use think\facade\App;


/**
 * 分销商管理控制器
 * Class AgentManage
 * @package app\adminapi\controller\v1\agent
 */
class AgentManage extends AuthController
{
    /**
     * AgentManage constructor.
     * @param App $app
     * @param AgentManageServices $services
     */
    public function __construct(App $app, AgentManageServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 分销管理列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['nickname', ''],
            ['data', ''],
        ]);
        return $this->success($this->services->agentSystemPage($where));
    }

    /**
     * 分销头部统计
     * @return mixed
     */
    public function get_badge()
    {
        $where = $this->request->getMore([
            ['data', ''],
            ['nickname', ''],
        ]);
        return $this->success(['res' => $this->services->getSpreadBadge($where)]);
    }

    /**
     * 推广人列表
     * @return mixed
     */
    public function get_stair_list()
    {
        $where = $this->request->getMore([
            ['uid', 0],
            ['data', ''],
            ['nickname', ''],
            ['type', '']
        ]);
        return $this->success($this->services->getStairList($where));
    }

    /**
     * 推广人列表头部统计
     * @return mixed
     */
    public function get_stair_badge()
    {
        $where = $this->request->getMore([
            ['uid', ''],
            ['data', ''],
            ['nickname', ''],
            ['type', ''],
        ]);
        return $this->success(['res' => $this->services->getSairBadge($where)]);
    }

    /**
     * 统计推广订单列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_stair_order_list()
    {
        $where = $this->request->getMore([
            ['uid', 0],
            ['data', ''],
            ['order_id', ''],
            ['type', ''],
        ]);
        return $this->success($this->services->getStairOrderList((int)$where['uid'], $where));
    }

    /**
     * 统计推广订单头部统计
     * @return mixed
     */
    public function get_stair_order_badge()
    {
        $where = $this->request->getMore([
            ['uid', ''],
            ['data', ''],
            ['order_id', ''],
            ['type', ''],
        ]);
        return $this->success($this->services->getStairOrderBadge($where));
    }

    /**
     * 查看公众号推广二维码
     * @param int $uid
     * @return json
     * */
    public function look_code($uid = '', $action = '')
    {
        if (!$uid || !$action) return $this->fail('缺少参数');
        try {
            if (method_exists($this, $action)) {
                $res = $this->$action($uid);
                if ($res)
                    return $this->success($res);
                else
                    return $this->fail(isset($res['msg']) ? $res['msg'] : '获取失败，请稍后再试！');
            } else
                return $this->fail('暂无此方法');
        } catch (\Exception $e) {
            return $this->fail('获取推广二维码失败，请检查您的微信配置', ['line' => $e->getLine(), 'messag' => $e->getMessage()]);
        }
    }

    /**
     * 获取公众号二维码
     * */
    public function wechant_code($uid)
    {
        $qr_code = $this->services->wechatCode((int)$uid);
        if (isset($qr_code['url']))
            return ['code_src' => $qr_code['url']];
        else
            return $this->fail('获取失败，请稍后再试！');
    }

    /**
     * TODO 查看小程序推广二维码
     * @param string $uid
     */
    public function look_xcx_code($uid = '')
    {
        if (!strlen(trim($uid))) {
            return $this->fail('缺少参数');
        }
        return $this->success($this->services->lookXcxCode((int)$uid));
    }

    /**
     * 查看H5推广二维码
     * @param string $uid
     * @return mixed|string
     */
    public function look_h5_code($uid = '')
    {
        if (!strlen(trim($uid))) return $this->fail('缺少参数');
        return $this->success($this->services->lookH5Code((int)$uid));
    }

    /**
     * 解除单个用户的推广权限
     * @param int $uid
     * */
    public function delete_spread($uid)
    {
        if (!$uid) $this->fail('缺少参数');
        return $this->success($this->services->delSpread((int)$uid) ? '解除成功' : '解除失败');
    }
}
