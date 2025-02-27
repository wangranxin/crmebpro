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

namespace app\dao\community;

use app\dao\BaseDao;
use app\model\community\CommunityComment;
use crmeb\basic\BaseModel;

/**
 * 社区评论
 * Class CommunityCommentDao
 * @package app\dao\community
 */
class CommunityCommentDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return CommunityComment::class;
    }

    /**
     * @param array $where
     * @param bool $search
     * @return BaseModel
     * @throws \ReflectionException
     */
    public function search(array $where = [], bool $search = false)
    {
        $keyword = $where['keyword'] ?? '';
        $fieldKey = $where['field_key'] ?? '';
        $fieldKey = in_array($fieldKey, ['user', 'community', 'comment', 'id']) ? $fieldKey : '';
        /** field_key
         *  user
         * community
         * comment
         * id
         */
        return parent::search($where, $search)
            ->when(isset($where['author_uid']), function ($query) use ($where) {
                if ($where['author_uid'] == 0) {
                    $query->where('is_show', 1);
                }

            })
            ->when($keyword !== '' && $fieldKey !== '', function ($query) use ($fieldKey, $keyword) {
                switch ($fieldKey) {
                    case 'user':
                        $query->whereIn('uid', function ($query) use ($keyword) {
                            $query->name('user')->whereLike('nickname', '%' . $keyword . '%')->field('uid');
                        });
                        break;
                    case 'community':
                        $query->whereIn('community_id', function ($query) use ($keyword) {
                            $query->name('community')->whereLike('title|content', '%' . $keyword . '%')->field('id');
                        });
                        break;
                    case 'comment':
                        $query->whereLike('content', '%' . $keyword . '%');
                        break;
                    case 'id':
                        $query->where('id', $keyword);
                }

            });
    }

    public function count($where = []): int
    {
        return $this->search($where)->count();
    }

    /**
     * 评论列表
     * @param array $where
     * @param string $field
     * @param array $with
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', array $with = [], int $page = 0, int $limit = 0, $order = 'add_time DESC')
    {
        return $this->search($where)->field($field)
            ->when($with, function ($query) use ($with) {
                $query->with($with);
            })->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order($order)->select()->toArray();
    }

}
