<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/1
 */

namespace crmeb\utils;


use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use Firebase\JWT\JWT;
use think\facade\Env;

/**
 * Jwt
 * Class JwtAuth
 * @package crmeb\utils
 */
class JwtAuth
{

    /**
     * token
     * @var string
     */
    protected $token;

    /**
     * 获取token
     * @param int $id
     * @param string $type
     * @param array $params
     * @return array
     */
    public function getToken(int $id, string $type, array $params = []): array
    {
        $host = app()->request->host();
        $time = time();

        $params += [
            'iss' => $host,
            'aud' => $host,
            'iat' => $time,
            'nbf' => $time,
            'exp' => strtotime('+ 3hour'),
        ];
        $params['jti'] = compact('id', 'type');
        $token = JWT::encode($params, Env::get('app.app_key', 'default'));

        return compact('token', 'params');
    }

    /**
     * 解析token
     * @param string $jwt
     * @return array
     */
    public function parseToken(string $jwt): array
    {
        $this->token = $jwt;
        list($headb64, $bodyb64, $cryptob64) = explode('.', $this->token);
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));
        return [$payload->jti->id, $payload->jti->type];
    }

    /**
     * 验证token
     */
    public function verifyToken()
    {
        JWT::$leeway = 60;

        JWT::decode($this->token, Env::get('app.app_key', 'default'), array('HS256'));

        $this->token = null;
    }

    /**
     * 获取token并放入令牌桶
     * @param int $id
     * @param string $type
     * @param array $params
     * @return array
     */
    public function createToken(int $id, string $type, array $params = [])
    {
        $tokenInfo = $this->getToken($id, $type, $params);
        $exp = $tokenInfo['params']['exp'] - $tokenInfo['params']['iat'] + 60;
        $res = CacheService::setTokenBucket($tokenInfo['token'], ['uid' => $id, 'type' => $type, 'token' => $tokenInfo['token'], 'exp' => $exp], (int)$exp);
        if (!$res) {
            throw new AdminException(ApiErrorCode::ERR_SAVE_TOKEN);
        }
        return $tokenInfo;
    }
}