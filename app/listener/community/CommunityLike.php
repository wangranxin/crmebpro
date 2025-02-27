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
 * 订单点赞取消点赞
 * Class Cancel
 * @package app\listener\order
 */
class CommunityLike implements ListenerInterface
{
    /**
     * 帖子删除事件
     * @param $event
     */
    public function handle($event): void
    {
        //type 1帖子,2评论
        //status 1点赞,2取消的点赞
        [$info, $uid, $type, $status] = $event;
        if ($info) {
            CommunityJob::dispatchDo('communityLike', [$info, $uid, $type, $status]);
        }
    }
}
