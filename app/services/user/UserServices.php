<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */
declare (strict_types=1);

namespace app\services\user;

use app\services\activity\StoreBargainServices;
use app\services\activity\StoreCombinationServices;
use app\services\activity\StoreSeckillServices;
use app\services\BaseServices;
use app\dao\user\UserDao;
use app\services\coupon\StoreCouponUserServices;
use app\services\message\service\StoreServiceServices;
use app\services\order\StoreOrderServices;
use app\services\other\QrcodeServices;
use app\services\product\product\StoreProductRelationServices;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\system\SystemUserLevelServices;
use app\services\wechat\WechatUserServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Route as Url;

/**
 *
 * Class UserServices
 * @package app\services\user
 * @method array getUserInfoArray(array $where, string $field, string $key) 根据条件查询对应的用户信息以数组形式返回
 * @method update($id, array $data, ?string $key = null) 修改数据
 * @method get(int $id) 获取一条数据
 * @method count(array $where) 获取指定条件下的数量
 * @method value(array $where, string $field) 获取指定的键值
 * @method bcInc($key, string $incField, string $inc, string $keyField = null, int $acc = 2) 高精度加法
 * @method bcDec($key, string $incField, string $inc, string $keyField = null, int $acc = 2) 高精度减法
 */
class UserServices extends BaseServices
{

