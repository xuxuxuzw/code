<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\services\message\service;


use app\dao\service\StoreServiceDao;
use app\services\BaseServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder;

/**
 * 客服
 * Class StoreServiceServices
 * @package app\services\message\service
 * @method getStoreServiceOrderNotice() 获取接受通知的客服
 */
class StoreServiceServices extends BaseServices
{
    /**
     * 创建form表单
     * @var Form
     */
    protected $builder;

    /**
     * 构造方法
     * StoreServiceServices constructor.
     * @param StoreServiceDao $dao
     */
    public function __construct(StoreServiceDao $dao, FormBuilder $builder)
    {
        $this->dao = $dao;
        $this->builder = $builder;
    }

    /**
     * 获取客服列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getServiceList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getServiceList($where, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 创建客服表单
     * @param array $formData
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function createServiceForm(array $formData = [])
    {
        if ($formData) {
            $field[] = $this->builder->frameImageOne('avatar', '客服头像', $this->url('admin/widget.images/index', ['fodder' => 'avatar'], true), $formData['avatar'] ?? '')->icon('ios-add')->width('60%')->height('435px');
//            $field[] = $this->builder->frameImageOne('avatar', '用户头像', $this->url('admin/widget.images/index', array('fodder' => 'avatar')))->icon('ios-add')->width('50%')->height('396px');
        } else {
            $field[] = $this->builder->frameImageOne('image', '商城用户', $this->url('admin/system.user/list', ['fodder' => 'image'], true))->icon('ios-add')->width('50%')->height('500px')->setProps(['srcKey' => 'image']);
            $field[] = $this->builder->hidden('uid', 0);
            $field[] = $this->builder->hidden('avatar', '');
        }
        $field[] = $this->builder->input('nickname', '客服名称', $formData['nickname'] ?? '')->col($this->builder->col(24));
        $field[] = $this->builder->input('phone', '手机号码', $formData['phone'] ?? '')->col($this->builder->col(24));
        $field[] = $this->builder->radio('customer', '统计管理', $formData['customer'] ?? 0)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $field[] = $this->builder->radio('notify', '订单通知', $formData['notify'] ?? 0)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $field[] = $this->builder->radio('status', '客服状态', $formData['status'] ?? 1)->options([['value' => 1, 'label' => '显示'], ['value' => 0, 'label' => '隐藏']]);
        return $field;
    }

    /**
     * 创建客服获取表单
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function create()
    {
        return create_form('添加客服', $this->createServiceForm(), $this->url('/app/wechat/kefu'), 'POST');
    }

    /**
     * 编辑获取表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function edit(int $id)
    {
        $serviceInfo = $this->dao->get($id);
        if (!$serviceInfo) {
            throw new AdminException('数据不存在!');
        }
        return create_form('编辑客服', $this->createServiceForm($serviceInfo->toArray()), $this->url('/app/wechat/kefu/' . $id), 'PUT');
    }

    /**
     * 获取某人的聊天记录用户列表
     * @param int $uid
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChatUser(int $uid)
    {
        /** @var StoreServiceLogServices $serviceLog */
        $serviceLog = app()->make(StoreServiceLogServices::class);
        /** @var UserServices $serviceUser */
        $serviceUser = app()->make(UserServices::class);
        $uids = $serviceLog->getChatUserIds($uid);
        if (!$uids) {
            return [];
        }
        return $serviceUser->getUserList(['uid' => $uids], 'nickname,uid,avatar as headimgurl');
    }

    /**
     * 检查用户是否是客服
     * @param int $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkoutIsService(int $uid)
    {
        return $this->dao->count(['uid' => $uid, 'status' => 1, 'customer' => 1]) ? true : false;
    }
}
