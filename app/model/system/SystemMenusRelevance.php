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

namespace app\model\system;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 菜单关联模型
 * Class SystemMenus
 * @package app\model\system
 */
class SystemMenusRelevance extends BaseModel
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
    protected $name = 'system_menus_relevance';

    public function searchMenuIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('menu_id', $value);
        } else {
            if ($value !== '') {
                $query->where('menu_id', $value);
            }
        }
    }

    public function searchKeywordAttr($query, $value)
    {
        if ($value !== '') {
            $query->whereLike('keyword', "%{$value}%");
        }
    }

}
