<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */
declare (strict_types=1);

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserLabelRelationDao;
use crmeb\exceptions\AdminException;

/**
 *
 * Class UserLabelRelationServices
 * @package app\services\user
 * @method getColumn(array $where, string $field, string $key = '') 获取某个字段数组
 * @method saveAll(array $data) 批量保存数据
 */
class UserLabelRelationServices extends BaseServices
{

    /**
     * UserLabelRelationServices constructor.
     * @param UserLabelRelationDao $dao
     */
    public function __construct(UserLabelRelationDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取某个用户标签ids
     * @param int $uid
     * @return array
     */
    public function getUserLabels(int $uid)
    {
        return $this->dao->getColumn(['uid' => $uid], 'label_id', '');
    }

    /**
     * 用户设置标签
     * @param int $uid
     * @param array $labels
     */
    public function setUserLable($uids, array $labels)
    {
        if (!is_array($uids)) {
            $uids = [$uids];
        }
        $re = $this->dao->whereDelete([
            ['uid', 'in', $uids],
            ['label_id', 'in', $labels],
        ]);
        if ($re === false) {
            throw new AdminException('清空用户标签失败');
        }
        $data = [];
        foreach ($uids as $uid) {
            foreach ($labels as $label) {
                $data[] = ['uid' => $uid, 'label_id' => $label];
            }
        }
        if ($data) {
            if (!$this->dao->saveAll($data))
                throw new AdminException('设置标签失败');
        }
        return true;
    }

    /**
     * 获取用户标签
     * @param array $uids
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLabelList(array $uids)
    {
        return $this->dao->getLabelList($uids);
    }
}
