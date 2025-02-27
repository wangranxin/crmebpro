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

namespace app\dao\activity\bargain;

use app\dao\BaseDao;
use app\model\activity\bargain\StoreBargainUser;
use crmeb\basic\BaseModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 参与砍价
 * Class StoreBargainUserDao
 * @package app\dao\activity\bargain
 */
class StoreBargainUserDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreBargainUser::class;
    }

    /**
     * 获取帮砍数量
     * @param array $where
     * @return array
     */
    public function getAllCount(array $where = [])
    {
        return $this->getModel()->where($where)->group('bargain_id')->column('count(*)', 'bargain_id');
    }

    /**
     * 获取砍价表ID
     * @param int $bargainId $bargainId 砍价商品
     * @param int $bargainUserUid $bargainUserUid  开启砍价用户编号
     * @return mixed
     */
    public function getBargainUserTableId(int $bargainId = 0, int $bargainUserUid = 0)
    {
        return $this->value(['bargain_id' => $bargainId, 'uid' => $bargainUserUid, 'is_del' => 0, 'status' => 1], 'id') ?? 0;
    }

    /**
     * 获取用户砍价列表
     * @param int $bargainUserUid
     * @param int $page
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function userAll(int $bargainUserUid, int $page = 0, int $limit = 0)
    {
        return $this->search(['uid' => $bargainUserUid, 'is_del' => 0])->with('getBargain')->order('add_time DESC,id DESC')->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->select()->toArray();
    }

    /**
     * 获取砍价状态
     * @param $bargainId
     * @param $uid
     * @return mixed
     */
    public function getBargainUserStatus($bargainId, $uid)
    {
        return $this->search(['bargain_id' => $bargainId, 'uid' => $uid])->order('add_time DESC')->value('status');
    }


    /**
     * 修改砍价状态
     * @param int $id
     * @param int $status
     * @return BaseModel
     */
    public function updateBargainStatus(int $id, int $status = 3)
    {
        return $this->getModel()->where('id', $id)->where('status', 1)->update(['status' => $status]);
    }

    /**
     * 砍价列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function bargainUserList(array $where, int $page = 0, int $limit = 0): array
    {
        return $this->search($where)->with(['getBargain', 'getUser'])->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('add_time desc')->select()->toArray();
    }
}
