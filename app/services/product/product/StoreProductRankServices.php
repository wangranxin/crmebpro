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
namespace app\services\product\product;

use app\dao\product\product\StoreProductDao;
use app\services\BaseServices;
use app\services\user\UserServices;
use think\annotation\Inject;


/**
 * Class StoreProductRankServices
 * @package app\services\product\product
 * @mixin StoreProductDao
 */
class StoreProductRankServices extends BaseServices
{
	/**
 	* 排名最大名次
	* @var int
	*/
	protected int $rankMax = 20;

    /**
     * @var StoreProductDao
     */
    #[Inject]
    protected StoreProductDao $dao;


	/**
 	* 获取商品在排行榜中排名
	* @param int $uid
	* @param int $productId
	* @param int $type
	* @return int
	*/
	public function getProductRank(int $uid, int $productId, int $type = 1)
	{
		$where['is_vip_product'] = 0;
        $where['is_verify'] = 1;
        $where['pid'] = 0;
		$where['is_show'] = 1;
		$where['is_del'] = 0;
        if ($uid) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $is_vip = $userServices->value(['uid' => $uid], 'is_money_level');
            $where['is_vip_product'] = $is_vip ? -1 : 0;
        }
		$field = ['id', 'IFNULL(sales,0) + IFNULL(ficti,0) as sales', 'star'];
		$order = match($type) {
			1 => 'sales desc, sort desc, id desc',//销量
			2 => 'star desc, sort desc, id desc',//评分
			3 => 'collect desc, sort desc, id desc',//收藏
			default => 'sales desc, sort desc, id desc',
		};
		$list = $this->dao->getRecommendProduct($where, $field, $this->rankMax, 0, 0, [], $order);
		$rank = 0;
		if ($list) {
			$key = array_search($productId, array_column($list, 'id'));
			if ($key !== false) {
				$rank = (int)$key + 1;
			}
		}
		return $rank;
	}

	/**
 	* 获取商品排行数据
	* @param int $uid
	* @param int $type
	* @param array $where
	* @param int $limit
	* @return array|null
	* @throws \ReflectionException
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	* @throws \throwable
	*/
	public function getProductRankList(int $uid, int $type = 1, array $where = [], int $limit = 0)
	{
		/** @var StoreProductServices $productServices */
		$productServices = app()->make(StoreProductServices::class);
		$order = match($type) {
			1 => 'sales desc, sort desc, id desc',//销量
			2 => 'star desc, sort desc, id desc',//评分
			3 => 'collect desc, sort desc, id desc',//收藏
			default => 'sales desc, sort desc, id desc',
		};
		return $productServices->getRecommendProduct($uid, $where, $limit, 'mid', $order);
	}

}
