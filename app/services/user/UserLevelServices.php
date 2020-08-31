<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */
declare (strict_types=1);

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserLevelDao;
use app\services\system\SystemUserLevelServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use think\exception\ValidateException;
use think\facade\Route as Url;

/**
 *
 * Class UserLevelServices
 * @package app\services\user
 * @method getDiscount(int $uid, string $field)
 */
class UserLevelServices extends BaseServices
{

    /**
     * UserLevelServices constructor.
     * @param UserLevelDao $dao
     */
    public function __construct(UserLevelDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 某些条件获取单个
     * @param array $where
     * @param string $field
     * @return mixed
     */
    public function getWhereLevel(array $where, string $field = '*')
    {
        return $this->getOne($where, $field);
    }

    /**
     * 获取一些用户等级信息
     * @param array $uids
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getUsersLevelInfo(array $uids)
    {
        return $this->dao->getColumn([['uid', 'in', $uids]], 'level_id,is_forever,valid_time', 'uid');
    }

    /**
     * 清除会员等级
     * @param $uids
     * @return \crmeb\basic\BaseModel|mixed
     */
    public function delUserLevel($uids)
    {
        if (is_array($uids))
            $re = $this->dao->batchUpdate($uids, ['is_del' => 1], 'uid');
        else
            $re = $this->dao->update($uids, ['is_del' => 1], 'uid');
        if (!$re)
            throw new AdminException('修改会员信息失败');
        return true;
    }

    /**
     * 根据用户uid 获取会员详细信息
     * @param int $uid
     * @param string $field
     */
    public function getUerLevelInfoByUid(int $uid, string $field = '')
    {
        $userLevelInfo = $this->dao->getUserLevel($uid);
        $data = [];
        if ($userLevelInfo) {
            $data = ['id' => $userLevelInfo['id'], 'level_id' => $userLevelInfo['level_id'], 'add_time' => $userLevelInfo['add_time']];
            $data['discount'] = $userLevelInfo['levelInfo']['discount'] ?? 0;
            $data['name'] = $userLevelInfo['levelInfo']['name'] ?? '';
            $data['money'] = $userLevelInfo['levelInfo']['money'] ?? 0;
            $data['icon'] = $userLevelInfo['levelInfo']['icon'] ?? '';
            $data['is_pay'] = $userLevelInfo['levelInfo']['is_pay'] ?? 0;
            $data['grade'] = $userLevelInfo['levelInfo']['grade'] ?? 0;
        }
        if ($field) return $data[$field] ?? '';
        return $data;
    }

    /**
     * 设置会员等级
     * @param $uid 用户uid
     * @param $level_id 等级id
     * @return UserLevel|bool|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setUserLevel(int $uid, int $level_id)
    {
        $vipinfo = app()->make(SystemUserLevelServices::class)->getLevel($level_id);
        if (!$vipinfo) {
            throw new AdminException('会员等级不存在');
        }
        /** @var  $user */
        $user = app()->make(UserServices::class);
        $userinfo = $user->getUserInfo($uid);
        $add_valid_time = (int)$vipinfo->valid_date * 86400;
        $uservipinfo = $this->getWhereLevel(['uid' => $uid, 'level_id' => $level_id, 'is_del' => 0]);
        //检查是否购买过
        if ($uservipinfo) {
            $stay = 0;
            //剩余时间
            if (time() < $uservipinfo->valid_time) $stay = $uservipinfo->valid_time - time();
            //如果购买过当前等级的会员过期了.从当前时间开始计算
            //过期时效: 剩余时间+当前会员等级时间+当前time
            $add_valid_time = $stay + $add_valid_time + time();
            $data['is_forever'] = $vipinfo->is_forever;
            $data['valid_time'] = $add_valid_time;
            if (!$this->dao->update($uservipinfo->id, $data))
                throw new AdminException('修改会员信息失败');
        } else {
            $data = [
                'is_forever' => $vipinfo->is_forever,
                'status' => 1,
                'is_del' => 0,
                'grade' => $vipinfo->grade,
                'uid' => $uid,
                'add_time' => time(),
                'level_id' => $level_id,
                'discount' => $vipinfo->discount,
            ];
            if ($data['is_forever'])
                $data['valid_time'] = 0;
            else
                $data['valid_time'] = $add_valid_time + time();
            $data['mark'] = '尊敬的用户' . $userinfo['nickname'] . '在' . date('Y-m-d H:i:s', time()) . '成为了' . $vipinfo['name'];
            if (!$this->dao->save($data))
                throw new AdminException('写入会员信息失败');
        }
        if (!$user->update($uid, ['level' => $level_id]))
            throw new AdminException('修改用户会员等级失败');
        return true;
    }

