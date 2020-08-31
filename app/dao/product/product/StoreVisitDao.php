<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-02
 */

namespace app\dao\product\product;

use app\dao\BaseDao;
use app\model\product\product\StoreVisit;

/**
 * Class StoreVisitDao
 * @package app\dao\product\product
 */
class StoreVisitDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreVisit::class;
    }
}
