<?php


namespace app\services\product\sku;


use app\dao\product\sku\StoreProductVirtualDao;
use app\services\BaseServices;
use app\services\product\product\StoreProductServices;
use think\annotation\Inject;

/**
 * 规格卡密信息
 * Class StoreProductVirtualServices
 * @package app\services\product\sku
 * @mixin StoreProductVirtualDao
 */
class StoreProductVirtualServices extends BaseServices
{
    /**
     * @var StoreProductVirtualDao
     */
    #[Inject]
    protected StoreProductVirtualDao $dao;

    /**
     * 规格中获取卡密列表
     * @param $unique
     * @param $product_id
     * @return array
     */
    public function getArr($unique, $product_id)
    {
        $res = $this->dao->getColumn(['attr_unique' => $unique, 'product_id' => $product_id], 'card_no,card_pwd');
        $data = [];
        foreach ($res as $item) {
            $data[] = ['key' => $item['card_no'], 'value' => $item['card_pwd']];
        }
        return $data;
    }

    /**
     * 获取订单发送卡密列表
     * @param array $where
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderCardList(array $where, int $limit = 1)
    {
        return $this->dao->getList($where, '*', 0, $limit);
    }

    /**
     * 保存商品规格（虚拟卡密信息）
     * @param int $id
     * @param array $valueGroup
     * @param int $store_id
     * @return bool
     */
    public function saveProductVirtual(int $id, array $valueGroup, int $store_id = 0)
    {
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $cardList = $this->dao->getList(['product_id' => $id, 'store_id' => $store_id], "card_no");
        $cardList = empty($cardList) ? [] : array_column($cardList, "card_no");
        $count = 0;

        foreach ($valueGroup as $item) {
            if (isset($item['product_type']) && $item['product_type'] == 1) {
                $num = 0;
                if (isset($item['disk_info']) && $item['disk_info']) {
                    $num = $item['stock'];
                    $count += $num;
                } elseif (isset($item['virtual_list']) && count($item['virtual_list'])) {
                    $num = $this->dao->count(['store_id' => $store_id, 'product_id' => $id, 'attr_unique' => $item['unique'], 'uid' => 0]) ?? 0;
                    $count += $num;
                    $data = [];
                    foreach ($item['virtual_list'] as $items) {
                        if (!in_array($items['key'], $cardList)) {
                            $data[] = [
                                'product_id' => $id,
                                'attr_unique' => $item['unique'],
                                'card_no' => $items['key'],
                                'card_pwd' => $items['value'],
                                'card_unique' => md5($item['unique'] . ',' . $items['key'] . ',' . $items['value'])
                            ];
                            $num++;
                            $count++;
                        }
                    }
                }
                if (!empty($data)) {
                    $this->dao->saveAll($data);
                }
                $storeProductAttrValueServices->update(['product_id' => $id, 'unique' => $item['unique']], ['stock' => $num]);
            }
        }
        $storeProductServices->update($id, ['stock' => $count]);
        unset($item);
        return true;
    }
}