    /**
     * UserServices constructor.
     * @param UserDao $dao
     */
    public function __construct(UserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取用户信息
     * @param $id
     * @param $field
     */
    public function getUserInfo(int $uid, $field = '*')
    {
        if (is_string($field)) $field = explode(',', $field);
        return $this->dao->get($uid, $field);
    }

    /**
     * 获取用户列表
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserList(array $where, string $field): array
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->getCount($where);
        return compact('list', 'count');
    }

    /**
     * 列表条数
     * @param array $where
     * @return int
     */
    public function getCount(array $where, bool $is_list = false)
    {
        return $this->dao->getCount($where, $is_list);
    }

    /**
     * 保存用户信息
     * @param $user
     * @param int $spreadUid
     * @param string $userType
     * @return User|\think\Model
     */
    public function setUserInfo($user, int $spreadUid = 0, string $userType = 'wechat')
    {
        $res = $this->dao->save([
            'account' => $user['account'] ?? 'wx' . rand(1, 9999) . time(),
            'pwd' => $user['pwd'] ?? md5('123456'),
            'nickname' => $user['nickname'] ?? '',
            'avatar' => $user['headimgurl'] ?? '',
            'phone' => $user['phone'] ?? '',
            'spread_uid' => $spreadUid,
            'add_time' => time(),
            'add_ip' => app()->request->ip(),
            'last_time' => time(),
            'last_ip' => app()->request->ip(),
            'user_type' => $userType
        ]);
        if (!$res)
            throw new AdminException('保存用户信息失败');
        /** @var UserBillServices $userBill */
        $userBill = app()->make(UserBillServices::class);
        //邀请新用户增加经验
        $userBill->inviteUserIncExp((int)$spreadUid);
        return $res;
    }

    /**
     * 某些条件用户佣金总和
     * @param array $where
     * @return mixed
     */
    public function getSumBrokerage(array $where)
    {
        return $this->dao->getWhereSumField($where, 'brokerage_price');
    }

    /**
     * 根据条件获取用户指定字段列表
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getColumn(array $where, string $field = '*', string $key = '')
    {
        return $this->dao->getColumn($where, $field, $key);
    }

    /**
     * 获取某个用户的推广下线
     */
    public function getSpreadList($uid)
    {
        $one_uids = $this->dao->getColumn(['spread_uid' => $uid], 'uid');
        $two_uids = $this->dao->getColumn([['spread_uid', 'in', $one_uids], ['spread_uid', '<>', 0]], 'uid');
        $uids = array_merge($one_uids, $two_uids);
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList(['uid' => $uids], 'uid,nickname,real_name,avatar,add_time', $page, $limit);
        foreach ($list as $k => $user) {
            $list[$k]['type'] = in_array($user['uid'], $one_uids) ? '一级' : '二级';
            $list[$k]['add_time'] = date('Y-m-d', $user['add_time']);
        }
        $count = count($uids);
        return compact('count', 'list');
    }

    /**查找多个uid信息
     * @param $uids
     * @param bool $field
     * @return UserDao|bool|\crmeb\basic\BaseModel|mixed|\think\Collection
     */
    public function getUserListByUids($uids, $field = false)
    {
        if (!$uids || !is_array($uids)) return false;
        return $this->dao->getUserListByUids($uids, $field);
    }

    /**
     * 获取分销用户
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAgentUserList(array $where = [], string $field = '*')
    {
        $where['status'] = 1;
        $where['is_promoter'] = 1;
        if (isset($where['nickname']) && $where['nickname'] !== '') {
            $where['like'] = $where['nickname'];
        }
        if (isset($where['data']) && $where['data']) {
            $where['time'] = $where['data'];
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getAgentUserList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        return compact('count', 'list');
    }

    /**
     * 获取分销员ids
     * @param array $where
     * @return array
     */
    public function getAgentUserIds(array $where)
    {
        $where['status'] = 1;
        $where['is_promoter'] = 1;
        if (isset($where['nickname']) && $where['nickname'] !== '') {
            $where['like'] = $where['nickname'];
        }
        if (isset($where['data']) && $where['data']) {
            $where['time'] = $where['data'];
        }
        return $this->dao->getAgentUserIds($where);
    }

    /**
     * 获取推广人列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSairList(array $where, string $field = '*')
    {
        $where_data = [];
        if (isset($where['uid'])) {
            if (isset($where['type'])) {
                $uids = $this->getColumn(['spread_uid' => $where['uid']], 'uid');
                switch ((int)$where['type']) {
                    case 1:
                        $where_data['uid'] = count($uids) > 0 ? $uids : 0;
                    case 2:
                        if (count($uids))
                            $spread_uid_two = $this->dao->getColumn([['spread_uid', 'IN', $uids]], 'uid');
                        else
                            $spread_uid_two = [];
                        $where_data['uid'] = count($spread_uid_two) > 0 ? $spread_uid_two : 0;
                        break;
                    default:
                        if (count($uids)) {
                            if ($spread_uid_two = $this->dao->getColumn([['spread_uid', 'IN', $uids]], 'uid')) {
                                $uids = array_merge($uids, $spread_uid_two);
                                $uids = array_unique($uids);
                                $uids = array_merge($uids);
                            }
                        }
                        $where_data['uid'] = count($uids) > 0 ? $uids : 0;
                        break;
                }
            }
            if (isset($where['data']) && $where['data']) {
                $where_data['time'] = $where['data'];
            }
            if (isset($where['nickname']) && $where['nickname']) {
                $where_data['like'] = $where['nickname'];
            }
            $where_data['status'] = 1;
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getSairList($where_data, '*', $page, $limit);
        $count = $this->dao->count($where_data);
        return compact('list', 'count');
    }

    /**
     * 获取推广人统计
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSairCount(array $where)
    {
        $where_data = [];
        if (isset($where['uid'])) {
            if (isset($where['type'])) {
                $uids = $this->getColumn(['spread_uid' => $where['uid']], 'uid');
                switch ((int)$where['type']) {
                    case 1:
                        $where_data['uid'] = count($uids) > 0 ? $uids : 0;
                    case 2:
                        if (count($uids))
                            $spread_uid_two = $this->dao->getColumn([['spread_uid', 'IN', $uids]], 'uid');
                        else
                            $spread_uid_two = [];
                        $where_data['uid'] = count($spread_uid_two) > 0 ? $spread_uid_two : 0;
                        break;
                    default:
                        if (count($uids)) {
                            if ($spread_uid_two = $this->dao->getColumn([['spread_uid', 'IN', $uids]], 'uid')) {
                                $uids = array_merge($uids, $spread_uid_two);
                                $uids = array_unique($uids);
                                $uids = array_merge($uids);
                            }
                        }
                        $where_data['uid'] = count($uids) > 0 ? $uids : 0;
                        break;
                }
            }
            if (isset($where['data']) && $where['data']) {
                $where_data['time'] = $where['data'];
            }
            if (isset($where['nickname']) && $where['nickname']) {
                $where_data['like'] = $where['nickname'];
            }
            $where_data['status'] = 1;
        }
        return $this->dao->count($where_data);
    }

    /**
     * 写入用户信息
     * @param array $data
     * @return bool
     */
    public function create(array $data)
    {
        if (!$this->dao->save($data))
            throw new AdminException('写入失败');
        return true;
    }

    /**
     * 重置密码
     * @param $id
     * @param string $password
     * @return mixed
     */
    public function resetPwd(int $uid, string $password)
    {
        if (!$this->dao->update($uid, ['pwd' => $password]))
            throw new AdminException('密码重置失败');
        return true;
    }

    /**
     * 增加推广人数
     * @param int $uid
     * @param int $num
     * @return bool
     * @throws Exception
     */
    public function incSpreadCount(int $uid, int $num = 1)
    {
        if (!$this->dao->incField($uid, 'spread_count', $num))
            throw new Exception('增加推广人数失败');
        return true;
    }


    /**
     * 设置用户登录类型
     * @param int $uid
     * @param string $type
     * @return bool
     * @throws Exception
     */
    public function setLoginType(int $uid, string $type = 'h5')
    {
        if (!$this->dao->update($uid, ['login_type' => $type]))
            throw new Exception('设置登录类型失败');
        return true;
    }

    /**
     * 设置推广员
     * @param int $uid
     * @param int $is_promoter
     * @return bool
     * @throws Exception
     */
    public function setIsPromoter(int $uid, $is_promoter = 1)
    {
        if (!$this->dao->update($uid, ['is_promoter' => $is_promoter]))
            throw new Exception('设置推广员失败');
        return true;
    }

    /**
     * 设置用户分组
     * @param $uids
     * @param int $group_id
     */
    public function setUserGroup($uids, int $group_id)
    {
        return $this->dao->batchUpdate($uids, ['group_id' => $group_id], 'uid');
    }

    /**
     * 增加用户余额
     * @param int $uid
     * @param float $old_now_money
     * @param float $now_money
     * @return bool
     * @throws Exception
     */
    public function addNowMoney(int $uid, $old_now_money, $now_money)
    {
        if (!$this->dao->update($uid, ['now_money' => bcadd($old_now_money, $now_money, 2)]))
            throw new Exception('增加用户余额失败');
        return true;
    }

    /**
     * 减少用户余额
     * @param int $uid
     * @param float $old_now_money
     * @param float $now_money
     * @return bool
     * @throws Exception
     */
    public function cutNowMoney(int $uid, $old_now_money, $now_money)
    {
        if ($old_now_money > $now_money) {
            if (!$this->dao->update($uid, ['now_money' => bcsub($old_now_money, $now_money, 2)]))
                throw new Exception('减少用户余额失败');
        }
        return true;
    }

    /**
     * 增加用户佣金
     * @param int $uid
     * @param float $brokerage_price
     * @param float $price
     * @return bool
     * @throws Exception
     */
    public function addBrokeragePrice(int $uid, $brokerage_price, $price)
    {
        if (!$this->dao->update($uid, ['brokerage_price' => bcadd($brokerage_price, $price, 2)]))
            throw new Exception('增加用户佣金失败');
        return true;
    }

    /**
     * 减少用户佣金
     * @param int $uid
     * @param float $brokerage_price
     * @param float $price
     * @return bool
     * @throws Exception
     */
    public function cutBrokeragePrice(int $uid, $brokerage_price, $price)
    {
        if (!$this->dao->update($uid, ['brokerage_price' => bcsub($brokerage_price, $price, 2)]))
            throw new Exception('减少用户佣金失败');
        return true;
    }

    /**
     * 增加用户积分
     * @param int $uid
     * @param float $old_integral
     * @param float $integral
     * @return bool
     * @throws Exception
     */
    public function addIntegral(int $uid, $old_integral, $integral)
    {
        if (!$this->dao->update($uid, ['integral' => bcadd($old_integral, $integral, 2)]))
            throw new Exception('增加用户积分失败');
        return true;
    }

    /**
     * 减少用户积分
     * @param int $uid
     * @param float $old_integral
     * @param float $integral
     * @return bool
     * @throws Exception
     */
    public function cutIntegral(int $uid, $old_integral, $integral)
    {
        if (!$this->dao->update($uid, ['integral' => bcsub($old_integral, $integral, 2)]))
            throw new Exception('减少用户积分失败');
        return true;
    }

    /**
     * 增加用户经验
     * @param int $uid
     * @param float $old_exp
     * @param float $exp
     * @return bool
     * @throws Exception
     */
    public function addExp(int $uid, float $old_exp, float $exp)
    {
        if (!$this->dao->update($uid, ['exp' => bcadd($old_exp, $exp, 2)]))
            throw new Exception('增加用户经验失败');
        return true;
    }

    /**
     * 减少用户经验
     * @param int $uid
     * @param float $old_exp
     * @param float $exp
     * @return bool
     * @throws Exception
     */
    public function cutExp(int $uid, float $old_exp, float $exp)
    {
        if (!$this->dao->update($uid, ['exp' => bcsub($old_exp, $exp, 2)]))
            throw new Exception('减少用户经验失败');
        return true;
    }

    /**
     * 获取用户标签
     * @param $uid
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLablel(array $uids)
    {
        /** @var UserLabelRelationServices $services */
        $services = app()->make(UserLabelRelationServices::class);
        $userlabels = $services->getUserLabelList($uids);
        $data = [];
        foreach ($uids as $uid) {
            $labels = array_filter($userlabels, function ($item) use ($uid) {
                if ($item['uid'] == $uid) {
                    return true;
                }
            });
            $data[$uid] = implode(',', array_column($labels, 'label_name'));
        }
        return $data;
    }

