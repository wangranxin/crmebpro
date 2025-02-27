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

namespace app\jobs\supplier;


use app\services\supplier\finance\SupplierFlowingWaterServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 供应商资金流水记录
 * Class SupplierFinanceJob
 * @package app\jobs
 */
class SupplierFinanceJob extends BaseJobs
{
    use QueueTrait;

    /**供应商流水
     * @param int $oid
     * @param int $type
     * @return bool
     */
    public function doJob(int $oid, int $type)
    {
        try {
            /** @var SupplierFlowingWaterServices $supplierFlowServices */
            $supplierFlowServices = app()->make(SupplierFlowingWaterServices::class);
            $supplierFlowServices->setSupplierFinance($oid, $type);
        } catch (\Throwable $e) {
            Log::error('记录流水失败:' . $e->getMessage());
        }
        return true;
    }
}
