<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\dao\shipping;

use app\dao\BaseDao;
use app\model\other\Express;

/**
 * 物流信息
 * Class ExpressDao
 * @package app\dao\other
 */
class ExpressDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return Express::class;
    }

    /**
     * 获取物流列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getExpressList(array $where, string $field,int $page, int $limit)
    {
        return $this->search($where)->field($field)->order('sort DESC,id DESC')->page($page, $limit)->select()->toArray();
    }

    /**
     * 指定的条件获取物流信息以数组返回
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getExpress(array $where, string $field, string $key)
    {
        return $this->search($where)->order('id DESC')->column($field, $key);
    }

}