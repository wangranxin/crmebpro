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

namespace app\model\community;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 社区用户Model
 * Class CommunityUser
 * @package app\model\community
 */
class CommunityUser extends BaseModel
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
    protected $name = 'community_user';

    /**
     * @var bool
     */
    protected $autoWriteTimestamp = false;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    /**
     * 用户关联帖子
     * @return \think\model\relation\HasMany
     * User: liusl
     * DateTime: 2024/8/26 15:44
     */
    public function community()
    {
        $where = [
            'status' => 1,
            'is_del' => 0,
            'is_verify'=>1
        ];
        return $this->hasMany(Community::class, 'relation_id', 'relation_id')->where($where)->field('id,content_type,image,relation_id');
    }

    /**
     * 商户搜索器
     * @param Model $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if (is_array($value)) {
            if ($value) $query->whereIn('type', $value);
        } else {
            if ($value !== '') $query->where('type', $value);
        }
    }

    /**
     * 关联门店ID、供应商ID搜索器
     * @param Model $query
     * @param $value
     */
    public function searchRelationIdAttr($query, $value)
    {
        if (is_array($value)) {
            if ($value) $query->whereIn('relation_id', $value);
        } else {
            if ($value !== '') $query->where('relation_id', $value);
        }
    }

    /**
     * status搜索器
     * @param Model $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') $query->where('status', $value);
    }

    /**
     * is_del搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') $query->where('is_del', $value);
    }

    /**
     * 用户ID搜索器
     * @param $query
     * @param $value
     * @return void
     * User: liusl
     * DateTime: 2024/8/26 15:44
     */
    public function searchNotRelationIdAttr($query, $value)
    {
        if (is_array($value)) {
            if ($value) $query->whereNotIn('relation_id', $value);
        } else {
            if ($value !== '') $query->where('relation_id', '<>', $value);
        }
    }


}
