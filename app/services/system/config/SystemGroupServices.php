<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\services\system\config;

use app\dao\system\config\SystemGroupDao;
use app\services\BaseServices;

/**
 * 组合数据
 * Class SystemGroupServices
 * @package app\services\system\config
 * @method getConfigNameId(string $configName) 获取配置id
 * @method save(array $data) 新增数据
 * @method get(int $id, ?array $field = []) 获取一条数据
 * @method count(array $where = []): int 根据条件获取条数
 * @method update($id, array $data, ?string $key = null) 修改数据
 * @method delete($id, ?string $key = null) 删除数据
 * @method value(array $where, ?string $field = '') 获取某个值
 */
class SystemGroupServices extends BaseServices
{

    /**
     * SystemGroupServices constructor.
     * @param SystemGroupDao $dao
     */
    public function __construct(SystemGroupDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取组合数据列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGroupList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getGroupList($where, $page, $limit);
        $count = $this->dao->count($where);
        foreach ($list as $key => $value) {
            $list[$key]['typelist'] = $value['fields'];
            unset($list[$key]['fields']);
        }
        return compact('list', 'count');
    }

    /**
     * 获取组合数据tab下的header头部
     * @param int $id
     * @return array
     */
    public function getGroupDataTabHeader(int $id)
    {
        $data = $this->getValueFields($id);
        $header = [];
        foreach ($data as $key => $item) {
            if ($item['type'] == 'upload' || $item['type'] == 'uploads') {
                $header[$key]['key'] = $item['title'];
                $header[$key]['minWidth'] = 60;
                $header[$key]['type'] = 'img';
            } elseif ($item['title'] == 'url' || $item['title'] == 'wap_url' || $item['title'] == 'link' || $item['title'] == 'wap_link') {
                $header[$key]['key'] = $item['title'];
                $header[$key]['minWidth'] = 200;
            } else {
                $header[$key]['key'] = $item['title'];
                $header[$key]['minWidth'] = 100;
            }
            $header[$key]['title'] = $item['name'];
        }
        array_unshift($header, ['key' => 'id', 'title' => '编号', 'minWidth' => 35]);
        array_push($header, ['slot' => 'status', 'title' => '是否可用', 'minWidth' => 80], ['slot' => 'action', 'fixed' => 'right', 'title' => '操作', 'minWidth' => 120]);
        return compact('header');
    }

    /**
     * 获取组合数据fields字段
     * @param int $id
     * @return array|mixed
     */
    public function getValueFields(int $id)
    {
        return json_decode($this->dao->value(['id' => $id], 'fields'), true) ?: [];
    }

}