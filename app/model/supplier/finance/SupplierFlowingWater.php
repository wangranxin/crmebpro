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

namespace app\model\supplier\finance;

use app\model\supplier\SystemSupplier;
use app\model\user\User;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 流水
 * Class SupplierFlowingWater
 * @package app\model\supplier\finance
 */
class SupplierFlowingWater extends BaseModel
{
    use ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'supplier_flowing_water';

    /**
     * 一对一关联用户表
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid')->field(['uid', 'nickname'])->bind([
            'user_nickname' => 'nickname',
        ]);
    }

	/**
	 * 一对一关联供应商表
	 * @return \think\model\relation\HasOne
	 */
	public function supplier()
	{
		return $this->hasOne(SystemSupplier::class, 'id', 'supplier_id');
	}

	/**
	 * 一对一关联供应商表
	 * @return \think\model\relation\HasOne
	 */
	public function supplierName()
	{
		return $this->hasOne(SystemSupplier::class, 'id', 'supplier_id')->field('id,supplier_name')->bind(['supplier_name']);
	}


    /**
     * id搜索器
     * @param $query
     * @param $value
     */
    public function searchIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } else {
            $query->where('id', $value);
        }
    }

    /**
     * 供应商id搜索器
     * @param $query
     * @param $value
     */
    public function searchSupplierIdAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('supplier_id', $value);
        }
    }

    /**
     * 用户id
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if ($value) $query->where('uid', $value);
    }

    /**
     * 排除type
     * @param Model $query
     * @param $value
     */
    public function searchNoTypeAttr($query, $value)
    {
        if ($value) $query->where('type', '<>', $value);
    }

    /**
     * 交易单号
     * @param Model $query
     * @param $value
     */
    public function searchOrderIdAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('order_id', 'LIKE', "%$value%");
        }
    }

    /**
     * 关联订单号
     * @param Model $query
     * @param $value
     */
    public function searchLinkIdAttr($query, $value)
    {
        if ($value !== '') $query->where('link_id', $value);
    }

    /**
     * 支出获取
     * @param Model $query
     * @param $value
     */
    public function searchPmAttr($query, $value)
    {
        if ($value !== '') $query->where('pm', $value);
    }

    /**
     * 类型
     * @param Model $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->where('type', 'in', $value);
            } else {
                $query->where('type', $value);
            }
        }
    }

    /**
     * 支付类型
     * @param Model $query
     * @param $value
     */
    public function searchPayTypeAttr($query, $value)
    {
        if ($value !== '') $query->where('pay_type', $value);
    }

    /**
     * 删除
     * @param Model $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') $query->where('is_del', $value);
    }

    /** 状态 */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') $query->where('status', $value);
    }

}
