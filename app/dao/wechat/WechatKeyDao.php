<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/4
 */
declare (strict_types=1);

namespace app\dao\wechat;

use think\model;
use app\dao\BaseDao;
use app\model\wechat\WechatKey;

/**
 *
 * Class UserWechatUserDao
 * @package app\dao\user
 */
class WechatKeyDao extends BaseDao
{
    protected function setModel(): string
    {
        return WechatKey::class;
    }

    /**
     * 搜索器
     * @param array $where
     * @return \crmeb\basic\BaseModel|mixed|Model
     */
    public function search(array $where = [])
    {
        return parent::search($where)->when(isset($where['id']), function ($query) use ($where) {
            $query->where('id', $where['id']);
        });
    }

}
