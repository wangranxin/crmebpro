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
declare (strict_types=1);

namespace app\dao\activity\coupon;

use app\dao\BaseDao;
use app\model\activity\coupon\StoreCouponIssue;
use crmeb\basic\BaseModel;


/**
 * 优惠卷
 * Class StoreCouponIssueDao
 * @package app\dao\activity\coupon
 */
class StoreCouponIssueDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreCouponIssue::class;
    }

    /**
     * @param array $where
     * @return BaseModel
     */
    public function search(array $where = [], bool $search = false)
    {
        return parent::search($where, $search)->when(isset($where['receive']) && $where['receive'] != '', function ($query) use ($where) {
            if ($where['receive'] == 'send') {
                $query->where('receive_type', 3)
                    ->where('status', 1)
                    ->where('is_del', 0)
                    ->where('remain_count > 0 OR is_permanent = 1')
                    ->where(function ($query1) {
                        $query1->where(function ($query2) {
                            $query2->where('start_time', '<=', time())->where('end_time', '>=', time());
                        })->whereOr(function ($query3) {
                            $query3->where('start_time', 0)->where('end_time', 0);
                        });
                    })->where(function ($query4) {
                        $query4->where(function ($query5) {
                            $query5->where('coupon_time', 0)->where('end_use_time', '>=', time());
                        })->whereOr('coupon_time', '>', 0);
                    });
            }
        })->when(isset($where['receive_type']) && $where['receive_type'], function ($query) use ($where) {
            $query->where('receive_type', $where['receive_type']);
        });
    }

    /**
     * 有效优惠券搜索
     * @param array $where
     * @return BaseModel|mixed|\think\Model
     */
    public function validSearch(array $where = [])
    {
        return parent::search($where)
            ->where('status', 1)
            ->where('is_del', 0)
            ->where('remain_count > 0 OR is_permanent = 1')
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<=', time())->where('end_time', '>=', time());
                })->whereOr(function ($query) {
                    $query->where('start_time', 0)->where('end_time', 0);
                });
            })->where(function ($query4) {
				$query4->where(function ($query5) {
					$query5->where('coupon_time', 0)->where('end_use_time', '>=', time());
				})->whereOr('coupon_time', '>', 0);
			});
    }

    /**
     * 获取列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, int $page = 0, int $limit = 0)
    {
        return $this->search($where)
			->when($page && $limit, function ($query) use ($page, $limit) {
				$query->page($page, $limit);
			})->when(!$page && $limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->order('id desc')->select()->toArray();
    }

    /**
 	* 获取有效的优惠券列表
	* @param array $where
	* @param string $field
	* @param int $page
	* @param int $limit
	* @param array $with
	* @return array
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function getValidList(array $where = [], string $field = '*', int $page = 0, int $limit = 0, array $with = [])
    {
        return $this->validSearch($where)->field($field)
            ->when(isset($where['not_id']) && $where['not_id'], function ($query) use ($where) {
                $query->whereNotIn('id', $where['not_id']);
            })->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->when(!$page && $limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->when($with, function ($query) use ($with) {
                $query->with($with);
            })->order('id desc')->select()->toArray();
    }

    /**
     * 获取有效的赠送券
     * @param array $ids
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getValidGiveCoupons(array $ids, string $field = '*', int $page = 0, int $limit = 0)
    {
        return $this->validSearch()->field($field)
            ->where('receive_type', 3)
            ->when(count($ids), function ($query) use ($ids) {
                $query->whereIn('id', $ids);
            })->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->select()->toArray();
    }

	/**
	 * 获取商品优惠券
	 * @param int $uid
	 * @param array $where
	 * @param string $field
	 * @param int $page
	 * @param int $limit
	 * @param string $sort
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 */
	public function getIssueCouponListNew(int $uid, array $where = [], string $field = '*', int $page = 0, int $limit = 0, string $sort = 'sort desc,id desc')
	{
		return $this->validSearch()->field($field)
			->where('receive_type', 1)
			->with(['used' => function ($query) use ($uid) {
				$query->where('uid', $uid);
			}])
			->when(isset($where['type']) && in_array($where['type'], [-1, 0, 1, 2, 3]), function ($query) use ($where) {
				if ($where['type'] == -1) {//即将过期的
					$query->where('end_time', '<>', 0)->whereTime('end_time', 'between', [time(), time() + 24 * 3600]);
				} else {
					$query->where('type', $where['type']);
				}
			})
			->when(isset($where['type']) && $where['type'] == 1 && isset($where['cate_id']) && $where['cate_id'], function ($query) use ($where) {
				$query->where('category_id', 'in', $where['cate_id']);
			})
			->when(isset($where['type']) && $where['type'] == 2 && isset($where['product_id']) && $where['product_id'], function ($query) use ($where) {
				$query->whereFindinSet('product_id', $where['product_id']);
			})
			->when(isset($where['type']) && $where['type'] == 3 && isset($where['brand_id']) && $where['brand_id'], function ($query) use ($where) {
				$query->where('brand_id', 'in', $where['brand_id']);
			})
			->when((isset($where['product_id']) && $where['product_id']) || (isset($where['cate_id']) && $where['cate_id']) || isset($where['brand_id']) && $where['brand_id'], function ($query) use ($where) {
				$query->where(function ($z) use ($where) {
					$z->whereOr('type', 0)
					->when(isset($where['product_id']) && $where['product_id'], function ($p) use ($where) {
						$p->whereOr(function ($c) use ($where) {
							$c->whereFindinSet('product_id', $where['product_id']);
						});
					})->when(isset($where['cate_id']) && $where['cate_id'], function ($c) use ($where) {
						$c->whereOr('category_id', 'in', $where['cate_id']);
					})->when(isset($where['brand_id']) && $where['brand_id'], function ($b) use ($where) {
						$b->whereOr('brand_id', 'in', $where['brand_id']);
					});
				});
			})->when(isset($where['priceOrder']) && $where['priceOrder'] != '', function ($query) use ($where) {
				if ($where['priceOrder'] === 'desc') {
					$query->field('if(coupon_type <= 1, `coupon_price`, 100-coupon_price) as true_coupon_price')->order("true_coupon_price desc");
				} else {
					$query->field('if(coupon_type <= 1, `coupon_price`, 100-coupon_price) as true_coupon_price')->order("true_coupon_price asc");
				}
			})->when(isset($where['timeOrder']) && $where['timeOrder'] != '', function ($query) use ($where) {
				if ($where['timeOrder'] === 'desc') {
					$query->order("add_time desc");
				} else {
					$query->order("add_time asc");
				}
			})->when(isset($where['defaultOrder']) && $where['defaultOrder'], function ($query) use ($where) {
				$query->order('sort desc,id desc');
			})
			->when($page && $limit, function ($query) use ($page, $limit) {
				$query->page($page, $limit);
			})->order($sort)->select()->toArray();
	}

    /**
     * 获取优惠券列表
     * @param int $uid 用户ID
     * @param int $type 0通用，1分类，2商品
     * @param int $typeId 分类ID或商品ID
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getIssueCouponList(int $uid, int $type, $typeId, string $field = '*', int $page = 0, int $limit = 0)
    {
        return $this->validSearch()->field($field)
            ->where('receive_type', 1)
            ->with(['used' => function ($query) use ($uid) {
                $query->where('uid', $uid);
            }])
            ->where('type', $type)
            ->when($type == 1, function ($query) use ($typeId) {
                if ($typeId) $query->where('category_id', 'in', $typeId);
            })
            ->when($type == 2, function ($query) use ($typeId) {
                if ($typeId) $query->whereFindinSet('product_id', $typeId);
            })
            ->when($type == 3, function ($query) use ($typeId) {
				if ($typeId) $query->where('brand_id', 'in', $typeId);
			})
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * PC端获取优惠券
     * @param int $uid
     * @param array $cate_ids
     * @param int $product_id
     * @param string $filed
     * @param int $page
     * @param int $limit
     * @param string $sort
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPcIssueCouponList(int $uid, array $cate_ids = [], int $product_id = 0, string $filed = '*', int $page = 0, int $limit = 0, string $sort = 'sort desc,id desc')
    {
        return $this->validSearch()->field($filed)
            ->where('receive_type', 1)
            ->with(['used' => function ($query) use ($uid) {
                $query->where('uid', $uid);
            }])->where(function ($query) use ($product_id, $cate_ids) {
                if ($product_id != 0 && $cate_ids != []) {
                    $query->whereFindinSet('product_id', $product_id)->whereOr('category_id', 'in', $cate_ids)->whereOr('type', 0);
                }
            })->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->when(!$page && $limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->order($sort)->select()->toArray();
    }

    /**
 	* 获取优惠券数量
	* @param $productId
	* @param $cateId
	* @param $brandId
	* @return array
	* @throws \think\db\exception\DbException
	*/
    public function getIssueCouponCount($productId = 0, $cateId = 0, $brandId = 0)
    {
        $count[0] = $this->validSearch()->where('receive_type', 1)->where('type', 0)->count();
        $count[1] = $this->validSearch()->where('receive_type', 1)->where('type', 1)->when($cateId != 0, function ($query) use ($cateId) {
            if ($cateId) $query->where('category_id', 'in', $cateId);
        })->count();
        $count[2] = $this->validSearch()->where('receive_type', 1)->where('type', 2)->when($productId != 0, function ($query) use ($productId) {
            if ($productId) $query->whereFindinSet('product_id', $productId);
        })->count();
		$count[3] = $this->validSearch()->where('receive_type', 1)->where('type', 3)->when($productId != 0, function ($query) use ($brandId) {
			if ($brandId) $query->where('brand_id', 'in', $brandId);
		})->count();
        return $count;
    }

    /**
     * 获取优惠卷详情
     * @param int $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfo(int $id)
    {
        return $this->validSearch()->where('id', $id)->find();
    }

    /**
     * 获取金大于额的优惠卷金额
     * @param string $totalPrice
     * @return float
     */
    public function getUserIssuePrice(string $totalPrice)
    {
        return $this->search(['status' => 1, 'is_full_give' => 1, 'is_del' => 0])
            ->where('full_reduction', '<=', $totalPrice)
            ->sum('coupon_price');
    }

    /**
     * 获取新人券
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNewCoupon()
    {
        return $this->validSearch()->where('receive_type', 2)->select()->toArray();
    }

    /**
     * 获取一条优惠券信息
     * @param int $id
     * @return mixed
     */
    public function getCouponInfo(int $id)
    {
        return $this->getModel()->where('id', $id)->where('status', 1)->where('is_del', 0)->find();
    }

    /**
     * 获取满赠、下单、关注赠送优惠券
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGiveCoupon(array $where, string $field = '*')
    {
        return $this->validSearch()->field($field)->where($where)->select()->toArray();
    }

    /**
     * 获取商品优惠卷列表
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function productCouponList($where, $field)
    {
        return $this->getModel()->where($where)->field($field)->select()->toArray();
    }

    /**
 	* 获取优惠券弹窗列表
	* @param array $receive_type
	* @param int $limit
	* @return array
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function getTodayCoupon(array $receive_type = [1, 4], int $limit = 10)
    {
        return $this->getModel()
        	->where('status', 1)
            ->where('is_del', 0)
            ->whereIn('receive_type', $receive_type)
            ->whereDay('add_time')
            ->when($limit, function ($query) use ($limit) {
            	$query->limit($limit);
            })->order('sort desc,id desc')->select()->toArray();
    }



}
