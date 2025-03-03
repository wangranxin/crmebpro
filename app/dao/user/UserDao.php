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

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\User;
use crmeb\basic\BaseModel;

/**
 * 用户
 * Class UserDao
 * @package app\dao\user
 */
class UserDao extends BaseDao
{

    protected function setModel(): string
    {
        return User::class;
    }

    /**
     * @param array $where
     * @param bool $search
     * @return BaseModel
     * @throws \ReflectionException
     */
    public function search(array $where = [], bool $search = false)
    {
        return parent::search($where, $search)->when(isset($where['label_id']) && $where['label_id'], function ($query) use ($where) {
            $query->whereIn('uid', function ($q) use ($where) {
                if (is_array($where['label_id'])) {
                    $q->name('user_label_relation')->whereIn('label_id', $where['label_id'])->field('uid')->select();
                } else {
                    if (strpos($where['label_id'], ',') !== false) {
                        $q->name('user_label_relation')->whereIn('label_id', explode(',', $where['label_id']))->field('uid')->select();
                    } else {
                        $q->name('user_label_relation')->where('label_id', $where['label_id'])->field('uid')->select();
                    }
                }
            });
        });
    }

    /**
     * 是否存在
     * @param int $uid
     * @return bool
     */
    public function exist(int $uid)
    {
        return !!$this->getModel()->where('uid', $uid)->count();
    }

    /**
     * @param array $where
     * @return mixed
     */
    public function getWithTrashedCount(array $where = [], array $time = [])
    {
        return $this->getModel()->withTrashed()->where($where)->whereTime('add_time', 'between', $time)->count();
    }

    /**
     * 获取删除和没有删除的用户信息
     * @param int $uid
     * @param $field
     * @return mixed
     */
    public function getUserWithTrashedInfo(int $uid, $field = '*')
    {
        return $this->getModel()->withTrashed()->field($field)->find($uid);
    }

    /**
     * 获取用户列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0, $order = ''): array
    {
        return $this->search($where)->field($field)->with(['label'])->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->when($order != '', function ($query) use ($order) {
            $query->order($order);
        })->select()->toArray();
    }

    /**
     * @param array $where
     * @return array|string
     */
    public function getCountList(array $where)
    {
        return $this->getModel()->where($where)->group('uid')->column('count(*) as user_count', 'uid');
    }

    /**
     * 获取特定条件的总数
     * @param array $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getCount(array $where): int
    {
        return $this->getModel()->where($where)->count();
    }

    /**
     * 用户支付成功个数增加
     * @param int $uid
     * @return mixed
     */
    public function incPayCount(int $uid)
    {
        event('user.update', [$uid]);
        return $this->getModel()->where('uid', $uid)->inc('pay_count', 1)->update();
    }

    /**
     * 某个字段累加某个数值
     * @param string $field
     * @param int $num
     */
    public function incField(int $uid, string $field, int $num = 1)
    {
        event('user.update', [$uid]);
        return $this->getModel()->where('uid', $uid)->inc($field, $num)->update();
    }

    /**
     * @param $uid
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLabel($uid, $field = '*')
    {
        return $this->search(['uid' => $uid])->field($field)->with(['label'])->select()->toArray();
    }

    /**
     * 获取分销用户
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAgentUserList(array $where, string $field = '*', int $page = 0, int $limit = 0)
    {
        return $this->search($where)->field($field)->with([
            'extract' => function ($query) {
                $query->field('sum(extract_price + extract_fee) as extract_count_price,count(id) as extract_count_num,uid')->where('status', '1')->group('uid');
            }, 'order' => function ($query) {
                $query->field('sum(pay_price) as order_price,count(id) as order_count,uid')->where('pid', '>=', 0)->where('paid', 1)->where('is_del', 0)->where('is_system_del', 0)->where('refund_status', 'IN', [0, 3])->group('uid');
            }, 'brokerage' => function ($query) {
                $query->field('sum(number) as brokerage_money,uid')->where('status', 1)->where('pm', 1)->group('uid');
            }, 'spreadCount' => function ($query) {
                $query->field('count(`uid`) as spread_count,spread_uid')->group('spread_uid');
            }, 'spreadUser' => function ($query) {
                $query->field('uid,phone,nickname');
            }, 'agentLevel' => function ($query) {
                $query->field('id,name');
            }
        ])->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('uid desc')->select()->toArray();
    }

    /**
     * 获取推广人列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSairList(array $where, string $field = '*', int $page = 0, int $limit = 0)
    {
        return $this->search($where)->field($field)->with([
            'order' => function ($query) {
                $query->field('sum(pay_price) as order_price,count(id) as order_count,uid')->where('paid', 1)->where('refund_status', 0)->group('uid');
            }, 'spreadCount' => function ($query) {
                $query->field('count(`uid`) as spread_count,spread_uid')->group('spread_uid');
            }, 'spreadUser' => function ($query) {
                $query->field('uid,phone,nickname');
            }
        ])->page($page, $limit)->order('uid desc')->select()->toArray();
    }

    /**
     * 获取推广人排行
     * @param array $time
     * @param string $field
     * @param int $page
     * @param int $limit
     */
    public function getAgentRankList(array $time, string $field = '*', int $page = 0, int $limit = 0)
    {
        return $this->getModel()->alias('t0')
            ->field($field)
            ->join('user t1', 't0.uid = t1.spread_uid', 'LEFT')
            ->where('t1.spread_uid', '<>', 0)
            ->order('count desc')
            ->order('t0.uid desc')
            ->where('t1.spread_time', 'BETWEEN', $time)
            ->page($page, $limit)
            ->group('t0.uid')
            ->select()->toArray();
    }

