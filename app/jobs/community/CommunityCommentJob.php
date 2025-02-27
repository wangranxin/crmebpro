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

namespace app\jobs\community;


use app\services\community\CommunityCommentServices;
use app\services\community\CommunityRecordServices;
use app\services\community\CommunityServices;
use app\services\community\CommunityUserServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 社区帖子评论
 * Class CommunityJob
 * @package app\jobs\community
 */
class CommunityCommentJob extends BaseJobs
{
    use QueueTrait;


    /**
     * 用户评论数数据矫正
     * @param $id
     * @return true
     * User: liusl
     * DateTime: 2024/9/10 11:08
     */
    public function communityCommentSync($id)
    {
        try {
            $communityCommentServices = app()->make(CommunityCommentServices::class);
            $communityCommentServices->syncCommentNum($id);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '用户评论数数据矫正失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

    public function communityMessage($id)
    {
        try {
            $communityServices = app()->make(CommunityServices::class);
            $communityCommentServices = app()->make(CommunityCommentServices::class);
            $communityRecordServices = app()->make(CommunityRecordServices::class);
            $info = $communityCommentServices->get(['id' => $id, 'is_verify' => 1, 'is_show' => 1, 'is_del' => 0]);
            if ($info) {
                $relation_id = $communityServices->value(['id' => $info['community_id']], 'relation_id');
                $communityRecordServices->save([
                    'uid' => $info['is_reply'] == 1 ? $relation_id : ($info['reply_id'] ? $info['reply_uid'] : $info['comment_reply_uid']),
                    'relation_id' => $info['uid'],
                    'type' => CommunityRecordServices::SET_TYPE_COMMENT,
                    'link_id' => $info['community_id'],
                    'comment_id' => $info['id'],
                    'comment_type' => $info['is_reply'] == 1 ? 1 : 2,
                    'content' => $info['content'],
                    'add_time' => time()
                ]);
            }
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '新增帖子评论消息记录失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }
}
