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

namespace app\listener\system\http;

use think\facade\Log;
use think\Request;
use think\Response;

/**
 * 订单创建事件
 * Class HttpEnd
 * @package app\listener\http
 */
class HttpEnd
{
    public function handle(Response $response): void
    {
        $data = $response->getData();
        //业务成功和失败分开存储
        $status = is_array($data) ? ($data["status"] ?? 0) : 0;
        if ($status == 200) {
            //业务成功日志开关
            if (!config("log.success_log")) return;
            $logType = "success";
        } else {
            //业务失败日志开关
            if (!config("log.fail_log")) return;
            $logType = "fail";
        }

        response_log_write($response->getData(), $logType);
    }
}
