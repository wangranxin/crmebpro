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

namespace app\dao\activity\bargain;

use app\dao\BaseDao;
use app\model\activity\bargain\StoreBargain;
use crmeb\basic\BaseModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 砍价商品
 * Class StoreBargainDao
 * @package app\dao\activity\bargain
 */
class StoreBargainDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreBargain::class;
    }

    /**
     * @param array $where
     * @param bool $search
     * @return BaseModel
     * @throws \ReflectionException
     */
    public function search(array $where = [], bool $search = true)
    {
        return parent::search($where, false)
        	->when(isset($where['pinkIngTime']), function ($query) use ($where) {
				$time = time();
				[$startTime, $stopTime] = is_array($where['pinkIngTime']) ? $where['pinkIngTime'] : [$time, $time];
				$query->where('start_time', '<=', $startTime)->where('stop_time', '>=', $stopTime);
			})->when(isset($where['start_status']) && $where['start_status'] !== '', function ($query) use ($where) {
                $time = time();
                switch ($where['start_status']) {
                    case -1:
                        $query->where('stop_time', '<', $time);
                        break;
                    case 0:
                        $query->where('start_time', '>', $time);
                        break;
                    case 1:
                        $query->where('start_time', '<=', $time)->where('stop_time', '>=', $time);
                        break;
                }
            });
    }

    /**
     * 获取砍价列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException|\ReflectionException
     */
    public function getList(array $where, int $page = 0, int $limit = 0)
    {
        return $this->search($where)->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 获取活动开启中的砍价id以数组形式返回
     * @param array $ids
     * @param array $field
     * @return array
     */
    public function getBargainIdsArray(array $ids, array $field = [])
    {
        return $this->search(['is_del' => 0, 'status' => 1])->where('start_time', '<=', time())
            ->where('stop_time', '>=', time())->whereIn('product_id', $ids)->column(implode(',', $field), 'product_id');
    }

    /**
     * 根据id获取砍价数据
     * @param array $ids
     * @param string $field
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function idByBargainList(array $ids, string $field)
    {
        return $this->getModel()->whereIn('id', $ids)->field($field)->select()->toArray();
    }

	/**
 	* 获取一条有效的砍价商品
	* @param int $id
	* @param string $field
	* @return array|mixed
	* @throws DataNotFoundException
	* @throws DbException
	* @throws ModelNotFoundException
	* @throws \ReflectionException
	*/
	public function validProduct(int $id, string $field = '*')
    {
        $where = ['is_show' => 1, 'is_del' => 0, 'status' => 1,'pinkIngTime' => true];
        return $this->search($where)->where('id', $id)->where('quota', '>', 0)->field($field)->order('sort DESC,id DES')->find();
    }

    /**
 	* 正在开启的砍价活动
	* @param int $status
	* @return BaseModel
	*/
    public function validWhere(int $status = 1)
    {
        return $this->getModel()->where('is_del', 0)->where('status', $status)->where('start_time', '<', time())->where('stop_time', '>', time());

    }

    /**
 	* 砍价列表
	* @param array $where
	* @param string $field
	* @param int $page
	* @param int $limit
	* @return array
	* @throws DataNotFoundException
	* @throws DbException
	* @throws ModelNotFoundException
	* @throws \ReflectionException
	*/
    public function bargainList(array $where = [],string $field = '*', int $page = 0, int $limit = 0)
    {
        return $this->search(['is_del' => 0, 'status' => 1])->where($where)->field($field)
            ->where('start_time', '<=', time())
            ->where('stop_time', '>=', time())
            ->where('product_id', 'IN', function ($query) {
                $query->name('store_product')->where('is_del', 0)->field('id');
            })->with('product')
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->when(!$page && $limit, function ($query) use ($limit) {
                $query->limit($limit);
            })->order('sort DESC,id DESC')->select()->toArray();
    }

    /**
     * 修改砍价状态
     * @param int $id
     * @param string $field
     * @return mixed
     */
    public function addBargain(int $id, string $field = 'look')
    {
        return $this->getModel()->where('id', $id)->inc($field, 1)->update();
    }
}
