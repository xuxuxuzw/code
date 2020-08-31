<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/8
 */
declare (strict_types=1);

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserAuthDao;
use crmeb\exceptions\AuthException;
use crmeb\services\CacheService;
use crmeb\utils\JwtAuth;

/**
 *
 * Class UserAuthServices
 * @package app\services\user
 */
class UserAuthServices extends BaseServices
{

    /**
     * UserAuthServices constructor.
     * @param UserAuthDao $dao
     */
    public function __construct(UserAuthDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取授权信息
     * @param $token
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException\
     */
    public function parseToken($token): array
    {
        if (!$token || !$tokenData = CacheService::getTokenBucket($token))
            throw new AuthException('请登录', 410000);

        if (!is_array($tokenData) || empty($tokenData) || !isset($tokenData['uid'])) {
            throw new AuthException('请登录', 410000);
        }

        /** @var JwtAuth $jwtAuth */
        $jwtAuth = app()->make(JwtAuth::class);
        //设置解析token
        [$id, $type] = $jwtAuth->parseToken($token);


        try {
            $jwtAuth->verifyToken();
        } catch (\Throwable $e) {
            CacheService::clearToken($token);
            throw new AuthException('登录已过期,请重新登录', 410001);
        }

        $user = $this->dao->get($id);

        if (!$user || $user->uid != $tokenData['uid']) {
            CacheService::clearToken($token);
            throw new AuthException('登录状态有误,请重新登录', 410002);
        }
        $tokenData['type'] = $type;
        return compact('user', 'tokenData');
    }

}
