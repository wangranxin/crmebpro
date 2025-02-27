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
namespace app\listener\community;

use app\jobs\activity\StorePromotionsJob;
use app\jobs\community\CommunityJob;
use app\services\order\StoreOrderInvoiceServices;
use crmeb\interfaces\ListenerInterface;

/**
 * 订单取消删除事件
 * Class Cancel
 * @package app\listener\order
 */
class CommunityCommentDelete implements ListenerInterface
{
    /**
     * 帖子删除事件
     * @param $event
     */
    public function handle($event): void
    {
        [$id] = $event;
        if ($id) {
            CommunityJob::dispatchDo('CommunityCommentDelete', [$id]);
        }
    }
}
