<?php

namespace app\api\middleware;

use app\services\message\service\StoreServiceServices;
use app\services\system\store\SystemStoreStaffServices;
use crmeb\interfaces\MiddlewareInterface;
use app\Request;

class CustomerMiddleware implements MiddlewareInterface
{

    public function handle(Request $request, \Closure $next)
    {
        $uid = $request->uid();
        /** @var StoreServiceServices $services */
        $services = app()->make(StoreServiceServices::class);
        /** @var SystemStoreStaffServices $storeServices */
        $storeServices = app()->make(SystemStoreStaffServices::class);
        if ((!$services->checkoutIsService($uid)) && (!$storeServices->verifyStatus($uid)))
            return app('json')->fail('权限不足');
        return $next($request);
    }
}
