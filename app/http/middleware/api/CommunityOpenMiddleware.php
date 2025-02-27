<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\http\middleware\api;


use app\Request;
use app\services\community\CommunityUserServices;
use crmeb\interfaces\MiddlewareInterface;

/**
 * 社区是否开启
 * Class StationOpenMiddleware
 * @package app\api\middleware
 */
class CommunityOpenMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next)
    {
        if (!sys_config('community_status', 1)) {
            return app('json')->make('410010', '社区暂未开放');
        }
        //社区用户写入
        $uid = $request->hasMacro('uid') ? $request->uid() : 0;
        if ($uid) {
            /** @var CommunityUserServices $communityUserServices */
            $communityUserServices = app()->make(CommunityUserServices::class);
            $communityUserServices->hasUser($uid);
        }
        return $next($request);
    }
}
