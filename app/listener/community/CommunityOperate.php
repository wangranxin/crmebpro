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
use app\services\community\CommunityServices;
use app\services\order\StoreOrderInvoiceServices;
use crmeb\interfaces\ListenerInterface;

/**
 * 帖子操作事件
 * Class Cancel
 * @package app\listener\order
 */
class CommunityOperate implements ListenerInterface
{
    /**
     * 帖子事件
     * @param $event
     */
    public function handle($event): void
    {
        $id = $event[0] ?? 0;
        $type = $event[1] ?? 0;
        if ($id) {
            //用户发帖数数据矫正
            CommunityJob::dispatchDo('communityUserSync', [$id]);
            //话题帖子数矫正
            CommunityJob::dispatchDo('communityTopicSync', [$id]);
            if ($type == 1) {
                //新增帖子增加积分经验
                CommunityJob::dispatchDo('communityIncome', [$id]);
                //修正帖子话题
                CommunityJob::dispatchDo('communityTopicStatus', [$id]);
            }
        }
    }
}
