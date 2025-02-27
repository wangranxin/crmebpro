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

namespace app\services\community;

use app\dao\community\CommunityRecordDao;
use app\services\BaseServices;
use think\annotation\Inject;


/**
 * 社区记录
 * Class CommunityRecordServices
 * @package app\services\community
 * @mixin CommunityRecordDao
 */
class CommunityRecordServices extends BaseServices
{
    /**
     * @var CommunityRecordDao
     */
    #[Inject]
    protected CommunityRecordDao $dao;

    //点赞
    const SET_TYPE_LIKE = 1;
    //评论
    const SET_TYPE_COMMENT = 2;
    //关注
    const SET_TYPE_FOLLOW = 3;


    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0)
    {
        if (!$limit) {
            [$page, $limit] = $this->getPageValue();
        }
        $list = $this->dao->search($where)
            ->when($limit && $page, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })
            ->when($limit && !$page, function ($query) use ($limit) {
                $query->limit($limit);
            })
            ->field($field)
            ->order('add_time DESC')
            ->select()
            ->toArray();

        $comment_ids = array_unique(array_column($list, 'link_id'));

        $communityServices = app()->make(CommunityServices::class);
        $communityUserServices = app()->make(CommunityUserServices::class);

        $comment_list = $communityServices->getColumn(['id' => $comment_ids], 'image,content_type', 'id', true);
        foreach ($list as &$item) {
            $item['image'] = $comment_list[$item['link_id']]['image'] ?? '';
            $item['content_type'] = $comment_list[$item['link_id']]['content_type'] ?? '';
            $userFormat = $communityUserServices->getUserFormat($item['relation_id'] ? 2 : 0, $item['relation_id']);
            $item['relation_author'] = $userFormat['author'];
            $item['relation_author_image'] = $userFormat['author_image'];
            $item['time_text'] = timeConverter($item['add_time']);
        }
        return $list;
    }

    /**
     * 点赞评论关注是否查看
     * @param $uid
     * @return bool[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/1/2 上午11:46
     */
    public function isViewed($uid)
    {
        $where = [
            'uid' => $uid,
            'is_viewed' => 0
        ];
        $likeViewed = $this->dao->get($where + ['type' => self::SET_TYPE_LIKE]);
        $commentViewed = $this->dao->get($where + ['type' => self::SET_TYPE_COMMENT]);
        $followViewed = $this->dao->get($where + ['type' => self::SET_TYPE_FOLLOW]);
        return [
            'like' => $likeViewed ? 1 : 0,
            'comment' => $commentViewed ? 1 : 0,
            'follow' => $followViewed ? 1 : 0
        ];
    }
}
