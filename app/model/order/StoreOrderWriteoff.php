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

namespace app\model\order;

use app\model\store\SystemStoreStaff;
use app\model\user\User;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 *  订单核销记录Model
 * Class StoreOrderWriteoff
 * @package app\model\order
 */
class StoreOrderWriteoff extends BaseModel
{
    use ModelTrait;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_order_writeoff';


	/**
	 * 获取领取人名称头像
	 * @return \think\model\relation\HasOne
	 */
	public function userInfo()
	{
		return $this->hasOne(User::class, 'uid', 'uid')->field('uid,nickname,avatar,phone')->bind(['nickname', 'avatar', 'phone']);
	}

	/**
	 * 订单信息
	 * @return \think\model\relation\HasOne
	 */
	public function orderInfo()
	{
		return $this->hasOne(StoreOrder::class, 'id', 'oid');
	}

	/**
	 * 订单商品信息
	 * @return \think\model\relation\HasOne
	 */
	public function cartInfo()
	{
		return $this->hasOne(StoreOrderCartInfo::class, 'id', 'order_cart_id');
	}

	/**
	 * 店员信息
	 * @return \think\model\relation\HasOne
	 */
	public function staffInfo()
	{
		return $this->hasOne(SystemStoreStaff::class, 'id', 'staff_id')->field('id,store_id,staff_name')->bind(['staff_name']);
	}

    /**
     * 订单ID搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchOidAttr($query, $value, $data)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('oid', $value);
            } else {
                $query->where('oid', $value);
            }
        }
    }

    /**
     * UID搜索器
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('uid', $value);
            } else {
                $query->where('uid', $value);
            }
        }
    }

	/**
	 * 商品类型搜索器
	 * @param Model $query
	 * @param $value
	 */
	public function searchTypeAttr($query, $value)
	{
		if (is_array($value)) {
			if ($value) $query->whereIn('type', $value);
		} else {
			if ($value !== '') $query->where('type', $value);
		}
	}

	/**
	 * 关联门店ID、供应商ID搜索器
	 * @param Model $query
	 * @param $value
	 */
	public function searchRelationIdAttr($query, $value)
	{
		if (is_array($value)) {
			if ($value) $query->whereIn('relation_id', $value);
		} else {
			if ($value !== '') $query->where('relation_id', $value);
		}
	}

    /**
     * product_id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchProductIdAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('product_id', $value);
            } else {
                $query->where('product_id', $value);
            }
        }
    }

    /**
     * 订单商品ID搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchOrderCartIdAttr($query, $value, $data)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('order_cart_id', $value);
            } else {
                $query->where('order_cart_id', $value);
            }
        }
    }

	/**
	 * staff_id搜索器
	 * @param Model $query
	 * @param $value
	 */
	public function searchStaffIdAttr($query, $value)
	{
		if ($value) {
			if (is_array($value)) {
				$query->whereIn('staff_id', $value);
			} else {
				$query->where('staff_id', $value);
			}
		}
	}

    public function searchProductTypeAttr($query, $value)
	{
		if ($value) {
			if (is_array($value)) {
				$query->whereIn('product_type', $value);
			} else {
				$query->where('product_type', $value);
			}
		}
	}


}