    /**
     * 获取推广员ids
     * @param array $where
     * @return array
     */
    public function getAgentUserIds(array $where)
    {
        return $this->search($where)->column('uid');
    }

    /**
     * 某个条件 用户某个字段总和
     * @param array $where
     * @param string $filed
     * @return float
     */
    public function getWhereSumField(array $where, string $filed)
    {
        return $this->search($where)->sum($filed);
    }

    /**
     * 根据条件查询对应的用户信息以数组形式返回
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getUserInfoArray(array $where, string $field, string $key)
    {
        return $this->search($where)->column($field, $key);
    }

    /**
     * 获取特定时间用户访问量
     * @param $time
     * @param $week
     * @return int
     */
    public function todayLastVisit($time, $week)
    {
        switch ($week) {
            case 1:
                return $this->search(['time' => $time ?: 'today', 'timeKey' => 'last_time'])->count();
            case 2:
                return $this->search(['time' => $time ?: 'week', 'timeKey' => 'last_time'])->count();
        }
    }

    /**
     * 获取特定时间用户访问量
     * @param $time
     * @return int
     */
    public function totalUserCount($time)
    {
        return $this->search(['time' => $time ?: 'today', 'timeKey' => 'add_time'])->count();
    }

    /**
     * 获取特定时间内用户列表
     * @param $starday
     * @param $yesterday
     * @return mixed
     */
    public function userList($starday, $yesterday)
    {
        return $this->getModel()->where('add_time', 'between time', [$starday, $yesterday])
            ->field("FROM_UNIXTIME(add_time,'%Y-%m-%d') as day,count(*) as count")
            ->group("FROM_UNIXTIME(add_time, '%Y%m%d')")
            ->order('add_time asc')
            ->select()->toArray();
    }

    /**
     * 购买量范围的用户数量
     * @param $status
     * @return int
     */
    public function userCount($status)
    {
        switch ($status) {
            case 1:
                return $this->getModel()->where('pay_count', '>', 1)->where('pay_count', '<=', 4)->count();
            case 2:
                return $this->getModel()->where('pay_count', '>', 4)->count();
        }
    }

    /**
     * 获取用户统计数据
     * @param $time
     * @param $type
     * @param $timeType
     * @return mixed
     */
    public function getTrendData($time, $type, $timeType)
    {
        return $this->getModel()->when($type != '', function ($query) use ($type) {
            $query->where('user_type', $type);
        })->where(function ($query) use ($time) {
            if ($time[0] == $time[1]) {
                $query->whereDay('add_time', $time[0]);
            } else {
                $time[1] = date('Y-m-d', strtotime($time[1]) + 86400);
                $query->whereTime('add_time', 'between', $time);
            }
        })->field("FROM_UNIXTIME(add_time,'$timeType') as days,count(uid) as num")->group('days')->select()->toArray();
    }

    /**
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfoList(array $where, $field = "*"): array
    {
        return $this->search($where)->field($field)->select()->toArray();
    }

    /**
     * 获取用户会员数量
     * @param $where (time  type)
     * @return int
     */
    public function getMemberCount($where, int $overdue_time = 0)
    {
        if (!$overdue_time) $overdue_time = time();
        return $this->search($where)->where('is_ever_level', 1)->whereOr(function ($qeury) use ($overdue_time) {
            $qeury->where('is_money_level', '>', 0)->where('overdue_time', '>', $overdue_time);
        })->count();
    }

    public function getOutOne(int $uid, $field = "*")
    {
        return $this->getModel()->where('uid|phone', $uid)->field($field)->find();
    }

    /**
     * 带锁的减余额
     * @param int $uid
     * @param string $incField
     * @param string $inc
     * @param string|null $keyField
     * @param int $acc
     * @param bool $dec_return_false
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/22
     */
    public function bcDecLook(int $uid, string $incField, string $inc, string $keyField = null, int $acc = 2, bool $dec_return_false = true)
    {

        $result = $this->getModel()->where('uid', $uid)->lock(true)->find();

        if (!$result) return false;

        if ($result[$incField] < $inc) {
            if ($dec_return_false) return false;
            $new = 0;
        } else {
            $new = bcsub($result[$incField], $inc, $acc);
        }

        $result->{$incField} = $new;
        return false !== $result->save();
    }

    /**
     * 获取指定分组类型和分组ID数组的用户数量
     *
     * @param int $divisionType 分组类型，1表示部门，其他表示代理商
     * @param array $divisionIds 分组ID数组
     * @return array 返回一个关联数组，键为分组ID，值为该分组下的用户数量
     */
    public function getDivisionCount($divisionType, $divisionIds)
    {
        // 根据分组类型选择查询字段
        $field = $divisionType == 1 ? 'division_id' : 'agent_id';
        return $this->getModel()->where('division_type', (int)$divisionType + 1)
            ->whereIn($field, $divisionIds) // 查询指定分组ID的数据
            ->group($field)->column('COUNT(uid) as count', $field);
    }

}
