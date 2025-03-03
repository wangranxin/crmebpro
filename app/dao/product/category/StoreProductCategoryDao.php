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

namespace app\dao\product\category;

use app\dao\BaseDao;
use app\model\product\category\StoreProductCategory;

/**
 * Class StoreProductCategoryDao
 * @package app\dao\product\product
 */
class StoreProductCategoryDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProductCategory::class;
    }

    /**
 	* 获取分类列表
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
    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0, array $with = ['children'])
    {
        return $this->search($where)->field($field)
			->when($with, function ($query) use ($with) {
				$query->with($with);
			})->when($page && $limit, function ($query) use($page, $limit) {
				$query->page($page, $limit);
			})->order('sort desc,id desc')->select()->toArray();
    }

    /**
     *
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTierList(array $where = [], array $field = ['*'])
    {
        return $this->search($where)->field($field)->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 添加修改选择上级分类列表
     * @param array $where
     * @return array
     */
    public function getMenus(array $where)
    {
        return $this->search($where)->order('sort desc,id desc')->column('cate_name,id');
    }

    /**
     * 根据id获取分类
     * @param string $cateIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCateArray(string $cateIds)
    {
        return $this->search(['id' => $cateIds])->field('cate_name,id')->select()->toArray();
    }

    /**
 	* 前端分类页面分离列表
	* @param string $field
	* @return array
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function getCategory(string $field = '*')
    {
        return $this->getModel()->field($field)->with('children')->where('is_show', 1)->where('pid', 0)->order('sort desc,id desc')->hidden(['add_time', 'is_show', 'sort', 'children.sort', 'children.add_time', 'children.pid', 'children.is_show'])->select()->toArray();
    }

    /**
     * 根据分类id获取上级id
     * @param array $cateId
     * @return array
     */
    public function cateIdByPid(array $cateId)
    {
        return $this->getModel()->whereIn('id', $cateId)->column('pid');
    }

    /**
     * 获取首页展示的二级分类  排序默认降序
     * @param int $limit
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function byIndexList($limit = 4, $field = 'id,cate_name,pid,pic')
    {
        return $this->getModel()->where('pid', '>', 0)->where('is_show', 1)->field($field)->order('sort DESC')->limit($limit)->select()->toArray();
    }

	/**
     * 获取一级分类和二级分类组成的集合
     * @param $cateId
     * @return mixed
     */
    public function getCateParentAndChildName(string $cateId)
    {
        return $this->getModel()->where('id', 'IN', $cateId)->field('cate_name as two,id,pid')
			->with(['parentCateName'])->select()->toArray();
    }

    /**
     * 按照个数获取一级分类下有商品的分类ID
     * @param $page
     * @param $limit
     * @return array
     */
    public function getCid($page, $limit)
    {
        return $this->getModel()
            ->where('is_show', 1)
            ->where('pid', 0)
            ->where('id', 'in', function ($query) {
                $query->name('store_product_relation')->where('type', 1)->where('status', 1)->group('relation_pid')->field('relation_pid')->select()->toArray();
            })
            ->page($page, $limit)
            ->order('sort DESC,id DESC')
            ->select()->toArray();
    }

    /**
     * 按照个数获取一级分类下有商品的分类个数
     * @param $page
     * @param $limit
     * @return int
     */
    public function getCidCount()
    {
        return $this->getModel()
            ->where('is_show', 1)
            ->where('pid', 0)
            ->where('id', 'in', function ($query) {
                $query->name('store_product_relation')->where('type', 1)->where('status', 1)->group('relation_pid')->field('relation_pid')->select()->toArray();
            })
            ->count();
    }

    /**
     * 通过分类id 获取（自己以及下级）的所有分类
     * @param int $id
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllById(int $id, string $field = 'id')
    {
        return $this->getModel()->where(function ($query) use ($id) {
            $query->where(function ($q) use ($id) {
                $q->where('id', $id)->whereOr('pid', $id);
            });
        })->where('is_show', 1)->field($field)->select()->toArray();
    }

    /**
     * 通过分类ids 获取（自己以及下级）的所有分类
     * @param int $ids
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllByIds(array $ids, string $field = 'id')
    {
        return $this->getModel()->where(function ($query) use ($ids) {
            $query->where(function ($q) use ($ids) {
                $q->where('id', 'in', $ids)->whereOr('pid', 'in', $ids);
            });
        })->where('is_show', 1)->field($field)->select()->toArray();
    }

    /**
     * 可以搜索的获取所有二级分类
     * @param array $where
     * @param string $field
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getALlByIndex(array $where, string $field = 'id,cate_name,pid,pic', int $limit = 0)
    {
		$pid = $where['pid'] ?? -1;
		$where['is_show'] = 1;
        return $this->search([])->field($field)
        	->when((int)$pid > -1, function ($query) use ($pid) {
                $query->where('pid', $pid);
            })->when(isset($where['name']) && $where['name'], function ($query) use ($where) {
                $query->whereLike('id|cate_name', '%' . $where['name'] . '%');
            })->when($limit > 0, function ($query) use ($limit) {
                $query->limit($limit);
            })->order('sort desc,id desc')->select()->toArray();
    }
}
