<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-02
 */

namespace app\services\product\sku;


use app\dao\product\sku\StoreProductAttrResultDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;

/**
 * Class StoreProductAttrResultService
 * @package app\services\product\sku
 * @method value(array $where, string $field)
 */
class StoreProductAttrResultServices extends BaseServices
{
    /**
     * StoreProductAttrResultServices constructor.
     * @param StoreProductAttrResultDao $dao
     */
    public function __construct(StoreProductAttrResultDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取属性规格
     * @param array $where
     * @return mixed
     */
    public function getResult(array $where)
    {
        return json_decode($this->dao->value($where, 'result'), true);
    }

    /**
     * 删除属性
     * @param int $id
     * @param int $type
     * @return bool
     */
    public function del(int $id, int $type)
    {
        return $this->dao->del($id, $type);
    }

    /**
     * 修改属性
     * @param array $data
     * @param int $id
     * @param int $type
     */
    public function setResult(array $data, int $id, int $type)
    {
        $res = $this->dao->save(['product_id' => $id, 'result' => json_encode($data), 'change_time' => time(), 'type' => $type]);
        if (!$res) throw new AdminException('规格保存失败');
    }
}