    /**
     * 会员列表
     * @param $where
     * @return mixed
     */
    public function getSytemList($where)
    {
        return app()->make(SystemUserLevelServices::class)->getLevelList($where);
    }

    /**
     * 获取添加修改需要表单数据
     * @param int $id
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function edit(int $id)
    {

        if ($id) {
            $vipinfo = app()->make(SystemUserLevelServices::class)->getlevel($id);
            if (!$vipinfo) {
                throw new AdminException('数据不存在');
            }
            $field[] = Form::hidden('id', $id);
            $msg = '编辑会员等级';
        } else {
            $msg = '添加会员等级';
        }
        $field[] = Form::input('name', '等级名称', isset($vipinfo) ? $vipinfo->name : '')->col(Form::col(24));
//        $field[] = Form::number('valid_date', '有效时间(天)', isset($vipinfo) ? $vipinfo->valid_date : 0)->min(0)->col(12);
        $field[] = Form::number('grade', '等级', isset($vipinfo) ? $vipinfo->grade : 0)->min(0)->col(8);
        $field[] = Form::number('discount', '享受折扣', isset($vipinfo) ? $vipinfo->discount : 100)->min(0)->col(8)->placeholder('输入折扣数100，代表原价，90代表9折');
        $field[] = Form::number('exp_num', '等级经验值', isset($vipinfo) ? $vipinfo->exp_num : 0)->min(0)->col(8);
        $field[] = Form::frameImageOne('icon', '图标', Url::buildUrl('admin/widget.images/index', array('fodder' => 'icon')), isset($vipinfo) ? $vipinfo->icon : '')->icon('ios-add')->width('60%')->height('435px');
        $field[] = Form::frameImageOne('image', '会员背景', Url::buildUrl('admin/widget.images/index', array('fodder' => 'image')), isset($vipinfo) ? $vipinfo->image : '')->icon('ios-add')->width('60%')->height('435px');
        $field[] = Form::radio('is_show', '是否显示', isset($vipinfo) ? $vipinfo->is_show : 0)->options([['label' => '显示', 'value' => 1], ['label' => '隐藏', 'value' => 0]])->col(24);
        $field[] = Form::textarea('explain', '等级说明', isset($vipinfo) ? $vipinfo->explain : '');
        return create_form($msg, $field, Url::buildUrl('/user/user_level'), 'POST');
    }

    /*
     * 会员等级添加或者修改
     * @param $id 修改的等级id
     * @return json
     * */
    public function save(int $id, array $data)
    {
        $systemUserLevel = app()->make(SystemUserLevelServices::class);
        if (!$id && $systemUserLevel->getWhereLevel(['is_del' => 0, 'grade' => $data['grade']])) throw new AdminException('已检测到您设置过的会员等级，此等级不可重复');
        //修改
        if ($id) {
            if (!$systemUserLevel->update($id, $data)) {
                throw new AdminException('修改失败');
            }
            return '修改成功';
        } else {
            //新增
            $data['add_time'] = time();
            if (!$systemUserLevel->save($data)) {
                throw new AdminException('添加失败');
            }
            return '添加成功';
        }
    }

    /**
     * 假删除
     * @param int $id
     * @return mixed
     */
    public function delLevel(int $id)
    {
        /** @var SystemUserLevelServices $systemUserLevel */
        $systemUserLevel = app()->make(SystemUserLevelServices::class);
        $level = $systemUserLevel->getWhereLevel(['id' => $id]);
        if ($level && $level['is_del'] != 1) {
            if (!$systemUserLevel->update($id, ['is_del' => 1]))
                throw new AdminException('删除失败');
        }
        return '删除成功';
    }

    /**
     * 设置是否显示
     * @param int $id
     * @param $is_show
     * @return mixed
     */
    public function setShow(int $id, int $is_show)
    {
        /** @var SystemUserLevelServices $systemUserLevel */
        $systemUserLevel = app()->make(SystemUserLevelServices::class);
        if (!$systemUserLevel->getWhereLevel(['id' => $id]))
            throw new AdminException('数据不存在');
        if ($systemUserLevel->update($id, ['is_show' => $is_show])) {
            return $is_show == 1 ? '显示成功' : '隐藏成功';
        } else {
            throw new AdminException($is_show == 1 ? '显示失败' : '隐藏失败');
        }
    }

