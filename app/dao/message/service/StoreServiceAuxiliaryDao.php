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

namespace app\dao\message\service;


use app\dao\other\queue\QueueAuxiliaryDao;
use crmeb\basic\BaseModel;

/**
 * 客服辅助表
 * Class StoreServiceAuxiliaryDao
 * @package app\dao\message\service
 */
class StoreServiceAuxiliaryDao extends QueueAuxiliaryDao
{

    /**
     * 搜索
     * @param array $where
     * @param bool $search
     * @return BaseModel
     * @throws \ReflectionException
     */
    public function search(array $where = [], bool $search = true)
    {
        return parent::search($where, $search)->where('type', 0);
    }

}

