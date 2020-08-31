<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/7
 */
declare (strict_types=1);

namespace app\services\activity;

use app\services\BaseServices;
use app\dao\activity\StorePinkDao;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\user\UserServices;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\wechat\WechatServices;
use app\services\wechat\WechatUserServices;
use crmeb\jobs\PinkJob;
use crmeb\jobs\RoutineTemplateJob;
use crmeb\jobs\WechatTemplateJob;
use crmeb\services\MiniProgramService;
use crmeb\services\UploadService;
use crmeb\services\UtilService;
use crmeb\utils\Queue;
use think\exception\ValidateException;

/**
 *
 * Class StorePinkServices
 * @package app\services\activity
 * @method getPinkCount(array $where)
 * @method int count(array $where = []) 获取指定条件下的条数
 * @method getPinkOkSumTotalNum()
 * @method isPink(int $id, int $uid) 是否能继续拼团
 * @method getPinkUserOne(int $id) 拼团
 * @method getCount(array $where) 获取某些条件总数
 * @method value(array $where, string $field)
 * @method getColumn(array $where, string $field, string $key)
 * @method whereUpdate(array $where, array $data)
 */
class StorePinkServices extends BaseServices
{

    /**
     * StorePinkServices constructor.
     * @param StorePinkDao $dao
     */
    public function __construct(StorePinkDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @return array
     */
    public function systemPage(array $where)
    {
        $where['k_id'] = 0;
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $page, $limit);
        foreach ($list as &$item) {
            $item['count_people'] = $this->dao->count(['k_id' => $item['id']]) + 1;
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 拼团列表头部
     * @return array
     */
    public function getStatistics()
    {
        $res = [
            ['col' => 6, 'count' => $this->dao->count(), 'name' => '参与人数(人)', 'className' => 'ios-speedometer-outline'],
            ['col' => 6, 'count' => $this->dao->count(['k_id' => 0]), 'name' => '成团数量(个)', 'className' => 'md-rose'],
        ];
        return compact('res');
    }

    /**
     * 参团人员
     * @param int $id
     * @return array
     */
    public function getPinkMember(int $id)
    {
        return $this->dao->getList(['k_id' => $id, 'is_refund' => 0]);
    }

    /**
     * 拼团退款
     * @param $id
     * @return bool
     */
    public function setRefundPink($order)
    {
        $res = true;
        if ($order['pink_id']) {
            $id = $order['pink_id'];
        } else {
            return true;
        }
        //正在拼团 团长
        $count = $this->dao->getOne(['id' => $id, 'uid' => $order['uid']]);
        //正在拼团 团员
        $countY = $this->dao->getOne(['k_id' => $id, 'uid' => $order['uid']]);
        if (!$count && !$countY) {
            return $res;
        }
        if ($count) {//团长
            //判断团内是否还有其他人  如果有  团长为第二个进团的人
            $kCount = $this->dao->getPinking(['k_id' => $id]);
            if ($kCount) {
                $res11 = $this->dao->update($id, ['k_id' => $kCount['id']], 'k_id');
                $res12 = $this->dao->update($kCount['id'], ['stop_time' => $count['add_time'] + 86400, 'k_id' => 0]);
                $res1 = $res11 && $res12;
                $res2 = $this->dao->update($id, ['stop_time' => time() - 1, 'k_id' => 0, 'is_refund' => $kCount['id'], 'status' => 3]);
            } else {
                $res1 = true;
                $res2 = $this->dao->update($id, ['stop_time' => time() - 1, 'k_id' => 0, 'is_refund' => $id, 'status' => 3]);
            }
            //修改结束时间为前一秒  团长ID为0
            $res = $res1 && $res2;
        } else if ($countY) {//团员
            $res = $this->dao->update($countY['id'], ['stop_time' => time() - 1, 'k_id' => 0, 'is_refund' => $id, 'status' => 3]);
        }
        return $res;
    }

    public function getPinkList(int $id, bool $type)
    {
        $where['cid'] = $id;
        $where['k_id'] = 0;
        $where['is_refund'] = 0;
        $list = $this->dao->pinkList($where);
        if ($type) {
            $pinkAll = [];
            foreach ($list as &$v) {
                $v['count'] = $v['people'] - $this->dao->getPinkPeople($v['id']);
                $v['h'] = date('H', (int)$v['stop_time']);
                $v['i'] = date('i', (int)$v['stop_time']);
                $v['s'] = date('s', (int)$v['stop_time']);
                $pinkAll[] = $v['id'];//开团团长ID
                $v['stop_time'] = (int)$v['stop_time'];
            }
            return [$list, $pinkAll];
        }
        return $list;
    }

    public function getPinkOkList(int $uid)
    {
        $list = $this->dao->successList($uid);
        $msg = [];
        foreach ($list as &$item) {
            if (isset($item['nickname'])) $msg[] = $item['nickname'] .= '拼团成功';
        }
        return $msg;
    }

    /**
     * 查找拼团信息
     * @param $pink
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPinkMemberAndPinkK($pink)
    {
        //查找拼团团员和团长
        if ($pink['k_id']) {
            $pinkAll = $this->dao->getPinkUserList(['k_id' => $pink['k_id'], 'is_refund' => 0]);
            $pinkT = $this->dao->getPinkUserOne($pink['k_id']);
        } else {
            $pinkAll = $this->dao->getPinkUserList(['k_id' => $pink['id'], 'is_refund' => 0]);
            $pinkT = $pink;
        }
        $count = count($pinkAll) + 1;
        $count = $pinkT['people'] - $count;
        $idAll = [];
        $uidAll = [];
        //收集拼团用户id和拼团id
        foreach ($pinkAll as $k => $v) {
            $idAll[$k] = $v['id'];
            $uidAll[$k] = $v['uid'];
        }
        $idAll[] = $pinkT['id'];
        $uidAll[] = $pinkT['uid'];
        return [$pinkAll, $pinkT, $count, $idAll, $uidAll];
    }

    /**
     * 拼团失败
     * @param $pinkAll
     * @param $pinkT
     * @param $pinkBool
     * @param bool $isRunErr
     * @param bool $isIds
     * @return array|int
     */
    public function pinkFail($pinkAll, $pinkT, $pinkBool, $isRunErr = true, $isIds = false)
    {
        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        /** @var StoreOrderRefundServices $orderServiceRefund */
        $orderServiceRefund = app()->make(StoreOrderRefundServices::class);
        $pinkIds = [];
        try {
            if ($pinkT['stop_time'] < time()) {//拼团时间超时  退款
                $pinkBool = -1;
                array_push($pinkAll, $pinkT);
                foreach ($pinkAll as $v) {
                    $order = $orderService->get($v['order_id_key']);
                    $res1 = $orderServiceRefund->orderApplyRefund($order, '拼团时间超时');
                    $res2 = $this->dao->getCount([['uid', '=', $v['uid']], ['is_tpl', '=', 0], ['k_id|id', '=', $pinkT['id']]]);
                    if ($res1 && $res2) {
                        if ($isIds) array_push($pinkIds, $v['id']);
                        $this->orderPinkAfterNo($pinkT['uid'], $pinkT['id'], false, $order->is_channel);
                    } else {
                        if ($isRunErr) return $pinkBool;
                    }
                }
            }
            if ($isIds) return $pinkIds;
            return $pinkBool;
        } catch (\Exception $e) {
            return $pinkBool;
        }
    }

    /**
     * 失败发送消息和修改状态
     * @param $uid
     * @param $pid
     * @param bool $isRemove
     * @param $channel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderPinkAfterNo($uid, $pid, $isRemove = false, $channel)
    {
        $pink = $this->dao->getOne([['id|k_id', '=', $pid], ['uid', '=', $uid]], '*', ['getProduct']);
        /** @var WechatServices $wechatService */
        $wechatService = app()->make(WechatServices::class);
        $userOpenid = $wechatService->getOne(['uid' => $uid], 'openid,user_type');
        if ($isRemove) {
            if ($channel == 1) {
                if ($userOpenid['user_type'] == 'wechat') {
                    Queue::instance()->do('sendOrderPinkClone')->job(WechatTemplateJob::class)->data($userOpenid['openid'], $pink, $pink->title)->push();
                }
            } else {
                if ($userOpenid['user_type'] == 'routine') {
                    Queue::instance()->do('sendPinkFail')->job(RoutineTemplateJob::class)->data($userOpenid['openid'], $pink->title, $pink->people, '亲，您的拼团取消，点击查看订单详情', '/pages/order_details/index?order_id=' . $pink->order_id)->push();
                }
            }
        } else {
            if ($channel == 1) {
                if ($userOpenid['user_type'] == 'wechat') {
                    Queue::instance()->do('sendOrderPinkFial')->job(WechatTemplateJob::class)->data($userOpenid['openid'], $pink, $pink->title)->push();
                }
            } else {
                if ($userOpenid['user_type'] == 'routine') {
                    Queue::instance()->do('sendPinkFail')->job(RoutineTemplateJob::class)->data($userOpenid['openid'], $pink->title, $pink->people, '亲，您拼团失败，自动为您申请退款，退款金额为：' . $pink->price, '/pages/order_details/index?order_id=' . $pink->order_id)->push();
                }
            }
        }
        $this->dao->whereUpdate([['id|k_id', '=', $pid]], ['status' => 3, 'stop_time' => time()]);
    }


    /**
     * 判断拼团状态
     * @param $pinkId
     * @return bool
     */
    public function isPinkStatus($pinkId)
    {
        if (!$pinkId) return false;
        $stopTime = $this->dao->value(['id' => $pinkId], 'stop_time');
        if ($stopTime < time()) return true; //拼团结束
        else return false;//拼团未结束
    }

    /**
     * 获取拼团order_id
     * @param int $id
     * @param int $uid
     * @return mixed
     */
    public function getCurrentPink(int $id, int $uid)
    {
        $oid = $this->dao->value(['id' => $id, 'uid' => $uid], 'order_id_key');
        if (!$oid) $oid = $this->dao->value(['k_id' => $id, 'uid' => $uid], 'order_id_key');
        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        return $orderService->value(['id' => $oid], 'order_id');
    }

    /**
     * 拼团成功
     * @param $uidAll
     * @param $idAll
     * @param $uid
     * @param $pinkT
     * @return int
     */
    public function pinkComplete($uidAll, $idAll, $uid, $pinkT)
    {
        $pinkBool = 6;
        try {
            if (!$this->dao->getCount([['id', 'in', $idAll], ['is_refund', '=', 1]])) {
                $this->dao->whereUpdate([['id', 'in', $idAll]], ['stop_time' => time(), 'status' => 2]);
                if (in_array($uid, $uidAll)) {
                    if ($this->dao->getCount([['uid', 'in', $uidAll], ['is_tpl', '=', 0], ['k_id|id', '=', $pinkT['id']]]))
                        $this->orderPinkAfter($uidAll, $pinkT['id']);
                    $pinkBool = 1;
                } else  $pinkBool = 3;
            }
            return $pinkBool;
        } catch (\Exception $e) {
            return $pinkBool;
        }
    }

    /**
     * 拼团成功修改
     * @param $uidAll
     * @param $pid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderPinkAfter($uidAll, $pid)
    {
        $pinkInfo = $this->dao->getOne([['p.id|p.k_id', '=', $pid]], '*', ['getUser']);
        if (!$pinkInfo) return false;
        foreach ($uidAll as $key => &$item) {
            /** @var WechatServices $wechatService */
            $wechatService = app()->make(WechatServices::class);
            $userOpenid = $wechatService->getOne(['uid' => $item], 'openid,user_type');
            if ($userOpenid['user_type'] == 'wechat') { //公众号模板消息
                Queue::instance()->do('sendOrderPinkSuccess')->job(WechatTemplateJob::class)->data($userOpenid['openid'], $pinkInfo->order_id, $pinkInfo->title)->push();
            } else if ($userOpenid['user_type'] == 'routine') {// 小程序模板消息
                Queue::instance()->do('sendPinkSuccess')->job(RoutineTemplateJob::class)->data($userOpenid['openid'], $pinkInfo->title, $pinkInfo->nickname, $pinkInfo->add_time, $pinkInfo->people, '/pages/order_details/index?order_id=' . $pinkInfo->order_id)->push();
            }
        }
        $this->dao->whereUpdate([['uid', 'in', $uidAll], ['id|k_id', '=', $pid]], ['is_tpl' => 1]);
    }

    /**
     * 创建拼团
     * @param $order
     * @return mixed
     */
    public function createPink(array $orderInfo)
    {
        /** @var StoreCombinationServices $services */
        $services = app()->make(StoreCombinationServices::class);
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        if ($orderInfo['is_channel'] == 1) {
            $openid = $wechatUserServices->uidToOpenid($orderInfo['uid'], 'routine');
        } else {
            $openid = $wechatUserServices->uidToOpenid($orderInfo['uid'], 'wechat');
        }
        $product = $services->getOne(['id' => $orderInfo['combination_id']], 'effective_time,title,people');
        if (!$product) {
            return false;
        }
        if ($orderInfo['pink_id']) {
            //拼团存在
            $res = false;
            $pink['uid'] = $orderInfo['uid'];//用户id
            if ($this->isPinkBe($pink, $orderInfo['pink_id'])) return false;
            $pink['order_id'] = $orderInfo['order_id'];//订单id  生成
            $pink['order_id_key'] = $orderInfo['id'];//订单id  数据库id
            $pink['total_num'] = $orderInfo['total_num'];//购买个数
            $pink['total_price'] = $orderInfo['pay_price'];//总金额
            $pink['k_id'] = $orderInfo['pink_id'];//拼团id
            foreach ($orderInfo['cartInfo'] as $v) {
                $pink['cid'] = $v['combination_id'];//拼团商品id
                $pink['pid'] = $v['product_id'];//商品id
                $pink['people'] = $product['people'];//几人拼团
                $pink['price'] = $v['productInfo']['price'];//单价
                $pink['stop_time'] = 0;//结束时间
                $pink['add_time'] = time();//开团时间
                $res = $this->save($pink);
            }

            $openid && $this->joinPinkSuccessSend($orderInfo, $openid, $product['title'], $pink);
            //处理拼团完成
            list($pinkAll, $pinkT, $count, $idAll, $uidAll) = $this->getPinkMemberAndPinkK($pink);
            if ($pinkT['status'] == 1) {
                if (!$count)//组团完成
                    $this->pinkComplete($uidAll, $idAll, $pink['uid'], $pinkT);
                else
                    $this->pinkFail($pinkAll, $pinkT, 0);
            }

            if ($res) return true;
            else return false;
        } else {
            //创建拼团
            $res = false;
            $pink['uid'] = $orderInfo['uid'];//用户id
            $pink['order_id'] = $orderInfo['order_id'];//订单id  生成
            $pink['order_id_key'] = $orderInfo['id'];//订单id  数据库id
            $pink['total_num'] = $orderInfo['total_num'];//购买个数
            $pink['total_price'] = $orderInfo['pay_price'];//总金额
            $pink['k_id'] = 0;//拼团id
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            foreach ($orderInfo['cartInfo'] as $v) {
                $pink['cid'] = $v['combination_id'];//拼团商品id
                $pink['pid'] = $v['product_id'];//商品id
                $pink['people'] = $product['people'];//几人拼团
                $pink['price'] = $v['productInfo']['price'];//单价
                $pink['stop_time'] = time() + $product->effective_time * 3600;//结束时间
                $pink['add_time'] = time();//开团时间
                $res1 = $this->dao->save($pink);
                $res2 = $orderServices->update($orderInfo['id'], ['pink_id' => $res1['id']]);
                $res = $res1 && $res2;
                $pink['id'] = $res1['id'];
            }
            Queue::instance()->job(PinkJob::class)->secs(($product->effective_time * 3600) + 60)->data($pink['id'])->push();
            // 开团成功发送模板消息
            $openid && $this->pinkSuccessSend($orderInfo, $openid, $product['title'], $pink);
            if ($res) return true;
            else return false;
        }
    }

    /**
     * 是否拼团
     * @param array $data
     * @param int $id
     * @return int
     */
    public function isPinkBe(array $data, int $id)
    {
        $data['id'] = $id;
        $count = $this->dao->getCount($data);
        if ($count) return $count;
        $data['k_id'] = $id;
        $count = $this->dao->getCount($data);
        if ($count) return $count;
        else return 0;
    }

    /**
     * 参加拼团成功发送模板消息
     * @param array $order
     * @param string $openid
     * @param string $title
     * @param $pink
     * @return mixed
     */
    public function joinPinkSuccessSend(array $order, string $openid, string $title, $pink)
    {
        if ($order['is_channel'] == 1) {
            /** @var UserServices $services */
            $services = app()->make(UserServices::class);
            $nickname = $services->value(['uid' => $order['uid']], 'nickname');
            return Queue::instance()->do('sendPinkSuccess')->job(RoutineTemplateJob::class)
                ->data($openid, $title, $nickname, $pink['add_time'], $pink['people'], '/pages/order_details/index?order_id=' . $pink['order_id'])->push();
        } else {
            return Queue::instance()->do('sendOrderPinkUseSuccess')->job(WechatTemplateJob::class)->data($openid, $order['order_id'], $title, $order['pink_id'])->push();
        }
    }

    /**
     * 开团发送模板消息
     * @param array $order
     * @param string $openid
     * @param string $title
     * @param $pink
     * @return mixed
     */
    public function pinkSuccessSend(array $order, string $openid, string $title, $pink)
    {
        if ($order['is_channel'] == 1) {
            /** @var UserServices $services */
            $services = app()->make(UserServices::class);
            $nickname = $services->value(['uid' => $order['uid']], 'nickname');
            return Queue::instance()->do('sendPinkSuccess')->job(RoutineTemplateJob::class)
                ->data($openid, $title, $nickname, $pink['add_time'], $pink['people'], '/pages/order_details/index?order_id=' . $pink['order_id'])->push();
        } else {
            return Queue::instance()->do('sendOrderPinkOpenSuccess')->job(WechatTemplateJob::class)->data($openid, $pink, $title)->push();
        }
    }

    /**
     * 取消拼团
     * @param int $uid
     * @param int $cid
     * @param int $pink_id
     * @param null $nextPinkT
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function removePink(int $uid, int $cid, int $pink_id, $nextPinkT = null)
    {
        $pinkT = $this->dao->getOne([
            ['uid', '=', $uid],
            ['id', '=', $pink_id],
            ['cid', '=', $cid],
            ['k_id', '=', 0],
            ['is_refund', '=', 0],
            ['status', '=', 1],
            ['stop_time', '>', time()],
        ]);
        if (!$pinkT) throw new ValidateException('未查到拼团信息，无法取消');
        list($pinkAll, $pinkT, $count, $idAll, $uidAll) = $this->getPinkMemberAndPinkK($pinkT);
        if (count($pinkAll)) {
            $count = $pinkT['people'] - ($this->dao->count(['k_id' => $pink_id, 'is_refund' => 0]) + 1);
            if ($count) {
                //拼团未完成，拼团有成员取消开团取 紧跟团长后拼团的人
                if (isset($pinkAll[0])) $nextPinkT = $pinkAll[0];
            } else {
                //拼团完成
                $this->PinkComplete($uidAll, $idAll, $uid, $pinkT);
                throw new ValidateException('拼团已完成，无法取消');
            }
        }
        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        /** @var StoreOrderRefundServices $orderServiceRefund */
        $orderServiceRefund = app()->make(StoreOrderRefundServices::class);
        //取消开团
        $order = $orderService->get($pinkT['order_id_key']);
        $res1 = $orderServiceRefund->orderApplyRefund($order, '拼团时间超时');
        $res2 = $this->dao->getCount([['uid', '=', $pinkT['uid']], ['k_id|id', '=', $pinkT['id']]]);
        if ($res1 && $res2) {
            $this->orderPinkAfterNo($pinkT['uid'], $pinkT['id'], true, $order->is_channel);
        }
        //当前团有人的时候
        if (is_array($nextPinkT)) {
            $this->dao->update($nextPinkT['id'], ['k_id' => 0, 'status' => 1, 'stop_time' => $pinkT['stop_time']]);
            $this->dao->update($pinkT['id'], ['k_id' => $nextPinkT['id']], 'k_id');
            $orderService->update($nextPinkT['order_id'], ['pink_id' => $nextPinkT['id']], 'order_id');
        }
        return true;
    }

    /**
     * 获取拼团海报
     * @param $pinkId
     * @param $from
     * @param $user
     * @return string
     */
    public function getPinkPoster($pinkId, $from, $user)
    {
        $pinkInfo = $this->dao->get((int)$pinkId);
        /** @var StoreCombinationServices $combinationService */
        $combinationService = app()->make(StoreCombinationServices::class);
        $storeCombinationInfo = $combinationService->getOne(['id' => $pinkInfo['cid']], 'title,image,id,product_id', ['getPrice']);
        $data['title'] = $storeCombinationInfo['title'];
        $data['image'] = $storeCombinationInfo['image'];
        $data['price'] = $pinkInfo['total_price'];
        $data['label'] = $pinkInfo['people'] . '人团';
        if ($pinkInfo['k_id']) $pinkAll = $this->getPinkMember($pinkInfo['k_id']);
        else $pinkAll = $this->getPinkMember($pinkInfo['id']);
        $count = count($pinkAll) + 1;
        $data['msg'] = '原价￥' . $storeCombinationInfo['product_price'] . ' 还差' . ($pinkInfo['people'] - $count) . '人拼团成功';

        /** @var SystemAttachmentServices $systemAttachmentServices */
        $systemAttachmentServices = app()->make(SystemAttachmentServices::class);

        try {
            $siteUrl = sys_config('site_url');
            if ($from == 'routine') {
                //小程序
                $name = $pinkId . '_' . $user['uid'] . '_' . $user['is_promoter'] . '_pink_share_routine.jpg';
                $imageInfo = $systemAttachmentServices->getInfo(['name' => $name]);
                if (!$imageInfo) {
                    $valueData = 'id=' . $pinkId;
                    /** @var UserServices $userServices */
                    $userServices = app()->make(UserServices::class);
                    if ($userServices->checkUserPromoter((int)$user['uid'], $user)) {
                        $valueData .= '&pid=' . $user['uid'];
                    }
                    $res = MiniProgramService::qrcodeService()->appCodeUnlimit('pages/activity/goods_combination_status/index', $valueData, 280);
                    if (!$res) throw new ValidateException('二维码生成失败');
                    $uploadType = (int)sys_config('upload_type', 1);
                    $upload = UploadService::init();
                    $res = $upload->to('routine/activity/pink/code')->validate()->stream($res, $name);
                    if ($res === false) {
                        throw new ValidateException($upload->getError());
                    }
                    $imageInfo = $upload->getUploadInfo();
                    $imageInfo['image_type'] = $uploadType;
                    if ($imageInfo['image_type'] == 1) $remoteImage = UtilService::remoteImage($siteUrl . $imageInfo['dir']);
                    else $remoteImage = UtilService::remoteImage($imageInfo['dir']);
                    if (!$remoteImage['status']) throw new ValidateException($remoteImage['msg']);
                    $systemAttachmentServices->save([
                        'name' => $imageInfo['name'],
                        'att_dir' => $imageInfo['dir'],
                        'satt_dir' => $imageInfo['thumb_path'],
                        'att_size' => $imageInfo['size'],
                        'att_type' => $imageInfo['type'],
                        'image_type' => $imageInfo['image_type'],
                        'module_type' => 2,
                        'time' => time(),
                        'pid' => 1,
                        'type' => 1
                    ]);
                    $url = $imageInfo['dir'];
                } else $url = $imageInfo['att_dir'];
                $data['url'] = $url;
                if ($imageInfo['image_type'] == 1)
                    $data['url'] = $siteUrl . $url;
                $posterImage = UtilService::setShareMarketingPoster($data, 'routine/activity/pink/poster');
                if (!is_array($posterImage)) throw new ValidateException('海报生成失败');
                $systemAttachmentServices->save([
                    'name' => $posterImage['name'],
                    'att_dir' => $posterImage['dir'],
                    'satt_dir' => $posterImage['thumb_path'],
                    'att_size' => $posterImage['size'],
                    'att_type' => $posterImage['type'],
                    'image_type' => $posterImage['image_type'],
                    'module_type' => 2,
                    'time' => $posterImage['time'],
                    'pid' => 1,
                    'type' => 1
                ]);
                if ($posterImage['image_type'] == 1) $posterImage['dir'] = $siteUrl . $posterImage['dir'];
                $routinePosterImage = set_http_type($posterImage['dir'], 0);//小程序推广海报
                return $routinePosterImage;
            } else if ($from == 'wechat') {
                //公众号
                $name = $pinkId . '_' . $user['uid'] . '_' . $user['is_promoter'] . '_pink_share_wap.jpg';
                $imageInfo = $systemAttachmentServices->getInfo(['name' => $name]);
                if (!$imageInfo) {
                    $codeUrl = set_http_type($siteUrl . '/pages/activity/goods_combination_status/index?id=' . $pinkId . '&spread=' . $user['uid'], 1);//二维码链接
                    $imageInfo = UtilService::getQRCodePath($codeUrl, $name);
                    if (is_string($imageInfo)) {
                        throw new ValidateException('二维码生成失败');
                    }
                    $systemAttachmentServices->save([
                        'name' => $imageInfo['name'],
                        'att_dir' => $imageInfo['dir'],
                        'satt_dir' => $imageInfo['thumb_path'],
                        'att_size' => $imageInfo['size'],
                        'att_type' => $imageInfo['type'],
                        'image_type' => $imageInfo['image_type'],
                        'module_type' => 2,
                        'time' => $imageInfo['time'],
                        'pid' => 1,
                        'type' => 1
                    ]);
                    $url = $imageInfo['dir'];
                } else $url = $imageInfo['att_dir'];
                $data['url'] = $url;
                if ($imageInfo['image_type'] == 1) $data['url'] = $siteUrl . $url;
                $posterImage = UtilService::setShareMarketingPoster($data, 'wap/activity/pink/poster');
                if (!is_array($posterImage)) throw new ValidateException('海报生成失败');
                $systemAttachmentServices->save([
                    'name' => $posterImage['name'],
                    'att_dir' => $posterImage['dir'],
                    'satt_dir' => $posterImage['thumb_path'],
                    'att_size' => $posterImage['size'],
                    'att_type' => $posterImage['type'],
                    'image_type' => $posterImage['image_type'],
                    'module_type' => 2,
                    'time' => $posterImage['time'],
                    'pid' => 1,
                    'type' => 1
                ]);
                if ($posterImage['image_type'] == 1) $posterImage['dir'] = $siteUrl . $posterImage['dir'];
                $wapPosterImage = set_http_type($posterImage['dir'], 1);//公众号推广海报
                return $wapPosterImage;
            }
            throw new ValidateException('参数错误');
        } catch (\Exception $e) {
            throw new ValidateException($e->getMessage());
        }
    }

    /**
     * 修改到期的拼团状态
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function statusPink()
    {
        $pinkListEnd = $this->dao->pinkListEnd();
        $failPinkList = [];//拼团失败
        $successPinkList = [];//拼团失败
        foreach ($pinkListEnd as $key => &$value) {
            $countPeople = $this->count(['k_id' => $value['id']]) + 1;
            if ($countPeople == $value['people'])
                $successPinkList[] = $value['id'];
            else
                $failPinkList[] = $value['id'];
        }
        $success = $this->successPinkEdit($successPinkList);
        $error = $this->failPinkEdit($failPinkList);
        $res = $success && $error;
        return $res;
    }

    /**
     * 拼团成功
     * @param array $pinkRegimental 成功的团长编号
     * @return bool
     * @throws \Exception
     */
    public function successPinkEdit(array $pinkRegimental)
    {
        if (!count($pinkRegimental)) return true;
        foreach ($pinkRegimental as $key => &$item) {
            $pinkList = $this->dao->getColumn(['k_id' => $item], 'id', 'id');
            $pinkList[] = $item;
            $pinkList = implode(',', $pinkList);
            $this->dao->whereUpdate([['id', 'in', $pinkList]], ['stop_time' => time(), 'status' => 2]);
            $pinkUidList = $this->dao->getColumn([['id', 'in', $pinkList], ['is_tpl', '=', 0]], 'uid', 'uid');
            if (count($pinkUidList)) $this->orderPinkAfter($pinkUidList, $item);//发送模板消息
        }
        return true;
    }

    /**
     * 拼团失败
     * @param array $pinkRegimental 失败的团长编号
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function failPinkEdit(array $pinkRegimental)
    {
        if (!count($pinkRegimental)) return true;
        foreach ($pinkRegimental as $key => &$item) {
            $pinkList = $this->dao->getColumn(['k_id' => $item], 'id', 'id');
            $pinkList[] = $item;
            $pinkList = implode(',', $pinkList);
            $refundPinkList = $this->dao->getColumn([['id', 'in', $pinkList]], 'order_id,uid', 'id');
            if ($refundPinkList) {
                /** @var StoreOrderRefundServices $orderService */
                $orderService = app()->make(StoreOrderRefundServices::class);
                foreach ($refundPinkList as $key => &$item) {
                    $orderService->orderApplyRefund($item['order_id'], '拼团时间超时');//申请退款
                }
            }
            $this->dao->whereUpdate([['id', 'in', $pinkList]], ['status' => 3]);
//            $pinkUidList = $this->dao->getColumn([['id', 'in', $pinkList], ['is_tpl', '=', 0]], 'uid', 'uid');
        }
        return true;
    }
}