    /**
     * 快速修改
     * @param int $id
     * @param $is_show
     * @return mixed
     */
    public function setValue(int $id, array $data)
    {
        /** @var SystemUserLevelServices $systemUserLevel */
        $systemUserLevel = app()->make(SystemUserLevelServices::class);
        if (!$systemUserLevel->getWhereLevel(['id' => $id]))
            throw new AdminException('数据不存在');
        if ($systemUserLevel->update($id, [$data['field'] => $data['value']])) {
            return true;
        } else {
            throw new AdminException('保存失败');
        }
    }

    /**
     * 检测用户会员升级
     * @param $uid
     * @return bool
     */
    public function detection(int $uid)
    {
        //商城会员是否开启
        if (!sys_config('member_func_status')) {
            return true;
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('没有此用户，无法检测升级会员');
        }
        $userLevelInfo = $this->getUerLevelInfoByUid($uid);

        if (empty($userLevelInfo)) {
            $level_id = 0;
        } else {
            $level_id = $userLevelInfo['level_id'];
        }
        /** @var SystemUserLevelServices $systemUserLevel */
        $systemUserLevel = app()->make(SystemUserLevelServices::class);
        $allLevel = $systemUserLevel->getLevelListAndGrade($level_id);
        if ($allLevel) {
            foreach ($allLevel as $vipinfo) {
                if ($user['exp'] >= $vipinfo['exp_num']) {
                    $uservip = $this->dao->getOne(['uid' => $uid, 'level_id' => $vipinfo['id']]);
                    if ($uservip) {
                        //降级在升级情况
                        if ($uservip->status == 0) {
                            $data['status'] = 1;
                            if (!$this->dao->update($vipinfo['id'], $data, 'id')) {
                                throw new ValidateException('检测升级失败');
                            }
                        }
                    } else {
                        $data = [
                            'is_forever' => $vipinfo['is_forever'],
                            'status' => 1,
                            'is_del' => 0,
                            'grade' => $vipinfo['grade'],
                            'uid' => $uid,
                            'add_time' => time(),
                            'level_id' => $vipinfo['id'],
                            'discount' => $vipinfo['discount'],
                        ];
                        $add_valid_time = (int)$vipinfo['valid_date'] * 86400;
                        if ($data['is_forever']) {
                            $data['valid_time'] = 0;
                        } else
                            $data['valid_time'] = $add_valid_time + time();
                        $data['mark'] = '尊敬的用户' . $user['nickname'] . '在' . date('Y-m-d H:i:s', time()) . '成为了' . $vipinfo['name'];
                        if (!$this->dao->save($data)) {
                            throw new ValidateException('检测升级失败');
                        }
                    }
                    if (!$userServices->update($uid, ['level' => $vipinfo['id']], 'uid')) {
                        throw new ValidateException('检测升级失败');
                    }
                }
            }
        }
        return true;
    }

    /**
     * 会员等级列表
     * @param int $uid
     */
    public function grade(int $uid)
    {
        //商城会员是否开启
        if (!sys_config('member_func_status')) {
            return [];
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('没有此用户，无法检测升级会员');
        }
        $userLevelInfo = $this->getUerLevelInfoByUid($uid);
        if (empty($userLevelInfo)) {
            $level_id = 0;
        } else {
            $level_id = $userLevelInfo['level_id'];
        }
        /** @var SystemUserLevelServices $systemUserLevel */
        $systemUserLevel = app()->make(SystemUserLevelServices::class);
        return $systemUserLevel->getLevelListAndGrade($level_id);
    }

    /**
     * 获取会员信息
     * @param int $uid
     * @return array[]
     */
    public function getUserLevelInfo(int $uid)
    {
        $data = ['level_info' => [], 'level_list' => []];
        //商城会员是否开启
        if (!sys_config('member_func_status')) {
            return $data;
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('没有此会员');
        }
        $data['level_info'] = $this->getUerLevelInfoByUid($uid);
        $data['level_info']['exp'] = $user['exp'] ?? 0;

        /** @var SystemUserLevelServices $systemUserLevel */
        $systemUserLevel = app()->make(SystemUserLevelServices::class);
        $data['level_list'] = $systemUserLevel->getColumn(['is_del' => 0, 'is_show' => 1], 'id,grade,name,exp_num');
        return $data;
    }

    /**
     * 经验列表
     * @param int $uid
     * @return array
     */
    public function expList(int $uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('没有此用户');
        }
        /** @var UserBillServices $userBill */
        $userBill = app()->make(UserBillServices::class);
        $data = $userBill->getExpList($uid, [], 'id,title,number,pm,add_time');
        $list = $data['list'] ?? [];
        return $list;
    }
}
