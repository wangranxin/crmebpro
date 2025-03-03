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

namespace app\dao\order;


use app\dao\BaseDao;
use app\model\order\StoreOrder;
use app\model\order\StoreOrderStatus;
use crmeb\basic\BaseModel;

/**
 * Class StoreOrderStoreOrderStatusDao
 * @package app\dao\order
 */
class StoreOrderStoreOrderStatusDao extends BaseDao
{
    protected string $alias = 'o';

    protected string $joinAlis = 's';

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreOrder::class;
    }

    /**
     * 设置链表模型
     * @return string
     */
    protected function setJoinModel(): string
    {
        return StoreOrderStatus::class;
    }

    /**
     * 设置模型
     * @return BaseModel
     */
    protected function getModel()
    {
        $name = app()->make($this->setJoinModel())->getName();
        return parent::getModel()->join($name . ' ' . $this->joinAlis, $this->joinAlis . '.oid = ' . $this->alias . '.id')->alias($this->alias);
    }

    /**
     * 搜索
     * @param array $where
     * @return BaseModel
     */
    public function search(array $where = [], bool $search = false)
    {
        return $this->getModel()->when(isset($where['paid']), function ($query) use ($where) {
            $query->where($this->alias . '.paid', $where['paid']);
        })->when(isset($where['status']), function ($query) use ($where) {
            $query->where($this->alias . '.status', $where['status']);
        })->when(isset($where['refund_status']), function ($query) use ($where) {
            $query->where($this->alias . '.refund_status', $where['refund_status']);
        })->when(isset($where['is_del']), function ($query) use ($where) {
            $query->where($this->alias . '.is_del', $where['is_del']);
        })->when(isset($where['change_type']), function ($query) use ($where) {
            $query->whereIn($this->joinAlis . '.change_type', $where['change_type']);
        })->when(isset($where['change_time']), function ($query) use ($where) {
            $query->where($this->joinAlis . '.change_time', '<', $where['change_time']);
        });
    }

    /**
     * 根据条件获取订单
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderIds(array $where, int $page = 0, int $limit = 0)
    {
        return $this->search($where)->whereIn('refund_type', [0, 3])->where('pid', '<>', -1)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->field([$this->alias . '.*'])->group($this->alias . '.id')->select()->toArray();
    }

    /**
     * 根据条件获取订单数量
     * @param array $where
     * @return int
     */
    public function getOrderCount(array $where)
    {
        return $this->search($where)->whereIn('refund_type', [0, 3])->where('pid', '<>', -1)->field([$this->alias . '.*'])->count();
    }

}
