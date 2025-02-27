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
namespace app\listener\notice;

use app\services\message\NoticeService;
use crmeb\interfaces\ListenerInterface;

/**
 * 发送消息
 * Class Create
 * @package app\listener\order
 */
class Notice implements ListenerInterface
{

    /**
     * @param $event
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    public function handle($event): void
    {
        try {
            [$data, $mark] = $event;

            app()->make(NoticeService::class)->setEvent($mark)->handle($data);

        } catch (\Throwable $e) {
            response_log_write([
                'message' => '发送消息错误' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
}
