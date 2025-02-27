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

namespace app\jobs\product;


use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\integral\StoreIntegralServices;
use app\services\activity\seckill\StoreSeckillServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 商品佣金批量修改
 * Class ProductSupplierJob
 * @package app\jobs\product
 */
class ProductBrokerageJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 修改商品、活动商品供应商ID
     * @param $product_id
     * @param $supplier_id
     * @return bool
     */
    public function doJob(int $id, array $data)
    {
        try {

        } catch (\Throwable $e) {
            response_log_write([
                'message' => '批量佣金设置发生错误,错误原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

}
