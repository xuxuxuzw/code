<?php


namespace app\adminapi\middleware;

use app\Request;
use app\services\system\admin\SystemRoleServices;
use crmeb\exceptions\AuthException;
use crmeb\interfaces\MiddlewareInterface;
use crmeb\utils\ApiErrorCode;

/**
 * 权限规则验证
 * Class AdminCkeckRoleMiddleware
 * @package app\http\middleware
 */
class AdminCkeckRoleMiddleware implements MiddlewareInterface
{

    public function handle(Request $request, \Closure $next)
    {
        if (!$request->adminId() || !$request->adminInfo())
            throw new AuthException(ApiErrorCode::ERR_ADMINID_VOID);

        if ($request->adminInfo()['level']) {
            /** @var SystemRoleServices $systemRoleService */
            $systemRoleService = app()->make(SystemRoleServices::class);
            $systemRoleService->verifiAuth($request);
        }

        return $next($request);
    }
}
