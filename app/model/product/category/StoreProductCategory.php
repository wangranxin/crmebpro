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

namespace app\model\product\category;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 商品分类Model
 * Class StoreProductCategory
 * @package app\model\product\product
 */
class StoreProductCategory extends BaseModel
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
    protected $name = 'store_product_category';

    /**
     * 添加时间获取器
     * @param $value
     * @return false|string
     */
    protected function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

	/**
	 * 父级
	 * @return \think\model\relation\HasOne
	 */
	public function parentCate()
	{
		return $this->hasOne(self::class, 'id', 'pid')->where('is_show', 1);
	}

	/**
	 * 父级
	 * @return \think\model\relation\HasOne
	 */
	public function parentCateName()
	{
		return $this->hasOne(self::class, 'id', 'pid')->field('id,pid,cate_name')->where('is_show', 1)->bind(['one' => 'cate_name']);
	}

	 /**
     * 获取子集分类查询条件
     * @return \think\model\relation\HasMany
     */
    public function AdminChildren()
    {
        return $this->hasMany(self::class, 'pid', 'id')->order('sort DESC,id DESC');
    }

    /**
     * 获取子集分类查询条件
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany(self::class, 'pid', 'id')->where('is_show', 1)->order('sort DESC,id DESC');
    }

	/**
     * ID搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIdAttr($query, $value)
    {
        if (is_array($value)) {
			if ($value) $query->whereIn('id', $value);
        } else {
			if ($value) $query->where('id', $value);
        }
    }

	/**
     * 分类是否显示搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchPidAttr($query, $value, $data)
    {
        if (is_array($value)) {
			if ($value) $query->whereIn('pid', $value);
        } else {
			if ($value !== '') $query->where('pid', $value);
        }
    }

	/**
	* @param $query
	* @param $value
	* @return void
	*/
	public function searchLevelAttr($query, $value){
		if (is_array($value)) {
			if ($value) $query->whereIn('level', $value);
		} else {
			if(in_array($value, [1, -1, 0])) {
                switch ($value) {
                    case -1://所有
                        break;
					case 0://所有一级
						$query->where('level', 0);
						break;
                    case 1://所有二级
                        $query->whereIn('level', [0, 1]);
                        break;
                }
            }
		}
	}


	/**
     * 分类是否显示搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchCateNameAttr($query, $value, $data)
    {
        if ($value !== '') $query->where('cate_name', 'like', '%' . $value . '%');
    }

	/**
     * 类型搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '') $query->where('type', $value);
    }


	/**
     * 关联ID搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchRelationIdAttr($query, $value)
    {
        if ($value !== '') $query->where('relation_id', $value);
    }


    /**
     * 分类是否显示搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsShowAttr($query, $value, $data)
    {
        if ($value !== '') $query->where('is_show', $value);
    }


}
