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

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserStoreOrderDao;
use think\annotation\Inject;

/**
 *
 * Class UserStoreOrderServices
 * @package app\services\user
 * @mixin UserStoreOrderDao
 */
class UserStoreOrderServices extends BaseServices
{

    /**
     * @var UserStoreOrderDao
     */
    #[Inject]
    protected UserStoreOrderDao $dao;

    /**
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/18
     * @param $uid
     * @param string $orderBy
     * @param string $keyword
     * @param array $time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserSpreadCountList($uid, $orderBy = '', $keyword = '', $time = [])
    {
        if ($orderBy === '') {
            $orderBy = 'u.add_time desc';
        }
        $where = [];
        $where[] = ['u.uid', 'IN', $uid];
        if (strlen(trim($keyword))) {
            $where[] = ['u.nickname|u.phone', 'LIKE', "%$keyword%"];
        }
        if ($time[0] || $time[1]) {
            $where[] = ['spread_time', 'between', $time];
        }
        [$page, $limit] = $this->getPageValue();
        $field = "u.uid,u.nickname,u.avatar,from_unixtime(u.add_time,'%Y/%m/%d') as time,u.spread_time,u.spread_count as childCount,p.orderCount,p.numberCount";
        $list = $this->dao->getUserSpreadCountList($where, $field, $orderBy, $page, $limit);
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        foreach ($list as &$item) {
            $item['childCount'] = count($userServices->getUserSpredadUids($item['uid'], 1)) ?? 0;
        }
        return $list;
    }
}
