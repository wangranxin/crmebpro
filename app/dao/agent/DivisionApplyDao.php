<?php

namespace app\dao\agent;

use app\dao\BaseDao;
use app\model\agent\DivisionApply;

class DivisionApplyDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return DivisionApply::class;
    }

    /**
     * 条件查询
     * @param $where
     * @return \crmeb\basic\BaseModel
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function getConditionModel($where)
    {
        return $this->getModel()->where('is_del', 0)
            ->when($where['division_id'] != 0, function ($query) use ($where) {
                $query->where('division_id', $where['division_id']);
            })->when($where['status'] !== '' && $where['status'] !== 'all', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })->when($where['keyword'] !== '', function ($query) use ($where) {
                $query->whereLike('uid|agent_name|name|phone', $where['keyword']);
            });
    }

    /**
     * 获取申请列表
     * @param $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyList($where, $page = 0, $limit = 0)
    {
        return $this->getConditionModel($where)->order('id desc')->when($page != 0, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->select()->toArray();
    }

    /**
     * 获取申请数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyCount($where)
    {
        return $this->getConditionModel($where)->count();
    }
}