    /**
     * 显示资源列表头部
     * @return array[]
     */
    public function typeHead()
    {
        //全部会员
        $all = $this->getCount([]);
        /** @var UserWechatuserServices $userWechatUser */
        $userWechatUser = app()->make(UserWechatuserServices::class);
        //小程序会员
        $routine = $userWechatUser->getCount([['w.user_type', '=', 'routine']]);
        //公众号会员
        $wechat = $userWechatUser->getCount([['w.user_type', '=', 'wechat']]);
        //H5会员
        $h5 = $userWechatUser->getCount(['w.openid' => '', 'u.user_type' => 'h5']);
        return [
            ['user_type' => '', 'name' => '全部会员', 'count' => $all],
            ['user_type' => 'routine', 'name' => '小程序会员', 'count' => $routine],
            ['user_type' => 'wechat', 'name' => '公众号会员', 'count' => $wechat],
            ['user_type' => 'h5', 'name' => 'H5会员', 'count' => $h5],
        ];
    }

    /**
     * 会员列表
     * @param array $where
     * @return array
     */
    public function index(array $where)
    {
        /** @var UserWechatuserServices $userWechatUser */
        $userWechatUser = app()->make(UserWechatuserServices::class);
        $fields = 'u.*,w.country,w.province,w.city,w.sex,w.unionid,w.openid,w.user_type as w_user_type,w.groupid,w.tagid_list,w.subscribe,w.subscribe_time';
        [$list, $count] = $userWechatUser->getWhereUserList($where, $fields);
        if ($list) {
            $uids = array_column($list, 'uid');
            $userlabel = $this->getUserLablel($uids);
            $userGroup = app()->make(UserGroupServices::class)->getUsersGroupName(array_unique(array_column($list, 'group_id')));
            $userExtract = app()->make(UserExtractServices::class)->getUsersSumList($uids);
            $levelName = app()->make(SystemUserLevelServices::class)->getUsersLevel(array_unique(array_column($list, 'level')));
            $userLevel = app()->make(UserLevelServices::class)->getUsersLevelInfo($uids);
            foreach ($list as &$item) {
                $item['status'] = ($item['status'] == 1) ? '正常' : '禁止';
                $item['birthday'] = $item['birthday'] ? date('Y-m-d', (int)$item['birthday']) : '';
                $item['extract_count_price'] = $userExtract[$item['uid']] ?? 0;//累计提现
                $item['spread_uid_nickname'] = $item['spread_uid'] ? ($item['spreadUser']['nickname'] ?? '') . '/' . $item['spread_uid'] : '无';
                //用户类型
                if ($item['openid'] != '' && $item['w_user_type'] == 'routine') {
                    $item['user_type'] = '小程序';
                } else if ($item['openid'] != '' && $item['w_user_type'] == 'wechat') {
                    $item['user_type'] = '公众号';
                } else if ($item['user_type'] == 'h5') {
                    $item['user_type'] = 'H5';
                } else $item['user_type'] = '其他';
                if ($item['sex'] == 1) {
                    $item['sex'] = '男';
                } else if ($item['sex'] == 2) {
                    $item['sex'] = '女';
                } else $item['sex'] = '保密';
                //等级名称
                $item['level'] = $levelName[$item['level']] ?? '无';
                //分组名称
                $item['group_id'] = $userGroup[$item['group_id']] ?? '无';
                //用户等级
                $item['vip_name'] = false;
                $levelinfo = $userLevel[$item['uid']] ?? null;
                if ($levelinfo) {
                    if ($levelinfo && ($levelinfo['is_forever'] || time() < $levelinfo['valid_time'])) {
                        $item['vip_name'] = $item['level'] != '无' ? $item['level'] : false;
                    }
                }
                $item['labels'] = $userlabel[$item['uid']] ?? '';
            }
        }

        return compact('count', 'list');
    }

