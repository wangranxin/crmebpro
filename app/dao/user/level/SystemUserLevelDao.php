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

namespace app\dao\user\level;

use think\Model;
use app\dao\BaseDao;
use think\db\exception\DbException;
use app\model\user\level\SystemUserLevel;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统设置用户等级
 * Class SystemUserLevelDao
 * @package app\dao\user\level
 */
class SystemUserLevelDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return SystemUserLevel::class;
    }

    /**
     * 获取列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', int $page = 0, $limit = 0)
    {
        return $this->getModel()->where($where)->field($field)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('grade asc')->select()->toArray();
    }

    /**
     * 复杂条件获取总数
     * @param array $where
     * @return int
     * @throws DbException
     */
    public function getCount(array $where): int
    {
        return $this->getModel()->where($where)->count();
    }

    /**
     * 获取上一个用户等级
     * @param $grade
     * @param string $field
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getPreLevel($grade, string $field = '*')
    {
        return $this->getModel()->where('grade', '<', $grade)->where('is_del', 0)->field($field)->order('grade desc')->find();
    }

    /**
     * 获取下一个用户等级
     * @param $grade
     * @param string $field
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getNextLevel($grade, string $field = '*')
    {
        return $this->getModel()->where('grade', '>', $grade)->where('is_del', 0)->field($field)->order('grade asc')->find();
    }
}
