<?php
/**
 * @author: zhypy<2146832@qq.com>
 * @day: 2020/7/4
 */
declare (strict_types=1);

namespace app\services\system;

use app\services\BaseServices;
use app\dao\system\SystemUserLevelDao;

/**
 *
 * Class SystemUserLevelServices
 * @package app\services\system
 */
class SystemUserLevelServices extends BaseServices
{

    /**
     * SystemUserLevelServices constructor.
     * @param SystemUserLevelDao $dao
     */
    public function __construct(SystemUserLevelDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 单个等级
     * @param int $id
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLevel(int $id, string $field = '*')
    {
        return $this->dao->getOne(['id' => $id, 'is_del' => 0], $field);
    }

    /**
     * 获取某条件等级
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWhereLevel(array $where, string $field = '*')
    {
        return $this->dao->getOne($where, $field);
    }

    /**
     * 获取所有等级列表
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLevelList(array $where, string $field = '*')
    {
        $where_data = [];
        if (isset($where['is_show']) && $where['is_show'] !== '') $where_data[] = ['is_show', '=', $where['is_show']];
        if (isset($where['title']) && $where['title']) $where_data[] = ['name', 'LIKE', "%$where[title]%"];
        $where_data[] = ['is_del', '=', '0'];
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where_data, $field ?? '*', $page, $limit);
        $count = $this->dao->getCount($where_data);
        return compact('list', 'count');
    }

    /**
     * 获取条件的会员等级列表
     * @param array $where
     * @param string $field
     */
    public function getWhereLevelList(array $where, string $field = '*')
    {
        return $this->dao->getList([['is_show', '=', 1], ['is_del', '=', 0], $where], $field ?? '*');
    }

    /**
     * 获取一些用户等级名称
     * @param $ids
     * @return array
     */
    public function getUsersLevel($ids)
    {
        return $this->dao->getColumn([['id', 'IN', $ids]], 'name', 'id');
    }

    /**
     * 获取会员等级列表
     * @param int $leval_id
     * @return array
     */
    public function getLevelListAndGrade(int $leval_id = 0, string $field = 'name,discount,image,icon,explain,id,grade,is_forever,valid_date,exp_num')
    {
        $list = $this->dao->getList(['is_del' => 0, 'is_show' => 1], $field);
        if ($list) {
            $listNew = array_combine(array_column($list, 'id'), $list);
            $grade = $listNew[$leval_id]['grade'] ?? 0;
            foreach ($list as &$item) {
                if ($grade < $item['grade'])
                    $item['is_clear'] = true;
                else
                    $item['is_clear'] = false;
                $item['task_list'] = [];
            }
        }
        return $list;
    }
}
