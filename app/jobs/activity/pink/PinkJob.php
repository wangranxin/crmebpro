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

namespace app\jobs\activity\pink;


use app\services\activity\combination\StorePinkServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 拼团失败
 * Class PinkJob
 * @package app\jobs
 */
class PinkJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($pinkId)
    {
        try {
            /** @var StorePinkServices $pinkService */
            $pinkService = app()->make(StorePinkServices::class);
            $info = $pinkService->get((int)$pinkId);
			if (!$info) {
				return true;
			}
			//已经成功 || 失败处理
			if (in_array($info['status'], [2, 3])) {
				return true;
			}
			[$pinkAll, $pinkT, $count, $idAll, $uidAll] = $pinkService->getPinkMemberAndPinkK($info);
			$pinkService->pinkFail($pinkAll, $pinkT, 0);

        } catch (\Throwable $e) {
            response_log_write([
                'message' => '拼团超时处理失败，原因：' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

    /**
     * 创建拼团
     * @param $orderInfo
     * @return bool
     */
    public function createPink($orderInfo)
    {
        if (!$orderInfo) {
            return true;
        }
        try {
            /** @var StorePinkServices $pinkServices */
            $pinkServices = app()->make(StorePinkServices::class);
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            $pinkServices->createPink($orderServices->tidyOrder($orderInfo, true));//创建拼团
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '创建拼团失败失败，原因：' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }
}
