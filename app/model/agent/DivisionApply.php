<?php

namespace app\model\agent;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

class DivisionApply extends BaseModel
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
    protected $name = 'division_apply';

    /**
     * 搜索器
     * @param $query
     * @param $value
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function searchUidAttr($query, $value)
    {
        if ($value !== '') $query->where('uid', $value);
    }
}