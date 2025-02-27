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

namespace app\listener\work;


use app\services\work\WorkChannelCodeServices;
use app\services\work\WorkClientFollowServices;
use app\services\work\WorkClientFollowTagsServices;
use crmeb\interfaces\ListenerInterface;
use crmeb\services\wechat\Work;
use think\exception\ValidateException;

/**
 * 编辑客户标签事件
 * Class ClientLabelListener
 * @package app\listener\work
 */
class ClientLabelListener implements ListenerInterface
{

    /**
     * @param $event
     */
    public function handle($event): void
    {
        [$state, $userId, $externalUserID] = $event;

        //渠道码id
        $channelId = 0;
        if (false !== strstr($state, 'channelCode-')) {
            $channelId = (int)str_replace('channelCode-', '', $state);
        }

        //获取用户标签
        $labelId = [];
        if ($channelId) {
            /** @var WorkChannelCodeServices $channelService */
            $channelService = app()->make(WorkChannelCodeServices::class);
            $labelId = $channelService->value(['id' => $channelId], 'label_id');
            $labelId = is_string($labelId) ? json_decode($labelId, true) : $labelId;
        }

        //编辑客户企业标签
        if ($labelId) {
            $resTage = Work::markTags($userId, $externalUserID, $labelId);
            if (0 !== $resTage['errcode']) {
                throw new ValidateException($resTage['errmsg']);
            }
            $tagRes = Work::getCorpTags();
            $tagMap = [];
            foreach ($tagRes["tag_group"]??[] as $tagGroupItem){
                foreach ($tagGroupItem["tag"]??[] as $tagItem){
                    $tagMap[$tagItem["id"]] = $tagItem +["group_id"=>$tagGroupItem["group_id"],"group_name"=>$tagGroupItem["group_name"]];
                }
            }
            /** @var WorkClientFollowServices $followService */
            $followService = app()->make(WorkClientFollowServices::class);
            $followId = $followService->value(['oper_userid' => $externalUserID], 'id');
            if (!$followId){
                return;
            }
            $data = [];
            foreach ($labelId as $tagId){
                $tag = $tagMap[$tagId]??[];
                $data[] = [
                    'follow_id' => $followId,
                    'group_name' => $tag['group_name'] ?? '',
                    'tag_name' => $tag['name'] ?? '',
                    'type' => 1,
                    'tag_id' => $tagId,
                    'create_time' => time()
                ];
            }
            /** @var WorkClientFollowTagsServices $tagService */
            $tagService = app()->make(WorkClientFollowTagsServices::class);
            $tagService->saveAll($data);
        }
    }
}
