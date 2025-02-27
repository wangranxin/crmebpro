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

namespace app\jobs\order;


use app\services\order\StoreOrderStatusServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * Class OrderStatusJob
 * @package app\jobs\order
 */
class OrderStatusJob extends BaseJobs
{

    use QueueTrait;

    /**
     * 写入订单记录
     * @param $orderId
     * @param $changeType
     * @param $data
     * @return bool
     */
    public function doJob($orderId, $changeType = '', $data = [])
    {
        if (!$orderId || !$data) {
            return true;
        }
        try {
            /** @var StoreOrderStatusServices $statusService */
            $statusService = app()->make(StoreOrderStatusServices::class);
            $changeManagerId = $data['change_manager_id'] ?? 0;
            $changeManagerType = $data['change_manager_type'] ?? '';
            $statusService->saveStatus((int)$orderId, (string)$changeType, (array)$data, (int)$changeManagerId, (string)$changeManagerType);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '写入订单【'.$changeType.'】记录失败,失败原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        return true;
    }

}
