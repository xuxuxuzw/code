<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-02
 */

namespace app\services\product\product;


use app\dao\product\product\StoreDescriptionDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;

/**
 * Class StoreDescriptionService
 * @package app\services\product\product
 */
class StoreDescriptionServices extends BaseServices
{
    /**
     * StoreDescriptionServices constructor.
     * @param StoreDescriptionDao $dao
     */
    public function __construct(StoreDescriptionDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取商品详情
     * @param array $where
     * @return string
     */
    public function getDescription(array $where)
    {
        $info = $this->dao->getDescription($where);
        if ($info) return htmlspecialchars_decode($info->description);
        return '';
    }

    /**
     * 保存商品详情
     * @param int $id
     * @param string $description
     * @param int $type
     * @return bool
     */
    public function saveDescription(int $id, string $description, int $type = 0)
    {
        $description = htmlspecialchars($description);
        $info = $this->dao->count(['product_id' => $id, 'type' => $type]);
        if ($info) {
            $res = $this->dao->update($id, ['description' => $description], 'product_id');
        } else {
            $res = $this->dao->save(['product_id' => $id, 'description' => $description, 'type' => $type]);
        }
        if (!$res) throw new AdminException('商品详情保存失败！');
    }

}
