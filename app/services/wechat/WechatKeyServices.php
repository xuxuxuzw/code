<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/7
 */

namespace app\services\wechat;


use app\dao\wechat\WechatKeyDao;
use app\services\BaseServices;

/**
 * 微信菜单
 * Class WechatMenuServices
 * @package app\services\wechat
 * @method delete($id, ?string $key = null)  删除
 * @method getOne(array $where)  获取一条数据
 * @method count(array $where)  读取数据条数
 * @method saveAll(array $where)  插入数据
 * @method getColumn($where,$key)  获取某个字段数组
 */
class WechatKeyServices extends BaseServices
{
    /**
     * 构造方法
     * WechatMenuServices constructor.
     * @param WechatKeyDao $dao
     */
    public function __construct(WechatKeyDao $dao)
    {
        $this->dao = $dao;
    }

}