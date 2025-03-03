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

namespace app\jobs\notice;


use crmeb\basic\BaseJobs;
use crmeb\services\printer\Printer;
use crmeb\traits\QueueTrait;


/**
 * 小票打印
 * Class PrintJob
 * @package app\jobs\notice
 */
class PrintJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 小票打印
     * @param $type
     * @param $configdata
     * @param $order
     * @param $product
     * @return bool
     */
    public function doJob($type, $configdata, $order, $product)
    {
        try {
            $printer = new Printer($type, $configdata);
            $printer->setPrinterContent([
                'name' => sys_config('site_name'),
                'orderInfo' => is_object($order) ? $order->toArray() : $order,
                'product' => $product
            ])->startPrinter();
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '小票打印失败失败,失败原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
		return true;
    }

}
