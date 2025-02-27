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

namespace app\jobs\user;


use app\services\order\StoreOrderServices;
use app\services\user\UserBrokerageServices;
use app\services\user\UserServices;
use app\services\user\UserSpreadServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 佣金记录
 * Class UserSpreadJob
 * @package app\jobs\user
 */
class UserBrokerageJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 老数据同步佣金
     * @param $info
     * @return true
     * User: liusl
     * DateTime: 2024/11/23 上午11:37
     */
    public function syncOldBrokeragePrice()
    {
        try {
            $userBrokerageServices = app()->make(UserBrokerageServices::class);
            $storeOrderServices = app()->make(StoreOrderServices::class);
            $userBrokerageServices->search([])->whereIn('type', $userBrokerageServices->isBrokerage)->chunk(100, function ($list) use ($userBrokerageServices, $storeOrderServices) {
                $list = $list->toArray();
                foreach ($list as $item) {
                    if (!$item['order_uid']) {
                        $orderInfo = $storeOrderServices->getOne(['id' => $item['link_id']], 'uid,pay_price');
                        if ($orderInfo) {
                            $userBrokerageServices->update($item['id'], ['order_uid' => $orderInfo['uid'] ?? 0, 'order_price' => $orderInfo['pay_price'] ?? 0]);
                        }
                    }
                }
            });
            return true;
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '同步旧版佣金金额失败,失败原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }
}
