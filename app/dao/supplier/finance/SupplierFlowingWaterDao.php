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

namespace app\dao\supplier\finance;


use app\dao\BaseDao;
use app\model\supplier\finance\SupplierFlowingWater;

/**
 * 供应商流水
 * Class SupplierFlowingWaterDao
 * @package app\dao\supplier\finance
 */
class SupplierFlowingWaterDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return SupplierFlowingWater::class;
    }


    /**
     * 获取供应商流水列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0, array $with = [])
    {
        return $this->search($where, false)
            ->when($with, function ($query) use ($with) {
                $query->with($with);
            })->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->field($field)->order('id desc')->select()->toArray();
    }

    /**
     * 搜索
     * @param array $where
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    public function search(array $where = [], bool $search = false)
    {
        return parent::search($where, $search)
            ->when(isset($where['type']) && $where['type'] !== '', function ($query) use ($where) {
                $query->where('type', $where['type']);
            })->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->where(function ($que) use ($where) {
                    $que->whereLike('order_id', '%' . $where['keyword'] . '%')->whereOr('uid', 'in', function ($q) use ($where) {
                        $q->name('user')->whereLike('nickname|uid', '%' . $where['keyword'] . '%')->field(['uid'])->select();
                    });
                });
            });
    }

    /**
     * 供应商账单
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFundRecord(array $where = [], int $page = 0, int $limit = 0)
    {
        $time = isset($where['status']) && $where['status'] == 1 ? 'finish_time' : 'add_time';
        $model = parent::search($where, false)
            ->when(isset($where['timeType']) && $where['timeType'] !== '', function ($query) use ($where, $time) {
                $timeUnix = '%Y-%m-%d';
                switch ($where['timeType']) {
                    case "day" :
                        $timeUnix = "%Y-%m-%d";
                        break;
                    case "week" :
                        $timeUnix = "%Y-%u";
                        break;
                    case "month" :
                        $timeUnix = "%Y-%m";
                        break;
                }

                $query->field("FROM_UNIXTIME($time,'$timeUnix') as day,sum(if(pm = 1,number,0)) as income_num,sum(if(pm = 0,number,0)) as exp_num,add_time,group_concat(id) as ids");
                $query->group("FROM_UNIXTIME($time, '$timeUnix')");
            });
        $count = $model->count();
        $order = $time . ' desc';
        $list = $model->when($page && $limit, function ($query) use ($page, $limit, $time) {
            $query->page($page, $limit);
        })->order($order)->select()->toArray();
        return compact('list', 'count');
    }

    /**
     * 获取一段时间订单统计数量、金额
     * @param array $where
     * @param array $time
     * @param string $timeType
     * @param string $countField
     * @param string $sumField
     * @param string $groupField
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderAddTimeList(array $where, array $time, string $timeType = "week", string $countField = '*', string $sumField = 'number', string $groupField = 'add_time')
    {
        return parent::search($where, false)
            ->where(isset($where['timekey']) && $where['timekey'] ? $where['timekey'] : 'add_time', 'between time', $time)
            ->when($timeType, function ($query) use ($timeType, $countField, $sumField, $groupField) {
                switch ($timeType) {
                    case "hour":
                        $timeUnix = "%H";
                        break;
                    case "day" :
                        $timeUnix = "%Y-%m-%d";
                        break;
                    case "week" :
                        $timeUnix = "%Y-%w";
                        break;
                    case "month" :
                        $timeUnix = "%Y-%d";
                        break;
                    case "weekly" :
                        $timeUnix = "%W";
                        break;
                    case "year" :
                        $timeUnix = "%Y-%m";
                        break;
                    default:
                        $timeUnix = "%m-%d";
                        break;
                }
                $query->field("FROM_UNIXTIME(`" . $groupField . "`,'$timeUnix') as day,count(" . $countField . ") as count,sum(`" . $sumField . "`) as price");
                $query->group('day');
            })->order('add_time asc')->select()->toArray();
    }
}
