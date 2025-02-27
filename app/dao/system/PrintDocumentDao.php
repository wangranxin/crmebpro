<?php

namespace app\dao\system;

use app\dao\BaseDao;
use app\model\system\PrintDocument;

class PrintDocumentDao extends BaseDao
{
    protected function setModel(): string
    {
        return PrintDocument::class;
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
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->where('print_name', 'like', '%' . $where['keyword'] . '%');
            })->when(isset($where['type']) && $where['type'], function ($query) use ($where) {
                $query->where('type', $where['type']);
            })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', $where['status']);
            })->when(isset($where['print_type']) && $where['print_type'] !== ''  && $where['print_type'] !== 0, function ($query) use ($where) {
                $query->where('print_type', $where['print_type']);
            })->when(isset($where['supplier_id']) && $where['supplier_id'] !== '', function ($query) use ($where) {
                $query->where('supplier_id', $where['supplier_id']);
            });
    }

    /**
     * 获取打印机列表
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
    public function printList($where, $page = 0, $limit = 0)
    {
        return $this->getConditionModel($where)->order('id desc')->when($page != 0, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->select()->toArray();
    }

    /**
     * 获取打印机数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function printCount($where)
    {
        return $this->getConditionModel($where)->count();
    }
}
