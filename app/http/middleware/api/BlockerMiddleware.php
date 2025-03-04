<?php
/**
 *  +----------------------------------------------------------------------
 *  | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 *  +----------------------------------------------------------------------
 *  | Author: CRMEB Team <admin@crmeb.com>
 *  +----------------------------------------------------------------------
 */

namespace app\http\middleware\api;


use app\Request;
use crmeb\exceptions\ApiException;
use crmeb\interfaces\MiddlewareInterface;
use crmeb\services\CacheService;

/**
 * reids锁
 * Class BlockerMiddleware
 * @author 等风来
 * @email 136327134@qq.com
 * @date 2022/11/21
 * @package app\http\middleware\api
 */
class BlockerMiddleware implements MiddlewareInterface
{

    /**
     * @param Request $request
     * @param \Closure $next
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/21
     */
    public function handle(Request $request, \Closure $next)
    {
        $uid = $request->uid();
        $key = md5($request->rule()->getRule() . $uid. json_encode($request->param()));
        if (!CacheService::setMutex($key)) {
            throw new ApiException('请求太过频繁，请稍后再试');
        }

        $response = $next($request);

        $this->after($response, $key);

        return $response;
    }

    /**
     * @param $response
     * @param $key
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/22
     */
    public function after($response, $key)
    {
        CacheService::delMutex($key);
    }
}
