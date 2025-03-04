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

namespace app\model\activity\combination;

use app\model\product\product\StoreProduct;use app\model\user\User;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 拼团Model
 * Class StorePink
 * @package app\model\activity\combination
 */
class StorePink extends BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_pink';

    use ModelTrait;

    /**
     * 用户一对一关联
     * @return \think\model\relation\HasOne
     */
    public function getUser()
    {
        return $this->hasOne(User::class, 'uid', 'uid', false)->bind(['delete_time']);
    }

    public function getProduct()
    {
        return $this->hasOne(StoreCombination::class, 'id', 'cid')->bind(['title']);
    }

	/**
     * 一对一获取原价
     * @return \think\model\relation\HasOne
     */
    public function getPrice()
    {
        return $this->hasOne(StoreProduct::class, 'id', 'pid')->field(['id', 'ot_price', 'price', 'quota_show', 'quota'])->bind(['ot_price', 'product_price' => 'price', 'quota_show', 'quota']);
    }

    /**
     * 订单号搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchOrderIdAttr($query, $value, $data)
    {
        $query->where('order_id', $value);
    }

    /**
     * 订单编号搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchOrderIdKeyAttr($query, $value, $data)
    {
        $query->where('order_id_key', $value);
    }

    /**
     * 拼团商品ID搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchCidAttr($query, $value, $data)
    {
        $query->where('cid', $value);
    }

    /**
     * 商品ID搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchPidAttr($query, $value, $data)
    {
        $query->where('pid', $value);
    }

    /**
     * 是否团长搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchKIdAttr($query, $value, $data)
    {
        $query->where('k_id', $value);
    }

    /**
     * 是否退款搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsRefundAttr($query, $value, $data)
    {
        $query->where('is_refund', $value ?? 0);
    }

    /**
     * 状态搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchStatusAttr($query, $value, $data)
    {
        if ($value != '') $query->where('status', $value);
    }
}
