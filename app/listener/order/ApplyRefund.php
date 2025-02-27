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
namespace app\listener\order;

use app\jobs\notice\SocketPushJob;
use crmeb\interfaces\ListenerInterface;

/**
 * 订单申请退款事件
 * Class ApplyRefund
 * @package app\listener\order
 */
class ApplyRefund implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        [$order, $refundId] = $event;

		SocketPushJob::dispatchDo('sendApplyRefund', [$order]);

        //退款消息推送
        event('notice.notice', [['order' => $order], 'send_order_apply_refund']);

    }
}
