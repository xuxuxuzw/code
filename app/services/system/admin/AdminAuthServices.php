<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/2
 */

namespace app\services\system\admin;


use app\dao\system\admin\AdminAuthDao;
use app\services\BaseServices;
use crmeb\exceptions\AuthException;
use crmeb\services\CacheService;
use crmeb\utils\ApiErrorCode;
use crmeb\utils\JwtAuth;
use Firebase\JWT\ExpiredException;

/**
 * admin授权service
 * Class AdminAuthServices
 * @package app\services\system\admin
 */
class AdminAuthServices extends BaseServices
{
    /**
     * 构造方法
     * AdminAuthServices constructor.
     * @param AdminAuthDao $dao
     */
    public function __construct(AdminAuthDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取Admin授权信息
     * @param $token
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function parseToken($token): array
    {
        /** @var CacheService $cacheService */
        $cacheService = app()->make(CacheService::class);
        //检测token是否过期
        if (!$token || !$cacheService->hasToken($token) || !($cacheToken = $cacheService->getTokenBucket($token))) {
            throw new AuthException(ApiErrorCode::ERR_LOGIN);
        }
        //是否超出有效次数
        if (isset($cacheToken['invalidNum']) && $cacheToken['invalidNum'] >= 3) {
            $cacheService->clearToken($token);
            throw new AuthException(ApiErrorCode::ERR_LOGIN_INVALID);
        }

        /** @var JwtAuth $jwtAuth */
        $jwtAuth = app()->make(JwtAuth::class);
        //设置解析token
        [$id, $type] = $jwtAuth->parseToken($token);

        //验证token
        try {
            $jwtAuth->verifyToken();
            CacheService::setTokenBucket($cacheToken['token'], $cacheToken, $cacheToken['exp']);
        } catch (ExpiredException $e) {
            $cacheToken['invalidNum'] = isset($cacheToken['invalidNum']) ? $cacheToken['invalidNum']++ : 1;
            $cacheService->setTokenBucket($cacheToken['token'], $cacheToken, $cacheToken['exp']);
        } catch (\Throwable $e) {
            $cacheService->clearToken($token);
            throw new AuthException(ApiErrorCode::ERR_LOGIN_INVALID);
        }

        //获取管理员信息
        $adminInfo = $this->dao->get($id);
        if (!$adminInfo || !$adminInfo->id) {
            $cacheService->clearToken($token);
            throw new AuthException(ApiErrorCode::ERR_LOGIN_STATUS);
        }

        $adminInfo->type = $type;
        return $adminInfo->hidden(['pwd', 'is_del', 'status'])->toArray();
    }
}