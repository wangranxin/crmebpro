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

namespace app\dao\activity\integral;


use app\dao\BaseDao;
use app\model\activity\integral\StoreIntegralOrder;
use crmeb\basic\BaseModel;

/**
 * 积分订单
 * Class StoreIntegralOrderDao
 * @package app\dao\activity\integral
 */
class StoreIntegralOrderDao extends BaseDao
{

    /**
     * 限制精确查询字段
     * @var string[]
     */
    protected array $withField = ['uid', 'order_id', 'real_name', 'user_phone', 'store_name'];

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return StoreIntegralOrder::class;
    }

    /**
     * 订单搜索
     * @param array $where
     * @param bool $search
     * @return BaseModel
     * @throws \ReflectionException
     */
    public function search(array $where = [], bool $search = false)
    {
        $isDel = isset($where['is_del']) && $where['is_del'] !== '' && $where['is_del'] != -1;
        $realName = $where['real_name'] ?? '';
        $fieldKey = $where['field_key'] ?? '';
        $fieldKey = $fieldKey == 'all' ? '' : $fieldKey;
        return parent::search($where, $search)->when($isDel, function ($query) use ($where) {
            $query->where('is_del', $where['is_del']);
        })->when(isset($where['is_system_del']), function ($query) {
            $query->where('is_system_del', 0);
        })->when(isset($where['paid']), function ($query) {
            $query->where('paid', 1);
        })->when(isset($where['is_price']) && $where['is_price'] == 1, function ($query) {
            $query->where('price', '>', 0);
        })->when(isset($where['is_integral']) && $where['is_integral'] == 1, function ($query) {
            $query->where('integral', '>', 0);
        })->when($realName && $fieldKey && in_array($fieldKey, $this->withField), function ($query) use ($where, $realName, $fieldKey) {
            if ($fieldKey !== 'store_name') {
                $query->where(trim($fieldKey), trim($realName));
            } else {
                $query->whereLike('store_name', '%' . $realName . '%');
            }
        })->when($realName && !$fieldKey, function ($query) use ($where) {
            $query->where(function ($que) use ($where) {
                $que->whereLike('order_id|real_name|store_name', '%' . $where['real_name'] . '%')->whereOr('uid', 'in', function ($q) use ($where) {
                    $q->name('user')->whereLike('nickname|uid|phone', '%' . $where['real_name'] . '%')->field(['uid'])->select();
                });
            });
        });
    }

	/**
	* @param array $where
	* @param string $field
	* @param int $page
	* @param int $limit
	* @param array $with
	* @return array
	* @throws \ReflectionException
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
	public function getList(array $where, string $field, int $page = 0, int $limit = 0, array $with = [])
    {
        return $this->search($where)->field($field)->with($with)
        ->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->select()->toArray();
    }

    /**
     * 订单搜索列表
     * @param array $where
     * @param array $field
     * @param int $page
     * @param int $limit
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderList(array $where, array $field, int $page, int $limit, array $with = [])
    {
        return $this->search($where)->field($field)->with(array_merge(['user'], $with))->page($page, $limit)->order('add_time DESC,id DESC')->select()->toArray();
    }

    /**
     * 根据条件获取订单列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getIntegralOrderList(array $where)
    {
        return $this->search($where)->order('id asc')->select()->toArray();
    }

    /**
     * 获取订单总数
     * @param array $where
     * @return int
     */
    public function count(array $where = []): int
    {
        return $this->search($where)->count();
    }

    /**
     * 查找指定条件下的订单数据以数组形式返回
     * @param array $where
     * @param string $field
     * @param string $key
     * @param string $group
     * @return array
     */
    public function column(array $where, string $field, string $key = '', string $group = '')
    {
        return $this->search($where)->when($group, function ($query) use ($group) {
            $query->group($group);
        })->column($field, $key);
    }

    /**
     * 获取订单详情
     * @param $uid
     * @param $key
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserOrderDetail(string $key, int $uid)
    {
        return $this->getOne(['order_id' => $key, 'uid' => $uid, 'is_del' => 0]);
    }

    /**
     * 获取用户已购买此活动商品的个数
     * @param $uid
     * @param $productId
     * @return int
     */
    public function getBuyCount($uid, $productId): int
    {
        return $this->getModel()
                ->where('uid', $uid)
                ->where('is_del', 0)
                ->where('product_id', $productId)
                ->value('sum(total_num)') ?? 0;
    }

}
