<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */
declare (strict_types=1);

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserBillDao;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Db;

/**
 *
 * Class UserBillServices
 * @package app\services\user
 * @method takeUpdate(int $uid, int $id) 修改收货状态
 * @method sum(array $where, string $field) 求和
 * @method count(array $where) 求条数
 */
class UserBillServices extends BaseServices
{

    /**
     * 用户记录模板
     * @var array[]
     */
    protected $incomeData = [
        'pay_give_integral' => [
            'title' => '购买商品赠送积分',
            'category' => 'integral',
            'type' => 'gain',
            'mark' => '购买商品赠送{%num%}积分',
            'status' => 1,
            'pm' => 1
        ],
        'order_give_integral' => [
            'title' => '下单赠送积分',
            'category' => 'integral',
            'type' => 'gain',
            'mark' => '下单赠送{%num%}积分',
            'status' => 1,
            'pm' => 1
        ],
        'order_give_exp' => [
            'title' => '下单赠送经验',
            'category' => 'exp',
            'type' => 'gain',
            'mark' => '下单赠送{%num%}经验',
            'status' => 1,
            'pm' => 1
        ],
        'get_brokerage' => [
            'title' => '获得推广佣金',
            'category' => 'now_money',
            'type' => 'brokerage',
            'mark' => '{%nickname%}成功消费{%pay_price%}元,奖励推广佣金{%number%}',
            'status' => 1,
            'pm' => 1
        ],
        'get_two_brokerage' => [
            'title' => '获得推广佣金',
            'category' => 'now_money',
            'type' => 'brokerage',
            'mark' => '二级推广人{%nickname%}成功消费{%pay_price%}元,奖励推广佣金{%number%}',
            'status' => 1,
            'pm' => 1
        ],
        'pay_product_refund' => [
            'title' => '商品退款',
            'category' => 'now_money',
            'type' => 'pay_product_refund',
            'mark' => '订单退款到余额{%num%}元',
            'status' => 1,
            'pm' => 1
        ],
        'integral_refund' => [
            'title' => '积分回退',
            'category' => 'integral',
            'type' => 'deduction',
            'mark' => '购买商品失败,回退积分{%num%}',
            'status' => 1,
            'pm' => 0
        ],
        'order_integral_refund' => [
            'title' => '返还下单使用积分',
            'category' => 'integral',
            'type' => 'sign',
            'mark' => '购买商品失败,回退积分{%num%}',
            'status' => 1,
            'pm' => 1
        ],
        'pay_product_integral_back' => [
            'title' => '商品退积分',
            'category' => 'integral',
            'type' => 'pay_product_integral_back',
            'mark' => '订单退积分{%num%}积分到用户积分',
            'status' => 1,
            'pm' => 1
        ],
        'deduction' => [
            'title' => '积分抵扣',
            'category' => 'integral',
            'type' => 'deduction',
            'mark' => '购买商品使用{%number%}积分抵扣{%deductionPrice%}元',
            'status' => 1,
            'pm' => 0
        ],
        'pay_product' => [
            'title' => '购买商品',
            'category' => 'now_money',
            'type' => 'pay_product',
            'mark' => '余额支付{%num%}元购买商品',
            'status' => 1,
            'pm' => 0
        ],
        'pay_money' => [
            'title' => '购买商品',
            'category' => 'now_money',
            'type' => 'pay_money',
            'mark' => '支付{%num%}元购买商品',
            'status' => 1,
            'pm' => 0
        ],
        'system_add' => [
            'title' => '系统增加余额',
            'category' => 'now_money',
            'type' => 'system_add',
            'mark' => '系统增加{%num%}元',
            'status' => 1,
            'pm' => 1
        ],
        'brokerage_to_nowMoney' => [
            'title' => '佣金提现到余额',
            'category' => 'now_money',
            'type' => 'extract',
            'mark' => '佣金提现到余额{%num%}元',
            'status' => 1,
            'pm' => 0
        ]
    ];

