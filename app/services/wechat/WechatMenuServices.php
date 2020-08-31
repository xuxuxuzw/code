<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/7
 */

namespace app\services\wechat;


use app\dao\wechat\WechatMenuDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\WechatService;

/**
 * 微信菜单
 * Class WechatMenuServices
 * @package app\services\wechat
 */
class WechatMenuServices extends BaseServices
{
    /**
     * 构造方法
     * WechatMenuServices constructor.
     * @param WechatMenuDao $dao
     */
    public function __construct(WechatMenuDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取微信菜单
     * @return array|mixed
     */
    public function getWechatMenu()
    {
        $menus = $this->dao->value(['key' => 'wechat_menus'], 'result');
        return $menus ? json_decode($menus, true) : [];
    }

    /**
     * 保存微信菜单
     * @param array $buttons
     * @return bool
     */
    public function saveMenu(array $buttons)
    {
        try {
            WechatService::menuService()->add($buttons);
            if ($this->dao->count(['key' => 'wechat_menus', 'result' => json_encode($buttons)])) {
                $this->dao->update('wechat_menus', ['result' => json_encode($buttons), 'add_time' => time()], 'key');
            } else {
                $this->dao->save(['key' => 'wechat_menus', 'result' => json_encode($buttons), 'add_time' => time()]);
            }
            return true;
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
    }
}