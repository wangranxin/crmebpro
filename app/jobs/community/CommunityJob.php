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


use app\services\community\CommunityCacheServices;
use app\services\community\CommunityCommentServices;
use app\services\community\CommunityRecordServices;
use app\services\community\CommunityRelevanceServices;
use app\services\community\CommunityServices;
use app\services\community\CommunityTopicServices;
use app\services\community\CommunityUserServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 社区帖子
 * Class CommunityJob
 * @package app\jobs\community
 */
class CommunityJob extends BaseJobs
{
    use QueueTrait;


    /**
     * 用户发帖数数据矫正
     * @param $id
     * @return true
     * User: liusl
     * DateTime: 2024/9/9 17:56
     */
    public function communityUserSync(int $id)
    {
        try {
            /** @var CommunityUserServices $communityUserServices */
            $communityUserServices = app()->make(CommunityUserServices::class);
            $communityUserServices->syncUserNum($id);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '用户发帖数数据矫正:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

    /**
     * 评价同步社区
     * @param $data
     * @return true
     * User: liusl
     * DateTime: 2024/8/27 12:15
     */
    public function computedSync(array $data)
    {
        if (!$data) return true;
        try {
            /** @var CommunityServices $communityServices */
            $communityServices = app()->make(CommunityServices::class);
            $data['image'] = $data['slider_image'][0] ?? '';
            $data['slider_image'] = json_encode($data['slider_image']);
            $data['is_verify'] = sys_config('community_verify', 1) ? 0 : 1;
            $data['topic_id'] = [];
            $communityServices->saveData($data);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '商品评价同步到社区失败,错误原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

    /**
     * 删除帖子后续
     * @param $id
     * @return true
     * User: liusl
     * DateTime: 2024/8/28 16:16
     */
    public function communityDelete(int $id)
    {
        if (!$id) return true;
        try {
            /** @var CommunityServices $communityServices */
            $communityServices = app()->make(CommunityServices::class);
            $communityServices->deleteFollow($id);
//            //用户发帖数数据矫正
//            $this->communityUserSync($id);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '帖子删除错误,错误原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

    /**
     * 帖子评论删除后续操作
     * @param $id
     * @return true
     * User: liusl
     * DateTime: 2024/8/28 16:18
     */
    public function CommunityCommentDelete(int $id)
    {
        if (!$id) return true;
        try {
            /** @var CommunityCommentServices $communityCommentServices */
            $communityCommentServices = app()->make(CommunityCommentServices::class);
            $communityCommentServices->deleteFollow($id);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '帖子评论删除错误,错误原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }


    /**
     * 话题对应帖子数矫正
     * @param $id
     * @return true
     * User: liusl
     * DateTime: 2024/11/11 17:58
     */
    public function communityTopicSync(int $id)
    {
        try {
            /** @var CommunityServices $communityServices */
            $communityServices = app()->make(CommunityServices::class);

            $communityServices->syncTopicNum($id);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '话题帖子数矫正:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

    /**
     * @return void
     * User: liusl
     * DateTime: 2025/1/2 下午4:53
     */
    public function communityTopicStatus(int $id)
    {
        try {
            app()->make(CommunityTopicServices::class)->setStatus($id);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '用户发贴新增话题:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

    /**
     * 发帖增加经验积分
     * @param $id
     * @return void
     * User: liusl
     * DateTime: 2024/12/3 上午11:03
     */
    public function communityIncome(int $id)
    {
        try {
            $communityServices = app()->make(CommunityServices::class);
            $communityServices->giveIncome($id);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '发帖增加经验积分:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

    /**
     * 帖子点赞取消
     * @param int $id
     * @param int $type 1帖子,2评论
     * @param int $status 1点赞 2取消
     * @return true
     * User: liusl
     * DateTime: 2024/12/5 上午11:41
     */
    public function communityLike($info, int $uid, int $type, int $status)
    {
        try {
            $communityUserServices = app()->make(CommunityUserServices::class);
            $communityCacheServices = app()->make(CommunityCacheServices::class);
            $communityRecordServices = app()->make(CommunityRecordServices::class);
            $communityServices = app()->make(CommunityServices::class);
            $communityCommentServices = app()->make(CommunityCommentServices::class);
            if ($type == 1) {
                if ($status == 1) {
                    //帖子点赞数
                    $communityServices->incUpdate(['id' => $info['id']], 'like_num');
                    //作者获得点赞数
                    $communityUserServices->incUpdate(['relation_id' => $info['relation_id']], 'like_num');
                    //点赞消息记录
                    if (!$communityRecordServices->get([
                        'uid' => $info['relation_id'],
                        'relation_id' => $uid,
                        'type' => CommunityRecordServices::SET_TYPE_LIKE,
                        'link_id' => $info['id'],
                    ])) {
                        $communityRecordServices->save([
                            'uid' => $info['relation_id'],
                            'relation_id' => $uid,
                            'type' => CommunityRecordServices::SET_TYPE_LIKE,
                            'link_id' => $info['id'],
                            'comment_type' => 1,
                            'add_time' => time()
                        ]);
                    }
                } else {
                    $communityServices->decUpdate(['id' => $info['id']], 'like_num');
                    $communityUserServices->decUpdate(['relation_id' => $info['relation_id']], 'like_num');
                }
                $communityCacheServices->setLike($info['id'], $uid, $status);
            } else {
                if ($status == 1) {
                    //帖子点赞数
                    $communityCommentServices->incUpdate(['id' => $info['id']], 'like_num');
                    if (!$communityRecordServices->get([
                        'uid' => $info['uid'],
                        'relation_id' => $uid,
                        'type' => CommunityRecordServices::SET_TYPE_LIKE,
                        'comment_type' => 2,
                        'link_id' => $info['community_id'],
                        'comment_id' => $info['id'],
                    ])) {
                        //点赞消息记录
                        $communityRecordServices->save([
                            'uid' => $info['uid'],
                            'relation_id' => $uid,
                            'type' => CommunityRecordServices::SET_TYPE_LIKE,
                            'comment_type' => 2,
                            'link_id' => $info['community_id'],
                            'comment_id' => $info['id'],
                            'add_time' => time()
                        ]);
                    }

                } else {
                    $communityCommentServices->decUpdate(['id' => $info['id']], 'like_num');
                }
                $communityCacheServices->setLike($info['id'], $uid, $status, CommunityCacheServices::COMMUNITY_COMMENT_LIKE);
            }
        } catch (\Throwable $e) {
            $str = $type == 1 ? '帖子点赞取消' : '帖子评论点赞取消';
            response_log_write([
                'message' => $str . ':' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        return true;
    }
}
