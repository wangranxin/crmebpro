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

namespace app\dao\product\sku;

use app\dao\BaseDao;
use app\model\product\sku\StoreProductAttrValue;

/**
 * Class StoreProductAttrValueDao
 * @package app\dao\product\sku
 */
class StoreProductAttrValueDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProductAttrValue::class;
    }

    /**
	* @param array $where
	* @param string $field
	* @param int $page
	* @param int $limit
	* @param string $order
	* @return array
	* @throws \ReflectionException
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0, string $order = 'id desc')
    {
        return $this->search($where)->field($field)
			->when($page && $limit, function ($query) use ($page, $limit) {
				$query->page($page, $limit);
			})->when(!$page && $limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->order($order)->select()->toArray();
    }

    /**
     * 减库存
     * @param array $where
     * @param int $num
     * @param string $stock
     * @param string $sales
     * @return bool|mixed
     */
    public function decStockIncSales(array $where, int $num, string $stock = 'stock', string $sales = 'sales')
    {
        $isQuota = false;
        if (isset($where['type']) && $where['type']) {
            $isQuota = true;
            if (count($where) == 2) {
                unset($where['type']);
            }
        }
        $field = $isQuota ? 'stock,quota' : 'stock';
        $product = $this->getModel()->where($where)->field($field)->find();
        if ($product) {
            return $this->getModel()->where($where)->when($isQuota, function ($query) use ($num) {
                $query->dec('quota', $num);
            })->dec($stock, $num)->dec('sum_stock', $num)->inc($sales, $num)->update();
        }
        return true;
    }

    /**
     * 加库存
     * @param array $where
     * @param int $num
     * @param string $stock
     * @param string $sales
     * @return bool|mixed
     */
    public function incStockDecSales(array $where, int $num, string $stock = 'stock', string $sales = 'sales')
    {
        $isQuota = false;
        if (isset($where['type']) && $where['type']) {
            $isQuota = true;
            if (count($where) == 2) {
                unset($where['type']);
            }
        }
        $salesOne = $this->getModel()->where($where)->value($sales);
        if ($salesOne) {
            $salesNum = $num;
            if ($num > $salesOne) {
                $salesNum = $salesOne;
            }
            return $this->getModel()->where($where)->when($isQuota, function ($query) use ($num) {
                $query->inc('quota', $num);
            })->inc($stock, $num)->inc('sum_stock', $num)->dec($sales, $salesNum)->update();
        }
        return true;
    }

    /**
     * 根据条件获取规格value
     * @param array $where
     * @param string $field
     * @param string $key
     * @param bool $search
     * @return array
     */
    public function getColumn(array $where, string $field = '*', string $key = 'suk', bool $search = false)
    {
        if ($search) {
            return $this->search($where)
                ->when(isset($where['store_id']) && $where['store_id'], function ($query) use ($where) {
                    $query->with(['storeBranch' => function ($querys) use ($where) {
                        $querys->where(['store_id' => $where['store_id'], 'product_id' => $where['product_id']]);
                    }]);
                })
                ->column($field, $key);
        } else {
            return $this->getModel()::where($where)
                ->when(isset($where['product_id']) && $where['product_id'], function ($query) use ($where,$field) {
                    if (is_array($where['product_id'])) {
                        $query->whereIn('product_id', $where['product_id']);
                    } else {
                        $query->where('product_id', $where['product_id']);
                    }
                })
                ->column($field, $key);
        }

    }

    /**
     * 根据条件删除规格value
     * @param int $id
     * @param int $type
     * @param array $suk
     * @return bool
     */
    public function del(int $id, int $type, array $suk = [])
    {
        return $this->search(['product_id' => $id, 'type' => $type, 'suk' => $suk])->delete();
    }

    /**
     * 保存数据
     * @param array $data
     * @return mixed|\think\Collection
     * @throws \Exception
     */
    public function saveAll(array $data)
    {
        return $this->getModel()->saveAll($data);
    }

    /**
     * 根据条件获取规格数据列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductAttrValue(array $where)
    {
        return $this->search($where)->order('id asc')->select()->toArray();
    }

    /**
     * 获取属性列表
     * @return mixed
     */
    public function attrValue()
    {
        return $this->search()->field('product_id,sum(sales * price) as val')->with(['product'])->group('product_id')->limit(20)->select()->toArray();
    }

    /**
     * 获取属性库存
     * @param string $unique
     * @return int
     */
    public function uniqueByStock(string $unique)
    {
        return $this->search(['unique' => $unique])->value('stock') ?: 0;
    }

    /**
     * 根据条形码获取一条商品规格信息
     * @param string $bar_code
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAttrByBarCode(string $bar_code)
    {
        return $this->getModel()->where('bar_code', $bar_code)->order('id desc')->find();
    }

    /**
     * 根据规格信息获取商品库存
     * @param array $ids
     * @return array|\think\Model|null
     */
    public function getProductStockByValues(array $ids)
    {
        return $this->getModel()->whereIn('product_id', $ids)->where('type', 0)
            ->field('`product_id` AS `id`, SUM(`stock`) AS `stock`')->group("product_id")->select()->toArray();
    }

    /**
 	* 分组查询
	* @param string $file
	* @param string $group_id
	* @param array $where
	* @param string $having
	* @return array|\crmeb\basic\BaseModel[]|\think\Collection
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function getGroupData(string $file,string $group_id,array $where,string $having = '')
    {
        return $this->getModel()->when($where,function ($query) use ($where) {
            $query->where($where);
        })->field($file)->group($group_id)->when($having,function ($query) use ($having){
            $query->having($having);
        })->select();
    }

	/**
     * 库存警戒查询
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/4/18
     * @param array $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getPolice(array $where)
    {
        return $this->getModel()->when($where, function ($query) use ($where) {
            $query->where($where);
        })->count();
    }

}
