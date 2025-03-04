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

namespace app\services\user\level;

use app\jobs\user\UserLevelJob;
use app\services\BaseServices;
use app\dao\user\level\SystemUserLevelDao;
use app\services\user\UserServices;
use think\annotation\Inject;

/**
 * 系统设置用户等级
 * Class SystemUserLevelServices
 * @package app\services\user\level
 * @mixin SystemUserLevelDao
 */
class SystemUserLevelServices extends BaseServices
{

    /**
     * @var SystemUserLevelDao
     */
    #[Inject]
    protected SystemUserLevelDao $dao;

    /**
     * 单个等级
     * @param int $id
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLevel(int $id, string $field = '*')
    {
        return $this->dao->getOne(['id' => $id, 'is_del' => 0], $field);
    }

    /**
     * 获取会员等级信息缓存
     * @param int $id
     * @return array|false|mixed|string|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/14
     */
    public function getLevelCache(int $id)
    {
        $level = $this->dao->cacheRemember($id, function () use ($id) {
            $level = $this->getLevel($id);
            if ($level) {
                return $level->toArray();
            } else {
                return null;
            }
        });

        return $level;
    }

    /**
     * 获取某条件等级
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWhereLevel(array $where, string $field = '*')
    {
        return $this->dao->getOne($where, $field);
    }

    /**
     * 获取所有等级列表
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLevelList(array $where, string $field = '*',$is_page = true)
    {
        $where_data = [];
        if (isset($where['is_show']) && $where['is_show'] !== '') $where_data[] = ['is_show', '=', $where['is_show']];
        if (isset($where['title']) && $where['title']) $where_data[] = ['name', 'LIKE', "%$where[title]%"];
        $where_data[] = ['is_del', '=', '0'];
        $page = $limit = 0;
        if($is_page){
            [$page, $limit] = $this->getPageValue();
        }
        $list = $this->dao->getList($where_data, $field ?? '*', $page, $limit);
        foreach ($list as &$item) {
            $item['image'] = set_file_url($item['image']);
            $item['icon'] = set_file_url($item['icon']);
        }
        $count = $this->dao->getCount($where_data);
        return $is_page ? compact('list', 'count') : $list;
    }

    /**
     * 获取条件的会员等级列表
     * @param array $where
     * @param string $field
     */
    public function getWhereLevelList(array $where, string $field = '*')
    {
        if ($where) {
            $whereData = [['is_show', '=', 1], ['is_del', '=', 0], $where];
        } else {
            $whereData = [['is_show', '=', 1], ['is_del', '=', 0]];
        }
        return $this->dao->getList($whereData, $field ?? '*');
    }

    /**
     * 获取一些用户等级名称
     * @param $ids
     * @return array
     */
    public function getUsersLevel($ids)
    {
        return $this->dao->getColumn([['id', 'IN', $ids]], 'name', 'id');
    }

    /**
     * 获取会员等级列表
     * @param int $leval_id
     * @return array
     */
    public function getLevelListAndGrade(int $leval_id = 0, string $field = 'name,discount,image,icon,explain,id,grade,is_forever,valid_date,exp_num')
    {
        $list = $this->dao->getList(['is_del' => 0, 'is_show' => 1], $field);
        if ($list) {
            $listNew = array_combine(array_column($list, 'id'), $list);
            $grade = $listNew[$leval_id]['grade'] ?? 0;
            foreach ($list as &$item) {
                if ($grade < $item['grade'])
                    $item['is_clear'] = true;
                else
                    $item['is_clear'] = false;
                $item['task_list'] = [];
            }
        }
        return $list;
    }

    /**
     * 获取用户等级折扣
     * @param int $uid
     * @param int $id
     * @return null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiscount(int $uid, int $id)
    {
        $discount = null;
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getOne(['uid' => $uid], 'uid,level,level_status');
        //需要会员是激活状态
        if ($id && $userInfo && $userInfo['level_status']) {
            $level = $this->dao->getOne(['id' => $id, 'is_del' => 0, 'is_show' => 1], 'id,discount');
            if (!$level) {//用户存在等级ID 但是被删除或者下架重新检测升级
				UserLevelJob::dispatch([$uid]);
                $level = $this->dao->getOne(['id' => $userInfo['level'], 'is_del' => 0, 'is_show' => 1], 'id,discount');
            }
            $discount = $level['discount'] ?? null;
        }
        return $discount;
    }
}
