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

namespace app\model\other\import;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * Class Import
 * @package app\model\other
 */
class ImportRecord extends BaseModel
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
    protected $name = 'import_record';

    /**
     * 类型搜索器
     * @param $query
     * @param $value
     * @return void
     * User: liusl
     * DateTime: 2024/12/9 下午2:23
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '') {
            if (is_array($value)) {
                $query->whereIn('type', $value);
            } else {
                $query->where('type', $value);
            }
        }
    }

    /**
     * 状态搜索器
     * @param $query
     * @param $value
     * @return void
     * User: liusl
     * DateTime: 2024/12/9 下午2:22
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            if (is_array($value)) {
                $query->whereIn('status', $value);
            } else {
                $query->where('status', $value);
            }
        }
    }

    /**
     * 名称搜索器
     * @param $query
     * @param $value
     * @return void
     * User: liusl
     * DateTime: 2024/12/9 下午2:22
     */
    public function searchNameAttr($query, $value)
    {
        if ($value !== '') {
            $query->whereLike('name', "%{$value}%");
        }
    }

    /**
     * 删除搜索器
     * @param $query
     * @param $value
     * @return void
     * User: liusl
     * DateTime: 2024/12/19 下午4:58
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_del', $value);
        }
    }

}
