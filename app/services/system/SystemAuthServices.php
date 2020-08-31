<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/7
 */

namespace app\services\system;


use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\HttpService;

/**
 * 商业授权
 * Class SystemAuthServices
 * @package app\services\system
 */
class SystemAuthServices extends BaseServices
{

    /**
     * 申请授权
     * @param array $data
     * @return bool
     */
    public function authApply(array $data)
    {
        $res = HttpService::postRequest('http://authorize.crmeb.net/api/auth_apply', $data);
        if ($res === false) {
            throw new AdminException('申请失败,服务器没有响应!');
        }
        $res = json_decode($res, true);
        if (isset($res['status'])) {
            if ($res['status'] == 400) {
                throw new AdminException($res['msg'] ?? "申请失败");
            } else {
                return true;
            }
        }
    }
}