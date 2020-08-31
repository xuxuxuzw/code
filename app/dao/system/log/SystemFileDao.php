<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/7
 */

namespace app\dao\system\log;


use app\dao\BaseDao;
use app\model\system\log\SystemFile;

/**
 * 文件校验模型
 * Class SystemFileDao
 * @package app\dao\system\log
 */
class SystemFileDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return SystemFile::class;
    }

    /**
     * 获取全部
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAll()
    {
        return $this->getModel()->order('atime desc')->select()->toArray();
    }

}