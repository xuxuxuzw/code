<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */
declare (strict_types=1);

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserLabelDao;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use think\facade\Route as Url;

/**
 *
 * Class UserLabelServices
 * @package app\services\user
 */
class UserLabelServices extends BaseServices
{

    /**
     * UserLabelServices constructor.
     * @param UserLabelDao $dao
     */
    public function __construct(UserLabelDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取某一本标签
     * @param $id
     * @return array|\think\Model|null
     */
    public function getLable($id)
    {
        return $this->dao->get($id);
    }

    /**
     * 获取所有用户标签
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLabelList()
    {
        return $this->dao->getList();
    }

    /**
     * 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList()
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($page, $limit);
        $count = $this->dao->count();
        return compact('list', 'count');
    }

    /**
     * 添加修改标签表单
     * @param int $id
     * @return mixed
     */
    public function add(int $id)
    {
        $label = $this->getLable($id);
        $field = array();
        if (!$label) {
            $title = '添加标签';
            $field[] = Form::input('label_name', '标签名称', '');
        } else {
            $title = '修改标签';
            $field[] = Form::hidden('id', $label->getData('id'));
            $field[] = Form::input('label_name', '标签名称', $label->getData('label_name'))->required('请填写标签名称');
        }
        return create_form($title, $field, Url::buildUrl('/user/user_label/save'), 'POST');
    }

    /**
     * 保存标签表单数据
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function save(int $id, array $data)
    {
        $levelName = $this->dao->getOne(['label_name' => $data['label_name']]);
        if ($id) {
            if (!$this->getLable($id)) {
                throw new AdminException('数据不存在');
            }
            if ($levelName && $id != $levelName['id']) {
                throw new AdminException('该标签已经存在');
            }
            if ($this->dao->update($id, $data)) {
                return true;
            } else {
                throw new AdminException('修改失败或者您没有修改什么！');
            }
        } else {
            unset($data['id']);
            if ($levelName) {
                throw new AdminException('该标签已经存在');
            }
            if ($this->dao->save($data)) {
                return true;
            } else {
                throw new AdminException('添加失败！');
            }
        }
    }

    /**
     * 删除
     * @param $id
     * @throws \Exception
     */
    public function delLabel(int $id)
    {
        if ($this->getLable($id)) {
            if (!$this->dao->delete($id)) {
                throw new AdminException('删除失败,请稍候再试!');
            }
        }
        return true;
    }
}