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
use app\model\order\StoreOrderCartInfo;

/**
 * 订单详情
 * Class StoreOrderCartInfoDao
 * @package app\dao\order
 * @method saveAll(array $data)
 */
class StoreOrderCartInfoDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreOrderCartInfo::class;
    }

    /**
     * 获取购物车详情列表
     * @param array $where
     * @param array $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCartInfoList(array $where, array $field = ['*'])
    {
        return $this->search($where)->field($field)->select()->toArray();
    }

    /**
     * 获取购物车信息以数组返回
     * @param array $where
     * @param string $field
     * @param string $key
     */
    public function getCartColunm(array $where, string $field, string $key = '')
    {
        return $this->search($where)->order('id asc')->column($field, $key);
    }

    /**
     * @param $cart_ids
     * @return array
     */
    public function getSplitCartNum($cart_ids)
    {
        $res = $this->getModel()->whereIn('old_cart_id', $cart_ids)->field('sum(cart_num) as num,old_cart_id')->group('old_cart_id')->select()->toArray();
        $data = [];
        foreach ($res as $value) {
            $data[$value['old_cart_id']] = $value['num'];
        }
        return $data;
    }

	/**
 	 * 临期 卡次
     * @param int $writeEndTime
     * @param int $writeTime
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAdventCartInfoList(int $time = 0, int $writeTime = 0)
    {
        $list = $this->getModel()->where(['is_writeoff' => 0, 'is_advent_sms' => 0])->where('write_start', '>', 0)->where('write_end', '>', 0)
            ->where('write_end', '>', $time)->where('write_end', '<=', $writeTime)
            ->field('id,oid,product_id,write_times,write_surplus_times,write_start,write_end,cart_info')->select();
        $list = count($list) > 0 ? $list->toArray() : [];
        return $list;
    }

    /**
 	 * 过期 卡次
     * @param int $writeTime
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getExpireCartInfoList(int $writeTime = 0)
    {
        $list = $this->getModel()->where(['is_writeoff' => 0, 'is_expire_sms' => 0])->where('write_start', '>', 0)->where('write_end', '>', 0)
            ->where('write_end', '<', $writeTime)
            ->field('id,oid,product_id,write_times,write_surplus_times,write_start,write_end,cart_info')->select();
        $list = count($list) > 0 ? $list->toArray() : [];
        return $list;
    }
}
