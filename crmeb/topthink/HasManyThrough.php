<?php
/**
 *  +----------------------------------------------------------------------
 *  | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 *  +----------------------------------------------------------------------
 *  | Author: CRMEB Team <admin@crmeb.com>
 *  +----------------------------------------------------------------------
 */

namespace crmeb\topthink;

use Closure;

/**
 * 远程一对多关联
 * Class HasManyThrough
 * @author 等风来
 * @email 136327134@qq.com
 * @date 2023/10/11
 * @package crmeb\topthink
 */
class HasManyThrough extends \think\model\relation\HasManyThrough
{

    /**
     * @param array $where
     * @param string $key
     * @param array $subRelation
     * @param Closure|null $closure
     * @param array $cache
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/10/11
     */
    protected function eagerlyWhere(array $where, string $key, array $subRelation = [], Closure $closure = null, array $cache = []): array
    {
        // 预载入关联查询 支持嵌套预载入
        $throughList = $this->through->where($where)->select();
        $keys = $throughList->column($this->throughPk, $this->throughPk);

        if ($closure) {
            $this->baseQuery = true;
            $closure($this->query);
        }

        $throughKey = $this->throughKey;

        if ($this->baseQuery) {
            $throughKey = $this->query->getTable() . '.' . $this->throughKey;
        }

        $withLimit = $this->query->getOptions('limit');
        if ($withLimit) {
            $this->query->removeOption('limit');
        }

        $list = $this->query
            ->where($throughKey, 'in', $keys)
            ->cache($cache[0] ?? false, $cache[1] ?? null, $cache[2] ?? null)
            ->select();

        // 组装模型数据
        $data = [];
        $keys = $throughList->column($this->foreignKey, $this->throughPk);

        foreach ($list as $set) {
            $key = $keys[$set->{$this->throughKey}];

            if ($withLimit && isset($data[$key]) && count($data[$key]) >= $withLimit) {
                continue;
            }

            $data[$key][] = $set;
        }

        return $data;
    }
}
