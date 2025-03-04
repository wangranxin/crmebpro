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

namespace app\dao\wechat;

use crmeb\basic\BaseModel;
use think\model;
use app\dao\BaseDao;
use app\model\wechat\WechatKey;

/**
 *
 * Class UserWechatUserDao
 * @package app\dao\user
 */
class WechatKeyDao extends BaseDao
{
    protected function setModel(): string
    {
        return WechatKey::class;
    }

    /**
     * 搜索器
     * @param array $where
     * @param bool $search
     * @return BaseModel
     * @throws \ReflectionException
     */
    public function search(array $where = [],bool $search = false)
    {
        return parent::search($where,$search)->when(isset($where['id']), function ($query) use ($where) {
            $query->where('id', $where['id']);
        });
    }

}
