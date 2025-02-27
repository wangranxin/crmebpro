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

namespace crmeb\basic;

use crmeb\traits\ModelTrait;
use think\db\Query;
use think\Model;
use crmeb\topthink\HasManyThrough;

/**
 * Class BaseModel
 * @package crmeb\basic
 * @mixin ModelTrait
 * @mixin Query
 */
class BaseModel extends Model
{

    /**
     * 重写一对多关联
     * @param string $model
     * @param string $through
     * @param string $foreignKey
     * @param string $throughKey
     * @param string $localKey
     * @param string $throughPk
     * @return HasManyThrough
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/10/11
     */
    public function hasManyThrough(string $model, string $through, string $foreignKey = '', string $throughKey = '', string $localKey = '', string $throughPk = ''): HasManyThrough
    {
        // 记录当前关联信息
        $model = $this->parseModel($model);
        $through = $this->parseModel($through);
        $localKey = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);
        $throughKey = $throughKey ?: $this->getForeignKey((new $through())->getName());
        $throughPk = $throughPk ?: (new $through())->getPk();

        return new HasManyThrough($this, $model, $through, $foreignKey, $throughKey, $localKey, $throughPk);
    }

}