    /**
     * 获取修改页面数据
     * @param int $id
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function edit(int $id)
    {
        $user = $this->getUserInfo($id);
        if (!$user)
            throw new AdminException('数据不存在');
        $f = array();
        $f[] = Form::input('uid', '用户编号', $user->getData('uid'))->disabled(true);
        $f[] = Form::input('real_name', '真实姓名', $user->getData('real_name'));
        $f[] = Form::input('phone', '手机号码', $user->getData('phone'));
        $f[] = Form::date('birthday', '生日', $user->getData('birthday') ? date('Y-m-d', $user->getData('birthday')) : '');
        $f[] = Form::input('card_id', '身份证号', $user->getData('card_id'));
        $f[] = Form::input('addres', '用户地址', $user->getData('addres'));
        $f[] = Form::textarea('mark', '用户备注', $user->getData('mark'));

        //查询高于当前会员的所有会员等级
        $grade = app()->make(UserLevelServices::class)->getUerLevelInfoByUid($id, 'grade');
        $systemLevelList = app()->make(SystemUserLevelServices::class)->getWhereLevelList(['grade', '>', $grade != '' ? $grade : 0], 'id,name');
        $f[] = Form::select('level', '会员等级', (string)$user->getData('level'))->setOptions(function () use ($systemLevelList) {
            $menus = [];
            foreach ($systemLevelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['name']];
            }
            return $menus;
        })->filterable(true);
        $systemGroupList = app()->make(UserGroupServices::class)->getGroupList();
        $f[] = Form::select('group_id', '用户分组', (string)$user->getData('group_id'))->setOptions(function () use ($systemGroupList) {
            $menus = [];
            foreach ($systemGroupList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['group_name']];
            }
            return $menus;
        })->filterable(true);
        $systemLabelList = app()->make(UserLabelServices::class)->getLabelList();
        $labels = app()->make(UserLabelRelationServices::class)->getUserLabels($user['uid']);
        $f[] = Form::select('label_id', '用户标签', $labels)->setOptions(function () use ($systemLabelList) {
            $menus = [];
            foreach ($systemLabelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['label_name']];
            }
            return $menus;
        })->filterable(true)->multiple(true);
        $f[] = Form::radio('is_promoter', '推广员', $user->getData('is_promoter'))->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $f[] = Form::radio('status', '状态', $user->getData('status'))->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '锁定']]);
        return create_form('编辑', $f, Url::buildUrl('/user/user/' . $id), 'PUT');
    }

    /**
     * 修改提交处理
     * @param $id
     * @return mixed
     */
    public function updateInfo(int $id, array $data)
    {
        $user = $this->getUserInfo($id);
        if (!$user) {
            throw new AdminException('数据不存在!');
        }
        $res1 = false;
        $res2 = false;
        $edit = array();
        /** @var UserBillServices $userBill */
        $userBill = app()->make(UserBillServices::class);
        if ($data['money_status'] && $data['money']) {//余额增加或者减少
            $bill_data = ['link_id' => $data['adminId'] ?? 0, 'number' => $data['money'], 'balance' => $user['now_money']];
            if ($data['money_status'] == 1) {//增加
                $edit['now_money'] = bcadd($user['now_money'], $data['money'], 2);
                $bill_data['title'] = '系统增加余额';
                $bill_data['mark'] = '系统增加了' . floatval($data['money']) . '余额';
                $res1 = $userBill->incomeNowMoney($user['uid'], 'system_add', $bill_data);
            } else if ($data['money_status'] == 2) {//减少
                $edit['now_money'] = bcsub($user['now_money'], $data['money'], 2);
                $bill_data['title'] = '系统减少余额';
                $bill_data['mark'] = '系统扣除了' . floatval($data['money']) . '余额';
                $res1 = $userBill->expendNowMoney($user['uid'], 'system_sub', $bill_data);
            }
        } else {
            $res1 = true;
        }
        if ($data['integration_status'] && $data['integration']) {//积分增加或者减少
            $integral_data = ['link_id' => $data['adminId'] ?? 0, 'number' => $data['integration'], 'balance' => $user['integral']];
            if ($data['integration_status'] == 1) {//增加
                $edit['integral'] = bcadd($user['integral'], $data['integration'], 2);
                $integral_data['title'] = '系统增加积分';
                $integral_data['mark'] = '系统增加了' . floatval($data['integration']) . '积分';
                $res2 = $userBill->incomeIntegral($user['uid'], 'system_add', $integral_data);
            } else if ($data['integration_status'] == 2) {//减少
                $edit['integral'] = bcsub($user['integral'], $data['integration'], 2);
                $integral_data['title'] = '系统减少积分';
                $integral_data['mark'] = '系统扣除了' . floatval($data['integration']) . '积分';
                $res2 = $userBill->expendIntegral($user['uid'], 'system_sub', $integral_data);
            }
        } else {
            $res2 = true;
        }
        //修改基本信息
        if (!isset($data['is_other']) || !$data['is_other']) {
            app()->make(UserLabelRelationServices::class)->setUserLable([$id], $data['label_id']);
            $edit['status'] = $data['status'];
            $edit['real_name'] = $data['real_name'];
            $edit['card_id'] = $data['card_id'];
            $edit['birthday'] = strtotime($data['birthday']);
            $edit['mark'] = $data['mark'];
            $edit['is_promoter'] = $data['is_promoter'];
            $edit['level'] = $data['level'];
            $edit['phone'] = $data['phone'];
            $edit['addres'] = $data['addres'];
            $edit['group_id'] = $data['group_id'];
            if ($data['level'] && !app()->make(UserLevelServices::class)->count(['uid' => $user['uid'], 'level_id' => $data['level'], 'is_del' => 0])) {
                app()->make(UserLevelServices::class)->setUserLevel((int)$user['uid'], (int)$data['level']);
            }
        }
        if ($edit) $res3 = $this->dao->update($id, $edit);

        else $res3 = true;
        if ($res1 && $res2 && $res3)
            return true;
        else throw new AdminException('修改失败');
    }

