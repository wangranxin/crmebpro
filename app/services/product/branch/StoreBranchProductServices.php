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

namespace app\services\product\branch;


use app\dao\product\product\StoreProductDao;
use app\services\BaseServices;
use app\services\order\StoreCartServices;
use app\services\product\product\StoreDescriptionServices;
use app\services\product\product\StoreProductRelationServices;
use app\services\product\product\StoreProductReplyCommentServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrResultServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use crmeb\exceptions\AdminException;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;

/**
 * Class StoreBranchProductServices
 * @package app\services\product\branch
 * @mixin StoreProductDao
 */
class StoreBranchProductServices extends BaseServices
{

    use ServicesTrait;

    /**
     * StoreBranchProductServices constructor.
     * @param StoreProductDao $dao
     */
    public function __construct(StoreProductDao $dao)
    {
        $this->dao = $dao;
    }


    /**
     * 保存或者修改门店数据
     * @param int $id
     * @param int $storeId
     * @param int $stock
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveStoreProduct(int $id, int $storeId, int $stock, array $data = [])
    {
        /** @var StoreProductServices $service */
        $service = app()->make(StoreProductServices::class);
        $productData = $service->get($id, ['store_name', 'image', 'sort', 'store_info', 'keyword', 'bar_code', 'cate_id', 'is_show']);
        if (!$productData) {
            throw new ValidateException('商品不穿在');
        }
        $productData = $productData->toArray();
        $productInfo = $this->dao->get(['product_id' => $id, 'store_id' => $storeId]);
        if ($productInfo) {
            $productInfo->label_id = isset($data['label_id']) ? implode(',', $data['label_id']) : '';
            $productInfo->is_show = $data['is_show'] ?? 1;
            $productInfo->stock = $stock;
            $productInfo->image = $productData['image'];
            $productInfo->sort = $productData['sort'];
            $productInfo->store_name = $productData['store_name'];
            $productInfo->store_info = $productData['store_info'];
            $productInfo->keyword = $productData['keyword'];
            $productInfo->bar_code = $productData['bar_code'];
            $productInfo->cate_id = $productData['cate_id'];
            $productInfo->save();
        } else {
            $product = [];
            $product['product_id'] = $id;
            $product['label_id'] = isset($data['label_id']) ? implode(',', $data['label_id']) : '';
            $product['is_show'] = $data['is_show'] ?? 1;
            $product['store_id'] = $storeId;
            $product['stock'] = $stock;
            $product['image'] = $productData['image'];
            $product['sort'] = $productData['sort'];
            $product['store_name'] = $productData['store_name'];
            $product['store_info'] = $productData['store_info'];
            $product['keyword'] = $productData['keyword'];
            $product['bar_code'] = $productData['bar_code'];
            $product['cate_id'] = $productData['cate_id'];
            $product['add_time'] = time();
            $this->dao->save($product);
        }
        return true;
    }


    /**
     * 获取商品库存
     * @param int $productId
     * @param string $uniqueId
     * @return int|mixed
     */
    public function getProductStock(int $productId, int $storeId, string $uniqueId = '')
    {
        /** @var  StoreProductAttrValueServices $StoreProductAttrValue */
        $StoreProductAttrValue = app()->make(StoreProductAttrValueServices::class);
        return $uniqueId == '' ?
            $this->dao->value(['product_id' => $productId], 'stock') ?: 0
            : $StoreProductAttrValue->uniqueByStock($uniqueId);
    }



    /**
     * 上下架
     * @param int $store_id
     * @param int $id
     * @param int $is_show
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setShow(int $store_id, int $id, int $is_show)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new AdminException('操作失败！');
        }
        //平台统一商品
        if ($info['pid']) {
            $productInfo = $this->dao->get($info['pid']);
            if ($is_show && !$productInfo['is_show']) {
                throw new AdminException('平台该商品暂未上架！');
            }
        }
        /** @var StoreCartServices $cartService */
        $cartService = app()->make(StoreCartServices::class);
        $cartService->batchUpdate([$id], ['status' => $is_show], 'product_id');
        $update = ['is_show' => $is_show];
        if ($is_show) {//手动上架 清空定时下架状态
			if ($info['is_verify'] != 1) {//验证商品是否审核通过
				throw new AdminException('该商品暂未审核通过');
			}
            if($info['price'] <= 0) {
                throw new AdminException('未设置售价，请联系平台处理');
            }
            $update['auto_off_time'] = 0;
        }
        $res = $this->update($info['id'], $update);
        /** @var StoreProductRelationServices $storeProductRelationServices */
        $storeProductRelationServices = app()->make(StoreProductRelationServices::class);
        $storeProductRelationServices->setShow([$id], (int)$is_show);

        if (!$res) throw new AdminException('操作失败！');
    }


	/**
	 * 删除门店、供应商同步删除商品
	 * @param array $where
	 * @param int $type
	 * @param int $relation_id
	 * @return bool
	 */
	public function deleteProducts(array $where = [], int $type = 0, int $relation_id = 0)
	{
		$where['type'] = $type;
		$where['relation_id'] = $relation_id;
		$productIds = $this->dao->getColumn($where, 'id');
		if ($productIds) {
			/** @var StoreProductAttrServices $productAttrServices */
			$productAttrServices = app()->make(StoreProductAttrServices::class);
			/** @var StoreProductAttrResultServices $productAttrResultServices */
			$productAttrResultServices = app()->make(StoreProductAttrResultServices::class);
			/** @var StoreProductAttrValueServices $productAttrValueServices */
			$productAttrValueServices = app()->make(StoreProductAttrValueServices::class);
			/** @var StoreDescriptionServices $productDescriptionServices */
			$productDescriptionServices = app()->make(StoreDescriptionServices::class);
			/** @var StoreProductRelationServices $productRelationServices */
			$productRelationServices = app()->make(StoreProductRelationServices::class);
			/** @var StoreProductReplyServices $productReplyServices */
			$productReplyServices = app()->make(StoreProductReplyServices::class);
			/** @var StoreProductReplyCommentServices $productReplyCommentServices */
			$productReplyCommentServices = app()->make(StoreProductReplyCommentServices::class);
			$idsArr = array_chunk($productIds, 100);
			foreach ($idsArr as $ids) {
				$productAttrServices->delete(['product_id' => $ids, 'type' => 0]);
				$productAttrResultServices->delete(['product_id' => $ids, 'type' => 0]);
				$productAttrValueServices->delete(['product_id' => $ids, 'type' => 0]);
				$productDescriptionServices->delete(['product_id' => $ids, 'type' => 0]);
				$productRelationServices->delete(['product_id' => $ids]);
				$this->dao->delete(['id' => $ids]);

				$replyIds = $productReplyServices->getColumn([['product_id', 'IN', $ids]], 'id');
				$replyIdsArr = array_chunk($replyIds, 100);
				foreach ($replyIdsArr as $rids) {
					$productReplyCommentServices->delete(['reply_id' => $rids]);
					$productReplyServices->delete(['id' => $rids]);
				}

				event('product.delete', [$ids]);
			}

			$this->dao->cacheTag()->clear();
			$productAttrServices->cacheTag()->clear();
		}
		return true;
	}

}
