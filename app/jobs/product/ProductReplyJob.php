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


use app\services\product\product\StoreProductCateServices;
use app\services\product\product\StoreProductReplyServices;use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 商品分类
 * Class ProductCateJob
 * @package app\jobs\product
 */
class ProductReplyJob extends BaseJobs
{
    use QueueTrait;

    /**
 	* 计算商品评分
	* @param $id
	* @return bool
	*/
    public function computedProductStar($id)
    {
		if (!is_array($id)) {
			$id = (int)$id;
			if (!$id) {
				return true;
			}
			$ids = [$id];
		} else {
			$ids = $id;
		}
        try {
            /** @var  StoreProductReplyServices $storeProductReplayServices */
            $storeProductReplayServices = app()->make(StoreProductReplyServices::class);
			foreach ($ids as $id) {
				$storeProductReplayServices->computedProductStar((int)$id);
			}
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '计算商品评分发生错误,错误原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

}