    /**
     * 编辑其他
     * @param $id
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function editOther($id)
    {
        $user = $this->getUserInfo($id);
        if (!$user) {
            throw new AdminException('数据不存在!');
        }
        $f = array();
        $f[] = Form::radio('money_status', '修改余额', 1)->options([['value' => 1, 'label' => '增加'], ['value' => 2, 'label' => '减少']]);
        $f[] = Form::number('money', '余额', 0)->min(0);
        $f[] = Form::radio('integration_status', '修改积分', 1)->options([['value' => 1, 'label' => '增加'], ['value' => 2, 'label' => '减少']]);
        $f[] = Form::number('integration', '积分', 0)->min(0);
        return create_form('修改其他', $f, Url::buildUrl('/user/update_other/' . $id), 'PUT');
    }

    /**
     * 设置会员分组
     * @param $id
     * @return mixed
     */
    public function setGroup($uids)
    {
        $userGroup = app()->make(UserGroupServices::class)->getGroupList();
        if (count($uids) == 1) {
            $user = $this->getUserInfo($uids[0], ['group_id']);
            $field[] = Form::select('group_id', '用户分组', (string)$user->getData('group_id'))->setOptions(function () use ($userGroup) {
                $menus = [];
                foreach ($userGroup as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['group_name']];
                }
                return $menus;
            })->filterable(true);
        } else {
            $field[] = Form::select('group_id', '用户分组')->setOptions(function () use ($userGroup) {
                $menus = [];
                foreach ($userGroup as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['group_name']];
                }
                return $menus;
            })->filterable(true);
        }
        $field[] = Form::hidden('uids', implode(',', $uids));
        return create_form('设置用户分组', $field, Url::buildUrl('/user/save_set_group'), 'PUT');
    }

    /**
     * 保存会员分组
     * @param $id
     * @return mixed
     */
    public function saveSetGroup($uids, int $group_id)
    {
        /** @var UserGroupServices $userGroup */
        $userGroup = app()->make(UserGroupServices::class);
        if (!$userGroup->getGroup($group_id)) {
            throw new AdminException('该分组不存在');
        }
        if (!$this->setUserGroup($uids, $group_id)) {
            throw new AdminException('设置分组失败或无改动');
        }
        return true;
    }

    /**
     * 设置用户标签
     * @param $uids
     * @return mixed
     */
    public function setLabel($uids)
    {
        $userLabel = app()->make(UserLabelServices::class)->getLabelList();
        if (count($uids) == 1) {
            $lids = app()->make(UserLabelRelationServices::class)->getUserLabels($uids[0]);
            $field[] = Form::select('label_id', '用户标签', $lids)->setOptions(function () use ($userLabel) {
                $menus = [];
                foreach ($userLabel as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['label_name']];
                }
                return $menus;
            })->filterable(true)->multiple(true);
        } else {
            $field[] = Form::select('label_id', '用户标签')->setOptions(function () use ($userLabel) {
                $menus = [];
                foreach ($userLabel as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['label_name']];
                }
                return $menus;
            })->filterable(true)->multiple(true);
        }
        $field[] = Form::hidden('uids', implode(',', $uids));
        return create_form('设置用户标签', $field, Url::buildUrl('/user/save_set_label'), 'PUT');
    }

    /**
     * 保存用户标签
     * @return mixed
     */
    public function saveSetLabel($uids, $lable_id)
    {
        foreach ($lable_id as $id) {
            if (!app()->make(UserLabelServices::class)->getLable((int)$id)) {
                throw new AdminException('有标签不存在或被删除');
            }
        }
        /** @var UserLabelRelationServices $services */
        $services = app()->make(UserLabelRelationServices::class);
        if (!$services->setUserLable($uids, $lable_id)) {
            throw new AdminException('设置标签失败');
        }
        return true;
    }

    /**
     * 赠送会员等级
     * @param int $uid
     * @return mixed
     * */
    public function giveLevel($id)
    {
        if (!$this->getUserInfo($id)) {
            throw new AdminException('用户不存在');
        }
        //查询高于当前会员的所有会员等级
        $grade = app()->make(UserLevelServices::class)->getUerLevelInfoByUid($id, 'grade');
        $systemLevelList = app()->make(SystemUserLevelServices::class)->getWhereLevelList(['grade', '>', $grade ?? 0], 'id,name');

        $field[] = Form::select('level_id', '会员等级')->setOptions(function () use ($systemLevelList) {
            $menus = [];
            foreach ($systemLevelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['name']];
            }
            return $menus;
        })->filterable(true);
        return create_form('赠送会员', $field, Url::buildUrl('/user/save_give_level/' . $id), 'PUT');
    }

    /**
     * 执行赠送会员等级
     * @param int $uid
     * @return mixed
     * */
    public function saveGiveLevel(int $id, int $level_id)
    {
        if (!$this->getUserInfo($id)) {
            throw new AdminException('用户不存在');
        }
        //查询当前选择的会员等级
        $systemLevel = app()->make(SystemUserLevelServices::class)->getLevel($level_id);
        if (!$systemLevel) throw new AdminException('您选择赠送的会员等级不存在！');
        //检查是否拥有此会员等级
        $level = app()->make(UserLevelServices::class)->getWhereLevel(['uid' => $id, 'level_id' => $level_id, 'is_del' => 0], 'valid_time,is_forever');
        if ($level) {
            if ($level['is_forever']) {
                throw new AdminException('此用户已有该会员等级，无法再次赠送');
            } else {
                if (time() < $level['valid_time'])
                    throw new AdminException('此用户已有该会员等级，无法再次赠送');
            }
        }
        //保存会员信息
        if (!app()->make(UserLevelServices::class)->setUserLevel($id, $level_id)) {
            throw new AdminException('赠送失败');
        }
        return true;
    }

    /**
     * 清除会员等级
     * @paran int $uid
     * @paran boolean
     * */
    public function cleanUpLevel($uid)
    {
        if (!$this->getUserInfo($uid))
            throw new AdminException('用户不存在');
        /** @var UserLevelServices $services */
        $services = app()->make(UserLevelServices::class);
        return $this->transaction(function () use ($uid, $services) {
            $res = $services->delUserLevel($uid);
            if (!$res && !$this->dao->update($uid, ['clean_time' => time(), 'level' => 0], 'uid'))
                throw new AdminException('清除失败');
            return true;
        });
    }

    /**
     * 用户详细信息
     * @param $uid
     */
    public function getUserDetailed(int $uid, $userIfno = [])
    {
        /** @var UserAddressServices $userAddress */
        $userAddress = app()->make(UserAddressServices::class);
        $field = 'real_name,phone,province,city,district,detail,post_code';
        $address = $userAddress->getUserDefaultAddress($uid, $field);
        if (!$address) {
            $address = $userAddress->getUserAddressList($uid, $field);
            $address = $address[0] ?? [];
        }
        $userInfo = $this->getUserInfo($uid);
        return [
            ['name' => '默认收货地址', 'value' => $address ? '收货人:' . $address['real_name'] . '邮编:' . $address['post_code'] . ' 收货人电话:' . $address['phone'] . ' 地址:' . $address['province'] . ' ' . $address['city'] . ' ' . $address['district'] . ' ' . $address['detail'] : ''],
            ['name' => '手机号码', 'value' => $userInfo['phone']],
            ['name' => '姓名', 'value' => ''],
            ['name' => '微信昵称', 'value' => $userInfo['nickname']],
            ['name' => '头像', 'value' => $userInfo['avatar']],
            ['name' => '邮箱', 'value' => ''],
            ['name' => '生日', 'value' => ''],
            ['name' => '积分', 'value' => $userInfo['integral']],
            ['name' => '上级推广人', 'value' => $userInfo['spread_uid'] ? $this->getUserInfo($userInfo['spread_uid'], ['nickname'])['nickname'] ?? '' : ''],
            ['name' => '账户余额', 'value' => $userInfo['now_money']],
            ['name' => '佣金总收入', 'value' => app()->make(UserBillServices::class)->getBrokerageSum($uid)],
            ['name' => '提现总金额', 'value' => app()->make(UserExtractServices::class)->getUserExtract($uid)],
        ];

    }

    /**
     * 获取用户详情里面的用户消费能力和用户余额积分等
     * @param $uid
     * @return array[]
     */
    public function getHeaderList(int $uid, $userInfo = [])
    {
        if (!$userInfo) {
            $userInfo = $this->getUserInfo($uid);
        }
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        return [
            [
                'title' => '余额',
                'value' => $userInfo['now_money'] ?? 0,
                'key' => '元',
            ],
            [
                'title' => '总计订单',
                'value' => $orderServices->count(['uid' => $uid]),
                'key' => '笔',
            ],
            [
                'title' => '总消费金额',
                'value' => $orderServices->together(['uid' => $uid], 'total_price'),
                'key' => '元',
            ],
            [
                'title' => '积分',
                'value' => $userInfo['integral'] ?? 0,
                'key' => '',
            ],
            [
                'title' => '本月订单',
                'value' => $orderServices->count(['uid' => $uid, 'time' => 'month']),
                'key' => '笔',
            ],
            [
                'title' => '本月消费金额',
                'value' => $orderServices->together(['uid' => $uid, 'time' => 'month'], 'total_price'),
                'key' => '元',
            ]
        ];
    }


    /**
     * 获取用户记录里的积分总数和签到总数和余额变动总数
     * @param $uid
     * @return array
     */
    public function getUserBillCountData($uid)
    {
        /** @var UserBillServices $userBill */
        $userBill = app()->make(UserBillServices::class);
        $integral_count = $userBill->getIntegralCount($uid);
        $sign_count = $userBill->getSignCount($uid);
        $balanceChang_count = $userBill->getBrokerageCount($uid);
        return [$integral_count, $sign_count, $balanceChang_count];
    }

    public function read(int $uid)
    {
        $userInfo = $this->getUserInfo($uid);
        if (!$userInfo) {
            throw new AdminException('数据不存在');
        }
        $info = [
            'uid' => $uid,
            'userinfo' => $this->getUserDetailed($uid, $userInfo),
            'headerList' => $this->getHeaderList($uid, $userInfo),
            'count' => $this->getUserBillCountData($uid),
            'ps_info' => $userInfo
        ];
        return $info;
    }

    /**
     * 获取单个用户信息
     * @param $id 用户id
     * @return mixed
     */
    public function oneUserInfo(int $id, string $type)
    {
        switch ($type) {
            case 'spread':
                return $this->getSpreadList($id);
                break;
            case 'order':
                /** @var StoreOrderServices $services */
                $services = app()->make(StoreOrderServices::class);
                return $services->getUserOrderList($id);
                break;
            case 'integral':
                /** @var UserBillServices $services */
                $services = app()->make(UserBillServices::class);
                return $services->getIntegralList($id, [], 'title,number,balance,mark,add_time');
                break;
            case 'sign':
                /** @var UserBillServices $services */
                $services = app()->make(UserBillServices::class);
                return $services->getSignList($id, [], 'title,number,mark,add_time');
                break;
            case 'coupon':
                /** @var StoreCouponUserServices $services */
                $services = app()->make(StoreCouponUserServices::class);
                return $services->getUserCouponList($id);
                break;
            case 'balance_change':
                /** @var UserBillServices $services */
                $services = app()->make(UserBillServices::class);
                return $services->getBrokerageList($id, [], 'title,type,number,balance,mark,pm,status,add_time');
                break;
            default:
                throw new AdminException('type参数错误');
        }
    }

    /**获取特定时间用户访问量
     * @param $time
     * @param $week
     * @return int
     */
    public function todayLastVisits($time, $week)
    {
        return $this->dao->todayLastVisit($time, $week);
    }

    /**获取特定时间新增用户
     * @param $time
     * @param $week
     * @return int
     */
    public function todayAddVisits($time, $week)
    {
        return $this->dao->todayAddVisit($time, $week);
    }

    /**
     * 用户图表
     */
    public function userChart()
    {
        $starday = date('Y-m-d', strtotime('-30 day'));
        $yesterday = date('Y-m-d');

        $user_list = $this->dao->userList($starday, $yesterday);
        $chartdata = [];
        $data = [];
        $chartdata['legend'] = ['用户数'];//分类
        $chartdata['yAxis']['maxnum'] = 0;//最大值数量
        $chartdata['xAxis'] = [date('m-d')];//X轴值
        $chartdata['series'] = [0];//分类1值
        if (!empty($user_list)) {
            foreach ($user_list as $k => $v) {
                $data['day'][] = $v['day'];
                $data['count'][] = $v['count'];
                if ($chartdata['yAxis']['maxnum'] < $v['count'])
                    $chartdata['yAxis']['maxnum'] = $v['count'];
            }
            $chartdata['xAxis'] = $data['day'];//X轴值
            $chartdata['series'] = $data['count'];//分类1值
        }
        $chartdata['bing_xdata'] = ['未消费用户', '消费一次用户', '留存客户', '回流客户'];
        $color = ['#5cadff', '#b37feb', '#19be6b', '#ff9900'];
        $pay[0] = $this->dao->count(['pay_count' => 0]);
        $pay[1] = $this->dao->count(['pay_count' => 1]);
        $pay[2] = $this->dao->userCount(1);
        $pay[3] = $this->dao->userCount(2);
        foreach ($pay as $key => $item) {
            $bing_data[] = ['name' => $chartdata['bing_xdata'][$key], 'value' => $pay[$key], 'itemStyle' => ['color' => $color[$key]]];
        }
        $chartdata['bing_data'] = $bing_data;
        return $chartdata;
    }

    /***********************************************/
    /************ 前端api services *****************/
    /***********************************************/

    /**
     * 用户信息
     * @param $info
     * @return mixed
     */
    public function userInfo($info)
    {
        /** @var UserBillServices $userBill */
        $userBill = app()->make(UserBillServices::class);
        $uid = (int)$info['uid'];
        $broken_time = intval(sys_config('extract_time'));
        $search_time = time() - 86400 * $broken_time;
        //改造时间
        $search_time = '1970/01/01' . ' - ' . date('Y/m/d H:i:s', $search_time);
        //可提现佣金
        //返佣 +
        $brokerage_commission = (string)$userBill->getUsersBokerageSum(['uid' => $uid, 'pm' => 1], $search_time);
        //退款退的佣金 -
        $refund_commission = (string)$userBill->getUsersBokerageSum(['uid' => $uid, 'pm' => 0], $search_time);
        $info['broken_commission'] = bcsub($brokerage_commission, $refund_commission, 2);
        if ($info['broken_commission'] < 0)
            $info['broken_commission'] = 0;
        $info['commissionCount'] = bcsub($info['brokerage_price'], $info['broken_commission'], 2);
        if ($info['commissionCount'] < 0)
            $info['commissionCount'] = 0;
        return $info;
    }

    /**
     * 个人中心
     * @param array $user
     */
    public function personalHome(array $user, $tokenData)
    {
        $userInfo = $user;
        $uid = (int)$user['uid'];
        /** @var StoreCouponUserServices $storeCoupon */
        $storeCoupon = app()->make(StoreCouponUserServices::class);
        /** @var UserBillServices $userBill */
        $userBill = app()->make(UserBillServices::class);
        /** @var UserExtractServices $userExtract */
        $userExtract = app()->make(UserExtractServices::class);
        /** @var StoreOrderServices $storeOrder */
        $storeOrder = app()->make(StoreOrderServices::class);
        /** @var UserLevelServices $userLevel */
        $userLevel = app()->make(UserLevelServices::class);
        /** @var StoreServiceServices $storeService */
        $storeService = app()->make(StoreServiceServices::class);
        /** @var SystemAttachmentServices $systemAttachment */
        $systemAttachment = app()->make(SystemAttachmentServices::class);
        /** @var WechatUserServices $wechatUser */
        $wechatUser = app()->make(WechatUserServices::class);
        $wechatUserInfo = $wechatUser->getOne(['uid' => $uid, 'user_type' => $tokenData['type']]);
        $user['is_complete'] = $wechatUserInfo['is_complete'] ?? 0;
        $user['couponCount'] = $storeCoupon->getUserValidCouponCount((int)$uid);
        $user['like'] = app()->make(StoreProductRelationServices::class)->getUserCollectCount($user['uid']);
        $user['orderStatusNum'] = $storeOrder->getOrderData($uid);
        $user['notice'] = 0;
        $user['recharge'] = $userBill->getRechargeSum($uid);//累计充值
        $user['orderStatusSum'] = $storeOrder->sum(['uid' => $uid, 'paid' => 1, 'is_del' => 0], 'pay_price');//累计消费
        $user['extractTotalPrice'] = $userExtract->getExtractSum(['uid' => $uid, 'status' => 1]);//累计提现
        $user['extractPrice'] = $user['brokerage_price'];//可提现
        $user['statu'] = (int)sys_config('store_brokerage_statu');
        if (!$user['is_promoter'] && $user['statu'] == 2) {
            $price = $storeOrder->sum(['paid' => 1, 'refund_status' => 0, 'uid' => $user['uid']], 'pay_price');
            $status = is_brokerage_statu($price);
            if ($status) {
                $this->dao->update($uid, ['is_promoter' => 1], 'uid');
                $user['is_promoter'] = 1;
            } else {
                $storeBrokeragePrice = sys_config('store_brokerage_price', 0);
                $user['promoter_price'] = bcsub((string)$storeBrokeragePrice, (string)$price, 2);
            }
        }
        /** @var UserBrokerageFrozenServices $frozenPrices */
        $frozenPrices = app()->make(UserBrokerageFrozenServices::class);
        $user['broken_commission'] = array_bc_sum($frozenPrices->getUserFrozenPrice($uid));
        if ($user['broken_commission'] < 0)
            $user['broken_commission'] = 0;
        $user['commissionCount'] = bcsub($user['brokerage_price'], $user['broken_commission'], 2);
        if ($user['commissionCount'] < 0)
            $user['commissionCount'] = 0;
        if (!sys_config('member_func_status'))
            $user['vip'] = false;
        else {
            $userLevel = $userLevel->getUerLevelInfoByUid($user['uid']);
            $user['vip'] = $userLevel ? true : false;
            if ($user['vip']) {
                $user['vip_id'] = $userLevel['id'] ?? 0;
                $user['vip_icon'] = $userLevel['icon'] ?? '';
                $user['vip_name'] = $userLevel['name'] ?? '';
            }
        }
        $user['yesterDay'] = $userBill->getUsersBokerageSum(['uid' => $uid, 'pm' => 1], 'yesterday');
        $user['recharge_switch'] = (int)sys_config('recharge_switch');//充值开关
        $user['adminid'] = $storeService->checkoutIsService($uid);
        if ($user['phone'] && $user['user_type'] != 'h5') {
            $user['switchUserInfo'][] = $userInfo;
            $h5UserInfo = $this->dao->getOne(['account' => $user['phone'], 'user_type' => 'h5']);
            if ($h5UserInfo) {
                $user['switchUserInfo'][] = $h5UserInfo;
            }
        } else if ($user['phone'] && $user['user_type'] == 'h5') {
            $wechatUserInfo = $this->getOne([['phone', '=', $user['phone']], ['user_type', '<>', 'h5']]);
            if ($wechatUserInfo) {
                $user['switchUserInfo'][] = $wechatUserInfo;
            }
            $user['switchUserInfo'][] = $userInfo;
        } else if (!$user['phone']) {
            $user['switchUserInfo'][] = $userInfo;
        }
        $user['broken_day'] = (int)sys_config('extract_time');//佣金冻结时间
        //队列生成分销海报
//        $routine_poster = $user['uid'] . '_' . $user['is_promoter'] . '_user_routine_poster_';
//        $wap_poster = $user['uid'] . '_' . $user['is_promoter'] . '_user_wap_poster_';
//        $routine_poster_count = $systemAttachment->count(['like_name' => $routine_poster]);
//        $wap_poster_count = $systemAttachment->count(['like_name' => $wap_poster]);
//        if ($routine_poster_count == 0 || $wap_poster_count == 0) {
//            Queue::instance()->do('spreadPoster')->job(PosterJob::class)->data($user, $isssl)->push();
//        }
        $user['balance_func_status'] = (int)sys_config('balance_func_status', 0);
        return $user;
    }

    /**
     * 用户资金统计
     * @param int $uid
     */
    public function balance(int $uid)
    {
        $userInfo = $this->getUserInfo($uid);
        if (!$userInfo) {
            throw new ValidateException('数据不存在');
        }
        /** @var UserBillServices $userBill */
        $userBill = app()->make(UserBillServices::class);
        /** @var StoreOrderServices $storeOrder */
        $storeOrder = app()->make(StoreOrderServices::class);
        $user['now_money'] = $userInfo['now_money'];//当前总资金
        $user['recharge'] = $userBill->getRechargeSum($uid);//累计充值
        $user['orderStatusSum'] = $storeOrder->sum(['uid' => $uid, 'paid' => 1, 'is_del' => 0], 'pay_price');//累计消费
        return $user;
    }

    /**
     * 用户修改信息
     * @param Request $request
     * @return mixed
     */
    public function eidtNickname(int $uid, array $data)
    {
        if (!$this->getUserInfo($uid)) {
            throw new ValidateException('用户不存在');
        }
        if (!$this->dao->update($uid, $data, 'uid')) {
            throw new ValidateException('修改失败');
        }
        return true;
    }

    /**
     * 获取推广人排行
     * @param $data 查询条件
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRankList(array $data)
    {
        switch ($data['type']) {
            case 'week':
                $startTime = strtotime('this week');
                $endTime = time();
                break;
            case 'month':
                $startTime = strtotime('last month');
                $endTime = time();
                break;
        }
        [$page, $limit] = $this->getPageValue();
        $field = 't0.uid,t0.spread_uid,count(t1.spread_uid) AS count,t0.add_time,t0.nickname,t0.avatar';
        return $this->dao->getAgentRankList([$startTime, $endTime], $field, $page, $limit);
    }

    /**
     * 静默绑定推广人
     * @param Request $request
     * @return mixed
     */
    public function spread(int $uid, int $spreadUid, $code)
    {
        $userInfo = $this->getUserInfo($uid);
        if (!$userInfo) {
            throw new ValidateException('数据不存在');
        }
        if ($code && !$spreadUid) {
            /** @var QrcodeServices $qrCode */
            $qrCode = app()->make(QrcodeServices::class);
            if ($info = $qrCode->getOne(['id' => $code, 'status' => 1])) {
                $spreadUid = $info['third_id'];
            }
        }
        $userSpreadUid = $this->dao->value(['uid' => $spreadUid], 'spread_uid');
        if (!$userInfo['spread_uid'] && $spreadUid != $uid && $userSpreadUid != $userInfo['uid']) {
            if (!$this->dao->update($uid, ['spread_uid' => $spreadUid, 'spread_time' => time()], 'uid')) {
                throw new ValidateException('绑定推广关系失败');
            }
            /** @var UserBillServices $userBill */
            $userBill = app()->make(UserBillServices::class);
            //邀请新用户增加经验
            $userBill->inviteUserIncExp((int)$spreadUid);
        }
        return true;
    }

    /**
     * 添加访问记录
     * @param Request $request
     * @return mixed
     */
    public function setVisit(array $data)
    {
        if (!$this->getUserInfo($data['uid'])) {
            throw new ValidateException('数据不存在');
        }
        $data['add_time'] = time();
        /** @var UserVisitServices $userVisit */
        $userVisit = app()->make(UserVisitServices::class);
        if ($userVisit->save($data)) {
            return true;
        } else {
            throw new ValidateException('添加访问记录失败');
        }
    }

    /**
     * 获取活动状态
     * @return mixed
     */
    public function activity()
    {
        /** @var StoreBargainServices $storeBragain */
        $storeBragain = app()->make(StoreBargainServices::class);
        /** @var StoreCombinationServices $storeCombinaion */
        $storeCombinaion = app()->make(StoreCombinationServices::class);
        /** @var StoreSeckillServices $storeSeckill */
        $storeSeckill = app()->make(StoreSeckillServices::class);
        $data['is_bargin'] = $storeBragain->validBargain() ? true : false;
        $data['is_pink'] = $storeCombinaion->validCombination() ? true : false;
        $data['is_seckill'] = $storeSeckill->getSeckillCount() ? true : false;
        return $data;
    }

    /**
     * 获取用户下级推广人
     * @param int $uid 当前用户
     * @param int $grade 等级  0  一级 1 二级
     * @param string $orderBy 排序
     * @param string $keyword
     * @return array|bool
     */
    public function getUserSpreadGrade(int $uid = 0, $grade = 0, $orderBy = '', $keyword = '')
    {
        $data = [
            'total' => 0,
            'totalLevel' => count($this->getUserSpredadUids($uid, 1)),
            'list' => []
        ];
        $data['count'] = count($this->getUserSpredadUids($uid));
        $user = $this->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('数据不存在');
        }
        $userStair = $this->dao->getColumn(['spread_uid' => $uid], 'uid');
        if (!count($userStair)) {
            return $data;
        }
        /** @var UserStoreOrderServices $userStoreOrder */
        $userStoreOrder = app()->make(UserStoreOrderServices::class);
        if ($grade == 0) {
            $data['total'] = count($userStair);
            $data['list'] = $userStoreOrder->getUserSpreadCountList($userStair, $orderBy, $keyword);
            return $data;
        }
        $userSecondary = $this->dao->getColumn([['spread_uid', 'IN', $userStair]], 'uid');
        $data['total'] = count($userStair);
        $data['totalLevel'] = count($userSecondary);
        $data['list'] = $userStoreOrder->getUserSpreadCountList($userSecondary, $orderBy, $keyword);
        return $data;
    }

    /**
     * 获取推广人uids
     * @param int $uid
     * @param bool $one
     * @return array
     */
    public function getUserSpredadUids(int $uid, int $type = 0)
    {
        $uids = $this->dao->getColumn(['spread_uid' => $uid], 'uid');
        if ($type === 2) {
            return $uids;
        }
        if ($uids) {
            $uidsTwo = $this->dao->getColumn([['spread_uid', 'in', $uids]], 'uid');
            if ($type === 1) {
                return $uidsTwo;
            }
            if ($uidsTwo) {
                $uids = array_merge($uids, $uidsTwo);
            }
        }
        return $uids;
    }


    /**
     * 检测用户是否是推广员
     * @param int $uid
     * @param $user
     * @return bool
     */
    public function checkUserPromoter(int $uid, $user = [])
    {
        if (!$user) {
            $user = $this->getUserInfo($uid);
        }
        if (!$user) {
            return false;
        }
        //分销是否开启
        if (!sys_config('brokerage_func_status')) {
            return false;
        }
        /** @var StoreOrderServices $storeOrder */
        $storeOrder = app()->make(StoreOrderServices::class);
        $sumPrice = $storeOrder->sum(['uid' => $uid, 'paid' => 1, 'is_del' => 0], 'pay_price');//累计消费
        $store_brokerage_statu = sys_config('store_brokerage_statu');
        $store_brokerage_price = sys_config('store_brokerage_price');
        if ($user['is_promoter'] || $store_brokerage_statu == 2 || ($store_brokerage_statu == 3 && $sumPrice > $store_brokerage_price)) {
            return true;
        }
        return false;
    }

}
