<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/2
 */

namespace app\adminapi\middleware;


use app\Request;
use app\services\system\log\SystemLogServices;
use crmeb\interfaces\MiddlewareInterface;

/**
 * 日志中間件
 * Class AdminLogMiddleware
 * @package app\adminapi\middleware
 */
class AdminLogMiddleware implements MiddlewareInterface
{
    /**
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        try {
            /** @var SystemLogServices $services */
            $services = app()->make(SystemLogServices::class);
            $services->recordAdminLog($request->adminId(), $request->adminInfo()['account'], 'system');
        } catch (\Throwable $e) {
        }
        return $next($request);
    }

}