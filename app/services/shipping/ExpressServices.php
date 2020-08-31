<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\services\shipping;


use app\dao\shipping\ExpressDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;

/**
 * 物流数据
 * Class ExpressServices
 * @package app\services\shipping
 * @method save(array $data) 保存数据
 * @method get(int $id, ?array $field = []) 获取数据
 * @method delete(int $id, ?string $key = null) 删除数据
 * @method update($id, array $data, ?string $key = null) 修改数据
 */
class ExpressServices extends BaseServices
{
    /**
     * 构造方法
     * ExpressServices constructor.
     * @param ExpressDao $dao
     */
    public function __construct(ExpressDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取物流信息
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getExpressList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getExpressList($where, '*',$page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 物流表单
     * @param array $formData
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function createExpressForm(array $formData = [])
    {
        $field[] = Form::input('name', '公司名称', $formData['name'] ?? '');
        $field[] = Form::input('code', '编码', $formData['code'] ?? '');
        $field[] = Form::number('sort', '排序', $formData['sort'] ?? 0);
        $field[] = Form::radio('is_show', '是否启用', $formData['is_show'] ?? 1)->options([['value' => 0, 'label' => '隐藏'], ['value' => 1, 'label' => '启用']]);
        return $field;
    }

    /**
     * 创建物流信息表单获取
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function createForm()
    {
        return create_form('添加物流公司', $this->createExpressForm(), $this->url('/freight/express'));
    }

    /**
     * 修改物流信息表单获取
     * @param int $id
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function updateForm(int $id)
    {
        $express = $this->dao->get($id);
        if (!$express) {
            throw new AdminException('查询数据失败,无法修改');
        }
        return create_form('编辑物流公司', $this->createExpressForm($express->toArray()), $this->url('/freight/express/' . $id), 'PUT');
    }

    /**
     * 获取物流信息组合成新的数组返回
     * @param array $where
     * @return array
     */
    public function express(array $where, string $k = 'id')
    {
        $list = $this->dao->getExpress($where, 'name', 'id');
        $data = [];
        foreach ($list as $key => $value) {
            $data[] = [$k => $key, 'value' => $value];
        }
        return $data;
    }

    /**
     * 获取物流信息组合成新的数组返回
     * @param array $where
     * @return array
     */
    public function expressSelectForm(array $where)
    {
        $list = $this->dao->getExpress($where, 'name', 'id');
        $data = [];
        foreach ($list as $key => $value) {
            $data[] = ['label' => $value, 'value' => $key];
        }
        return $data;
    }

    public function expressList()
    {
        return $this->dao->getExpressList(['is_show'=>1],'id,name',0,0);
    }
}