<?php


namespace crmeb\services\workerman;


use app\services\system\admin\AdminAuthServices;
use crmeb\exceptions\AuthException;
use Workerman\Connection\TcpConnection;

class WorkermanHandle
{
    protected $service;

    public function __construct(WorkermanService &$service)
    {
        $this->service = &$service;
    }

    public function login(TcpConnection &$connection, array $res, Response $response)
    {
        if (!isset($res['data']) || !$token = $res['data']) {
            return $response->close([
                'msg' => '授权失败!'
            ]);
        }

        try {
            /** @var AdminAuthServices $adminAuthService */
            $adminAuthService = app()->make(AdminAuthServices::class);
            $authInfo = $adminAuthService->parseToken($token);
        } catch (AuthException $e) {
            return $response->close([
                'msg' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }

        if (!$authInfo || !isset($authInfo['id'])) {
            return $response->close([
                'msg' => '授权失败!'
            ]);
        }

        $connection->adminInfo = $authInfo;
        $connection->adminId = $authInfo['id'];
        $this->service->setUser($connection);

        return $response->success();
    }
}
