<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/1
 */

namespace app\services\system\config;


use app\dao\system\config\SystemConfigTabDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;

/**
 * 系统配置分类
 * Class SystemConfigTabServices
 * @package app\services\system\config
 * @method save(array $data) 写入数据
 * @method update($id, array $data, ?string $key = null) 修改数据
 * @method delete($id, ?string $key = null) 删除数据
 */
class SystemConfigTabServices extends BaseServices
{
    /**
     * SystemConfigTabServices constructor.
     * @param SystemConfigTabDao $dao
     */
    public function __construct(SystemConfigTabDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取配置分类
     * @param int $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfigTabAll(array $where)
    {
        $tabList = $this->dao->getConfigTabAll($where);
        if (isset($where['type']) && $where['type'] > -1) {
            $tabList = sort_list_tier($tabList);
        }
        return $tabList;
    }

    /**
     * 子级下的所有配置分类
     * @param int $pid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfigChildrenTabAll(int $pid)
    {
        $tabList = $this->getConfigTabAll(['status' => 1, 'pid' => $pid]);
        $configTab = [];
        foreach ($tabList as $k => $v) {
            if (!$v['info']) {
                $configTab[$k]['id'] = $v['id'];
                $configTab[$k]['label'] = $v['title'];
                $configTab[$k]['icon'] = $v['icon'];
                $configTab[$k]['type'] = $v['type'];
                $configTab[$k]['pid'] = $v['pid'];
            }
        }
        return $configTab;
    }

    /**
     * 系统设置头部分类读取
     * @param int $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfigTab(int $type)
    {
        $configTab = $this->getConfigTabAll(['type' => $type]);
        $pid = [];
        if ($type == -1) {
            $pid = array_column($configTab, 'pid');
        }
        foreach ($configTab as &$item) {
            if (!$item['info']) {
                $item['value'] = $item['id'];
                $item['label'] = $type == -1 ? $item['html'] . $item['title'] : $item['title'];
                $item['icon'] = $item['icon'];
                $item['type'] = $item['type'];
                if (in_array($item['id'], $pid)) {
                    $item['disabled'] = true;
                }
                $item['children'] = $this->getConfigChildrenTabAll($item['value']);
            }
        }
        return $configTab;
    }

    /**
     * 获取配置分类列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfgTabList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getConfgTabList($where, $page, $limit);
        $count = $this->dao->count($where);
        $menusValue = [];
        foreach ($list as $item) {
            $menusValue[] = $item->getData();
        }
        $list = get_tree_children($menusValue);
        return compact('list', 'count');
    }

    /**
     * 获取配置分类选择下拉树
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSelectForm()
    {
        $menuList = $this->dao->getConfigTabAll([], ['id', 'pid', 'title']);
        $list = sort_list_tier($menuList, 0, 'pid', 'id');
        $menus = [['value' => 0, 'label' => '顶级按钮']];
        foreach ($list as $menu) {
            $menus[] = ['value' => $menu['id'], 'label' => $menu['html'] . $menu['title']];
        }
        return $menus;
    }

    /**
     * 创建form表单
     * @param array $formData
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createConfigTabForm(array $formData = [])
    {
        $form[] = Form::select('pid', '父级分类', isset($formData['pid']) ? (string)$formData['pid'] : '')->setOptions($this->getSelectForm())->filterable(true);
        $form[] = Form::input('title', '分类昵称', $formData['title'] ?? '');
        $form[] = Form::input('eng_title', '分类字段英文', $formData['eng_title'] ?? '');
        $form[] = Form::frameInputOne('icon', '图标', $this->url('admin/widget.widgets/icon', ['fodder' => 'icon'], true), $formData['icon'] ?? '')->icon('ios-ionic')->height('435px');
        $form[] = Form::radio('type', '类型', $formData['type'] ?? 0)->options([
            ['value' => 0, 'label' => '系统'],
            ['value' => 3, 'label' => '其它']
        ]);
        $form[] = Form::radio('status', '状态', $formData['status'] ?? 1)->options([['value' => 1, 'label' => '显示'], ['value' => 2, 'label' => '隐藏']]);
        $form[] = Form::number('sort', '排序', $formData['sort'] ?? 0);
        return $form;
    }

    /**
     * 添加配置分类表单
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function createForm()
    {
        return create_form('添加配置分类', $this->createConfigTabForm(), $this->url('/setting/config_class'));
    }

    /**
     * 修改配置分类表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function updateForm(int $id)
    {
        $configTabInfo = $this->dao->get($id);
        if (!$configTabInfo) {
            throw new AdminException('没有查到数据,无法修改!');
        }
        return create_form('编辑配置分类', $this->createConfigTabForm($configTabInfo->toArray()), $this->url('/setting/config_class/' . $id), 'PUT');
    }
}