    /**
     * UserBillServices constructor.
     * @param UserBillDao $dao
     */
    public function __construct(UserBillDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * TODO 获取用户记录总和
     * @param $uid
     * @param string $category
     * @param array $type
     * @return mixed
     */
    public function getRecordCount(int $uid, $category = 'now_money', $type = [], $time = '', $pm = false)
    {

        $where = [];
        $where['uid'] = $uid;
        $where['category'] = $category;
        $where['status'] = 1;

        if (strlen(trim($type))) {
            $where['type'] = explode(',', $type);
        }
        if ($time) {
            $where['time'] = $time;
        }
        if ($pm) {
            $where['pm'] = 0;
        }
        return $this->dao->getBillSumColumn($where);
    }

    public function getUsersBokerageSum(array $where, $time = 0)
    {
        $where_data = [
            'type' => 'brokerage',
            'category' => 'now_money',
            'status' => 1,
            'pm' => $where['pm'] ?? 0,
            'uid' => $where['uid'] ?? 0
        ];
        if ($time) $where_data['time'] = $time;
        return $this->dao->getBillSumColumn($where_data);
    }

    /**
     * 某个用户佣金总和
     * @param int $uid
     * @return float
     */
    public function getUserBillBrokerageSum(int $uid)
    {
        $where = ['uid' => $uid, 'category' => 'now_money', 'type' => 'brokerage'];
        return $this->dao->getBillSum($where);
    }

    /**
     * 获取用户|所有佣金总数
     * @param int $uid
     * @param array $where_time
     * @return float
     */
    public function getBrokerageSum(int $uid = 0, $where_time = [])
    {
        $where = ['category' => 'now_money', 'type' => ['system_add', 'pay_product', 'extract', 'pay_product_refund', 'system_sub'], 'pm' => 1, 'status' => 1];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        return $this->dao->getBillSum($where);
    }

    public function getBrokerageNumSum($link_ids = [])
    {
        $where = ['category' => 'now_money', 'type' => ['brokerage']];
        if ($link_ids) $where['link_id'] = $link_ids;
        return $this->dao->getBillSum($where);
    }

    /**
     * 获取用户|所有佣金总数
     * @param int $uid
     * @param array $where_time
     * @return float
     */
    public function getBrokerageCount(int $uid = 0, $where_time = [])
    {
        $where = ['category' => 'now_money', 'type' => ['system_add', 'pay_product', 'extract', 'pay_product_refund', 'system_sub'], 'pm' => 1, 'status' => 1];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        return $this->dao->getBillCount($where);
    }

    /**
     * 用户|所有资金变动列表
     * @param int $uid
     * @param string $field
     * @return array
     */
    public function getBrokerageList(int $uid = 0, $where_time = [], string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $where = ['category' => 'now_money'];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        foreach ($list as &$item) {
            $value = array_filter($this->incomeData, function ($value) use ($item) {
                if ($item['type'] == $value['type']) {
                    return $item['title'];
                }
            });
            $item['type_title'] = $value[$item['type']]['title'] ?? '未知类型';
        }
        return compact('list', 'count');
    }

    /**
     * 获取用户的充值总数
     * @param int $uid
     * @return float
     */
    public function getRechargeSum(int $uid = 0, $where_time = [])
    {
        $where = ['category' => 'now_money', 'type' => 'recharge', 'pm' => 1, 'status' => 1];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        return $this->dao->getBillSum($where);
    }

    /**
     * 用户|所有充值列表
     * @param int $uid
     * @param string $field
     * @return array
     */
    public function getRechargeList(int $uid = 0, $where_time = [], string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $where = ['category' => 'now_money', 'type' => 'recharge'];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取用户的积分总数
     * @param int $uid
     * @return float
     */
    public function getIntegralSum(int $uid = 0, $where_time = [])
    {
        $where = ['category' => 'integral', 'type' => ['sign', 'system_add'], 'pm' => 1, 'status' => 1];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        return $this->dao->getBillSum($where);
    }

    /**
     * 获取用户的获取积分总次数
     * @param int $uid
     * @return float
     */
    public function getIntegralCount(int $uid = 0, $where_time = [])
    {
        $where = ['category' => 'integral', 'type' => ['sign', 'system_add'], 'pm' => 1, 'status' => 1];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        return $this->dao->getBillCount($where);
    }

    /**
     * 获取积分列表
     * @param int $uid
     * @param array $where_time
     * @param string $field
     * @return array
     */
    public function getIntegralList(int $uid = 0, $where_time = [], string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $where = ['category' => 'integral'];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取用户签到总数
     * @param int $uid
     * @return float
     */
    public function getSignlSum(int $uid = 0, $where_time = [])
    {
        $where = ['category' => 'integral', 'type' => 'sign', 'pm' => 1, 'status' => 1];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        return $this->dao->getBillSum($where);
    }

    /**
     * 获取用户的签到总次数
     * @param int $uid
     * @return float
     */
    public function getSignCount(int $uid = 0, $where_time = [])
    {
        $where = ['category' => 'integral', 'type' => 'sign', 'pm' => 1, 'status' => 1];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        return $this->dao->getBillCount($where);
    }

    /**
     * 获取签到列表
     * @param int $uid
     * @param array $where_time
     * @param string $field
     * @return array
     */
    public function getSignList(int $uid = 0, $where_time = [], string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $where = ['category' => 'integral', 'type' => 'sign'];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 经验总数
     * @param int $uid
     * @param array $where_time
     * @return float
     */
    public function getExpSum(int $uid = 0, $where_time = [])
    {
        $where = ['category' => 'exp', 'pm' => 1, 'status' => 1];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        return $this->dao->getBillSum($where);
    }

    /**
     * 获取所有经验列表
     * @param int $uid
     * @param array $where_time
     * @param string $field
     * @return array
     */
    public function getExpList(int $uid = 0, $where_time = [], string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $where = ['category' => ['exp']];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['time'] = $where_time;
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }


    /**
     * 增加佣金
     * @param int $uid
     * @param string $type
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function incomeNowMoney(int $uid, string $type, array $data)
    {
        $data['uid'] = $uid;
        $data['category'] = 'now_money';
        $data['type'] = $type;
        $data['pm'] = 1;
        $data['status'] = 1;
        $data['add_time'] = time();
        if (!$this->dao->save($data))
            throw new Exception('增加记录失败');
        return true;
    }

    /**
     * 扣除佣金
     * @param int $uid
     * @param string $type
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function expendNowMoney(int $uid, string $type, array $data)
    {
        $data['uid'] = $uid;
        $data['category'] = 'now_money';
        $data['type'] = $type;
        $data['pm'] = 0;
        $data['status'] = 1;
        $data['add_time'] = time();
        if (!$this->dao->save($data))
            throw new Exception('增加记录失败');
        return true;
    }

    /**
     * 增加积分
     * @param int $uid
     * @param string $type
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function incomeIntegral(int $uid, string $type, array $data)
    {
        $data['uid'] = $uid;
        $data['category'] = 'integral';
        $data['type'] = $type;
        $data['pm'] = 1;
        $data['status'] = 1;
        $data['add_time'] = time();
        if (!$this->dao->save($data))
            throw new Exception('增加记录失败');
        return true;
    }

    /**
     * 扣除积分
     * @param int $uid
     * @param string $type
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function expendIntegral(int $uid, string $type, array $data)
    {
        $data['uid'] = $uid;
        $data['category'] = 'integral';
        $data['type'] = $type;
        $data['pm'] = 0;
        $data['status'] = 1;
        $data['add_time'] = time();
        if (!$this->dao->save($data))
            throw new Exception('增加记录失败');
        return true;
    }


    /**
     * 写入用户记录
     * @param string $type 写入类型
     * @param int $uid
     * @param int|string|array $number
     * @param int|string $balance
     * @param int $link_id
     * @return bool|mixed
     */
    public function income(string $type, int $uid, $number, $balance, $link_id)
    {
        $data = $this->incomeData[$type] ?? null;
        if (!$data) {
            return true;
        }
        $data['uid'] = $uid;
        $data['balance'] = $balance;
        $data['link_id'] = $link_id;
        if (is_array($number)) {
            $key = array_keys($number);
            $key = array_map(function ($item) {
                return '{%' . $item . '%}';
            }, $key);
            $value = array_values($number);
            $data['number'] = $number['number'] ?? 0;
            $data['mark'] = str_replace($key, $value, $data['mark']);
        } else {
            $data['number'] = $number;
            $data['mark'] = str_replace(['{%num%}'], $number, $data['mark']);
        }
        $data['add_time'] = time();
        return $this->dao->save($data);
    }

    /**
     * 邀请新用户增加经验
     * @param int $spreadUid
     */
    public function inviteUserIncExp(int $spreadUid)
    {
        if (!$spreadUid) {
            return false;
        }
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $spread_user = $userService->getUserInfo($spreadUid);
        if (!$spread_user) {
            return false;
        }
        $exp_num = sys_config('invite_user_exp', 0);
        if ($exp_num) {
            $userService->incField($spreadUid, 'exp', (int)$exp_num);
            $data = [];
            $data['uid'] = $spreadUid;
            $data['number'] = $exp_num;
            $data['category'] = 'exp';
            $data['type'] = 'invite_user';
            $data['title'] = $data['mark'] = '邀新奖励';
            $data['balance'] = $spread_user['exp'];
            $data['pm'] = 1;
            $data['status'] = 1;
            $this->dao->save($data);
        }
        //检测会员等级
        try {
            /** @var UserLevelServices $levelServices */
            $levelServices = app()->make(UserLevelServices::class);
            //检测会员升级
            $levelServices->detection($spreadUid);
        } catch (\Throwable $e) {
            Log::error('会员等级升级失败,失败原因:' . $e->getMessage());
        }
        return true;
    }

    /**
     * 获取type
     * @param array $where
     * @param string $filed
     */
    public function getBillType(array $where)
    {
        return $this->dao->getType($where);
    }

    /**
     * 资金类型
     */
    public function bill_type()
    {
        $where = [];
        $where['not_type'] = ['gain', 'system_sub', 'deduction', 'sign'];
        $where['not_category'] = ['exp', 'integral'];
        return ['list' => $this->getBillType($where)];
    }

    /**
     * 获取资金列表
     * @param array $where
     * @param string $field
     * @return array
     */
    public function getBillList(array $where, string $field = '*', $is_page = true)
    {
        $where_data = [];
        if (isset($where['uid']) && $where['uid'] != '') {
            $where_data['uid'] = $where['uid'];
        }
        if ($where['start_time'] != '' && $where['end_time'] != '') {
            $where_data['time'] = str_replace('-', '/', $where['start_time']) . ' - ' . str_replace('-', '/', $where['end_time']);
        }
        if (isset($where['category']) && $where['category'] != '') {
            $where_data['category'] = $where['category'];
        }
        if (isset($where['type']) && $where['type'] != '') {
            $where_data['type'] = $where['type'];
        }
        $where_data['not_category'] = ['integral', 'exp'];
        $where_data['not_type'] = ['gain', 'system_sub', 'deduction', 'sign'];
        if (isset($where['nickname']) && $where['nickname'] != '') {
            $where_data['like'] = $where['nickname'];
        }
        $page = $limit = 0;
        if ($is_page) [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getBillList($where_data, $field, $page, $limit);
        foreach ($data as &$item) {
            $item['nickname'] = $item['user']['nickname'] ?? '';
            unset($item['user']);
        }
        $count = $this->dao->count($where_data);
        return compact('data', 'count');
    }

    /**
     * 获取佣金列表
     * @param array $where
     * @return array
     */
    public function getCommissionList(array $where)
    {
        $where_data = [];
        $where_data[] = ['b.type', '=', 'brokerage'];
        $where_data[] = ['b.category', '=', 'now_money'];

        if (isset($where['nickname']) && $where['nickname']) {
            $where_data[] = ['u.account|u.nickname|u.uid|u.phone', 'LIKE', "%$where[nickname]%"];
        }
        if (isset($where['price_max']) && isset($where['price_min']) && $where['price_max'] && $where['price_min']) {
            $where_data[] = ['u.brokerage_price', 'between', [$where['price_min'], $where['price_max']]];
        }
        /** @var UserUserBillServices $userUserBill */
        $userUserBill = app()->make(UserUserBillServices::class);
        [$count, $list] = $userUserBill->getBrokerageList($where_data, 'sum(b.number) as sum_number,u.nickname,u.uid,u.now_money,u.brokerage_price');
        $uids = array_unique(array_column($list, 'uid'));
        /** @var UserExtractServices $userExtract */
        $userExtract = app()->make(UserExtractServices::class);
        $extractSumList = $userExtract->getUsersSumList($uids);
        foreach ($list as &$item) {
            $item['extract_price'] = $extractSumList[$item['uid']] ?? 0;
        }
        return compact('count', 'list');
    }

    public function user_info(int $uid)
    {
        /** @var UserServices $user */
        $user = app()->make(UserServices::class);
        $user_info = $user->getUserInfo($uid, 'nickname,spread_uid,now_money,add_time');
        if (!$user_info) {
            throw new ValidateException('您查看的用户信息不存在!');
        }
        $user_info = $user_info->toArray();
        $user_info['number'] = $this->getUserBillBrokerageSum($uid);
        $user_info['add_time'] = date('Y-m-d H:i:s', $user_info['add_time']);
        $user_info['spread_name'] = $user_info['spread_uid'] ? $user->getUserInfo((int)$user_info['spread_uid'], 'nickname', true)['nickname'] ?? '' : '';
        return compact('user_info');
    }

    /**
     * 记录分享次数
     * @param int $uid 用户uid
     * @param int $cd 冷却时间
     * @return Boolean
     * */
    public function setUserShare(int $uid, $cd = 300)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('用户不存在！');
        }
        $cachename = 'Share_' . $uid;
        if (Cache::has($cachename)) {
            return false;
        }
        $data = ['title' => '用户分享记录', 'uid' => $uid, 'category' => 'share', 'type' => 'share', 'number' => 1, 'link_id' => 0, 'balance' => 0, 'mark' => date('Y-m-d H:i:s', time()) . ':用户分享'];
        if (!$this->dao->save($data)) {
            throw new ValidateException('记录分享记录失败');
        }
        Cache::set($cachename, 1, $cd);
        return true;
    }

    /**
     * 获取佣金提现列表
     * @param int $uid
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBillOneList(int $uid, array $where)
    {
        $where['uid'] = $uid;
        $data = $this->getBillList($where);
        foreach ($data['data'] as &$item) {
            $item['_add_time'] = $item['add_time'] ?? '';
        }
        return $data;
    }

    /**
     * 获取积分列表
     * @param array $where
     * @param string $field
     * @return array
     */
    public function getPointList(array $where, string $field = '*')
    {
        $where_data = [];
        $where_data['category'] = 'integral';
        if (isset($where['uid']) && $where['uid'] != '') {
            $where_data['uid'] = $where['uid'];
        }
        if ($where['start_time'] != '' && $where['end_time'] != '') {
            $where_data['time'] = $where['start_time'] . ' - ' . $where['end_time'];
        }
        if (isset($where['type']) && $where['type'] != '') {
            $where_data['type'] = $where['type'];
        }
        if (isset($where['nickname']) && $where['nickname'] != '') {
            $where_data['like'] = $where['nickname'];
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getBillList($where_data, $field, $page, $limit);
        foreach ($list as &$item) {
            $item['nickname'] = $item['user']['nickname'] ?? '';
            //$item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', (int)$item['add_time']) : '';
            unset($item['user']);
        }
        $count = $this->dao->count($where_data);
        return compact('list', 'count');
    }

    /**
     * 积分头部信息
     * @param array $where
     * @return array[]
     */
    public function getUserPointBadgelist(array $where)
    {
        $data = [];
        $where_data = [];
        $where_data['category'] = 'integral';
        if ($where['start_time'] != '' && $where['end_time'] != '') {
            $where_data['time'] = $where['start_time'] . ' - ' . $where['end_time'];
        }
        if (isset($where['nickname']) && $where['nickname'] != '') {
            $where_data['like'] = $where['nickname'];
        }
        $data['SumIntegral'] = $this->dao->getBillSumColumn($where_data);
        $where_data['type'] = 'sign';
        $data['CountSign'] = $this->dao->getUserSignPoint($where_data);
        $data['SumSign'] = $this->dao->getBillSumColumn($where_data);
        $where_data['type'] = 'deduction';
        $data['SumDeductionIntegral'] = $this->dao->getBillSumColumn($where_data);
        return [
            [
                'col' => 6,
                'count' => $data['SumIntegral'],
                'name' => '总积分(个)',
            ],
            [
                'col' => 6,
                'count' => $data['CountSign'],
                'name' => '客户签到次数(次)',
            ],
            [
                'col' => 6,
                'count' => $data['SumSign'],
                'name' => '签到送出积分(个)',
            ],
            [
                'col' => 6,
                'count' => $data['SumDeductionIntegral'],
                'name' => '使用积分(个)',
            ],
        ];
    }

    /**
     * 退佣金
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderRefundBrokerageBack(int $id, string $orderId)
    {
        $brokerageList = $this->dao->getUserBillList([
            'category' => 'now_money',
            'type' => 'brokerage',
            'link_id' => $id,
            'pm' => 1
        ]);
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $brokerages = $userServices->getColumn(['uid' => array_column($brokerageList, 'uid')], 'brokerage_price', 'uid');
        $userBillData = [];
        $res = true;
        foreach ($brokerageList as $item) {
            $usermoney = $brokerages[$item['uid']] ?? 0;
            if ($item['number'] > $usermoney) {
                $item['number'] = $usermoney;
            }
            $res = $res && $userServices->bcDec($item['uid'], 'brokerage_price', (string)$item['number'], 'uid');
            $userBillData[] = [
                'title' => '退款退佣金',
                'uid' => $item['uid'],
                'pm' => 0,
                'add_time' => time(),
                'category' => 'now_money',
                'type' => 'brokerage',
                'number' => $item['number'],
                'link_id' => $id,
                'balance' => bcsub((string)$usermoney, (string)$item['number'], 2),
                'mark' => '订单退款扣除佣金' . floatval($item['number']) . '元'
            ];
        }
        if ($userBillData) {
            $res = $res && $this->dao->saveAll($userBillData);
        }
        /** @var UserBrokerageFrozenServices $services */
        $services = app()->make(UserBrokerageFrozenServices::class);
        $services->updateFrozen($orderId);
        return $res;
    }

    /**
     * 佣金排行
     * @param string $time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function brokerageRankList(string $time = 'week')
    {
        $where = [];
        $where['category'] = 'now_money';
        $where['type'] = 'brokerage';
        if ($time) {
            $where['time'] = $time;
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->brokerageRankList($where, $page, $limit);
        foreach ($list as $key => &$item) {
            $item['nickname'] = $item['user']['nickname'] ?? '';
            $item['avatar'] = $item['user']['avatar'] ?? '';
            if ($item['brokerage_price'] == '0.00' || $item['brokerage_price'] == 0 || !$item['brokerage_price']) {
                unset($list[$key]);
            }
            unset($item['user']);
        }
        return $list;
    }

    /**
     * 获取用户排名
     * @param int $uid
     * @param string $time
     */
    public function getUserBrokerageRank(int $uid, string $time = 'week')
    {
        $where = [];
        $where['category'] = 'now_money';
        $where['type'] = 'brokerage';
        if ($time) {
            $where['time'] = $time;
        }
        $list = $this->dao->brokerageRankList($where, 0, 0);

        $position_tmp_one = array_column($list, 'uid');
        $position_tmp_two = array_column($list, 'brokerage_price', 'uid');
        if (!in_array($uid, $position_tmp_one)) {
            $position = 0;
        } else {
            if ($position_tmp_two[$uid] == 0.00) {
                $position = 0;
            } else {
                $position = array_search($uid, $position_tmp_one) + 1;
            }
        }
        return $position;
    }


    /**
     * 推广数据    昨天的佣金   累计提现金额  当前佣金
     * @param int $uid
     * @return mixed
     */
    public function commission(int $uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (!$userServices->getUserInfo($uid)) {
            throw new ValidateException('数据不存在');
        }
        /** @var UserExtractServices $userExtract */
        $userExtract = app()->make(UserExtractServices::class);
        $data = [];
        $data['uid'] = $uid;
        $data['pm'] = 1;
        $data['commissionCount'] = $this->getUsersBokerageSum($data);
        $data['lastDayCount'] = $this->getUsersBokerageSum($data, 'yesterday');//昨天的佣金
        $data['extractCount'] = $userExtract->getUserExtract($uid);//累计提现金额

        return $data;
    }

    /**
     * 前端佣金排行页面数据
     * @param int $uid
     * @param $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function brokerage_rank(int $uid, $type)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        if (!$userService->getUserInfo($uid)) {
            throw new ValidateException('数据不存在');
        }
        return [
            'rank' => $this->brokerageRankList($type),
            'position' => $this->getUserBrokerageRank($uid, $type)
        ];
    }

    /**
     * @param $uid
     * @param $type
     * @return array
     */
    public function getUserBillList(int $uid, int $type)
    {
        $where = [];
        $where['uid'] = $uid;
        $where['category'] = 'now_money';
        switch ((int)$type) {
            case 0:
                $where['type'] = ['recharge', 'brokerage', 'pay_money', 'system_add', 'pay_product_refund', 'system_sub'];
                break;
            case 1:
                $where['type'] = ['pay_money'];
                break;
            case 2:
                $where['type'] = ['recharge', 'system_add'];
                break;
            case 3:
                $where['type'] = ['brokerage'];
                break;
            case 4:
                $where['type'] = ['extract'];
                break;
        }
        $field = 'FROM_UNIXTIME(add_time,"%Y-%m") as time,group_concat(id SEPARATOR ",") ids';
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getUserBillListByGroup($where, $field, 'time', $page, $limit);
        $data = [];
        if ($list) {
            $listIds = array_column($list, 'ids');
            $ids = [];
            foreach ($listIds as $id) {
                $ids = array_merge($ids, explode(',', $id));
            }
            $info = $this->dao->getColumn([['id', 'in', $ids]], 'FROM_UNIXTIME(add_time,"%Y-%m-%d %H:%i") as add_time,title,number,pm', 'id');
            foreach ($list as $item) {
                $value['time'] = $item['time'];
                $id = explode(',', $item['ids']);
                array_multisort($id, SORT_DESC);
                $value['list'] = [];
                foreach ($id as $v) {
                    if (isset($info[$v])) {
                        $value['list'][] = $info[$v];
                    }
                }
                array_push($data, $value);
            }
        }
        return $data;
    }

    /**
     * 推广 佣金/提现 总和
     * @param int $uid
     * @param $type 3 佣金  4 提现
     * @return mixed
     */
    public function spread_count(int $uid, $type)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        if (!$userService->getUserInfo($uid)) {
            throw new ValidateException('数据不存在');
        }
        $count = 0;
        if ($type == 3) {
            $count1 = $this->getRecordCount($uid, 'now_money', 'brokerage');
            $count2 = $this->getRecordCount($uid, 'now_money', 'brokerage', '', true);
            $count = $count1 - $count2;
        } else if ($type == 4) {
            /** @var UserExtractServices $userExtract */
            $userExtract = app()->make(UserExtractServices::class);
            $count = $userExtract->getUserExtract($uid);//累计提现
        }
        return $count ? $count : 0;
    }

    /**
     * 推广订单
     * @param Request $request
     * @return mixed
     */
    public function spread_order(int $uid, array $data)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        if (!$userService->getUserInfo($uid)) {
            throw new ValidateException('数据不存在');
        }
        $result = ['list' => [], 'count' => 0];
        $uids = $userService->getColumn(['spread_uid' => $uid], 'uid');
        /** @var UserBillStoreOrderServices $userBillStoreOrder */
        $userBillStoreOrder = app()->make(UserBillStoreOrderServices::class);
        $result['count'] = $userBillStoreOrder->getRecordOrderCount($uid, $uids, $data['category'], $data['type']) ?? 0;
        if ($result['count']) {
            $list = $userBillStoreOrder->getRecordList($uid, $uids, $data['category'], $data['type']);
            $times = array_map(function ($item) {
                return $item['time'];
            }, $list);
            $res = [];
            $infos = $userBillStoreOrder->getRecordOrderListDraw($uid, $uids, $times, $data['category'], $data['type']);
            if ($infos) {
                $uids = array_unique(array_column($infos, 'uid'));
                $userInfos = $userService->getColumn([['uid', 'in', $uids]], 'uid,avatar,nickname', 'uid');
                foreach ($times as $k => $time) {
                    $res[$k]['time'] = $time;
                    foreach ($infos as &$info) {
                        $info['avatar'] = $userInfos[$info['uid']]['avatar'] ?? '';
                        $info['nickname'] = $userInfos[$info['uid']]['nickname'] ?? '';
                        if ($info['time_key'] == $time) {
                            $res[$k]['child'][] = $info;
                        }
                    }
                    $res[$k]['count'] = count($res[$k]['child']);
                }
            }
            $result['list'] = $res;
        }
        return $result;
    }
}
