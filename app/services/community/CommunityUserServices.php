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

use app\dao\community\CommunityUserDao;
use app\services\agent\AgentLevelServices;
use app\services\BaseServices;
use app\services\user\level\SystemUserLevelServices;
use app\services\user\UserFriendsServices;
use app\services\user\UserServices;
use crmeb\services\CacheService;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Response;


/**
 * 社区用户
 * Class CommunityTopicServices
 * @package app\services\community
 * @mixin CommunityUserDao
 */
class CommunityUserServices extends BaseServices
{
    /**
     * @var CommunityUserDao
     */
    #[Inject]
    protected CommunityUserDao $dao;

    /**
     * 检查用户是否存在
     * 此方法用于确认给定的用户ID是否已在社区中存在，如果不存在，则将其标记为存在
     * @param int $uid 需要检查的用户ID，默认值为0，表示不特定用户
     * @return bool 总是返回true，表示所有用户均被视为存在，特定逻辑处理在方法内部进行
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function hasUser(int $uid = 0)
    {
        if (!$uid) return true;
        $this->cacheTag()->remember('community_is_user_cache_' . $uid, function () use ($uid) {
            $info = $this->dao->get(['type' => 2, 'relation_id' => $uid, 'is_del' => 0]);
            if (!$info) {
                $this->dao->save(['type' => 2, 'relation_id' => $uid, 'add_time' => time()]);
            }
            return 1;
        });
        return true;
    }

    /**
     * 主页
     * @param int $authorUid //作者ID
     * @param int $uid
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/22 12:06
     */
    public function getInfo(int $authorUid, int $uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        /** @var CommunityCacheServices $communityCacheServices */
        $communityCacheServices = app()->make(CommunityCacheServices::class);

        $site_name = sys_config('site_name');
        $site_image = sys_config('wap_login_logo');
        $friend_count = 0;
        if ($authorUid == 0) {
            $info = $this->dao->get(['type' => 0, 'is_del' => 0]);
        } else {
            $info = $this->dao->get(['type' => 2, 'relation_id' => $authorUid, 'is_del' => 0]);
            $userInfo = app()->make(UserServices::class)->getUserCacheInfo($authorUid);
            if (!$userInfo) {
                throw new ValidateException('用户不存在～');
            }
            /** @var UserFriendsServices $userFriendsServices */
            $userFriendsServices = app()->make(UserFriendsServices::class);
            $friend = $userFriendsServices->getFriendUids($uid);
            $friend = array_diff($friend, [$uid]);
            $friend_count = count($friend);
        }
        if (!$info) {
            throw new ValidateException('用户不存在～');
        }

        $info = $info->toArray();
        $info['friend_count'] = $friend_count;
        $info['author'] = $site_name;
        $info['author_image'] = $site_image;
        $info['level_name'] = '';
        $info['vip_status'] = 0;
        $info['is_self'] = 0;
        $info['is_follow'] = $communityCacheServices->checkUserLike($authorUid, $uid, CommunityCacheServices::COMMUNITY_INTEREST);
        if ($authorUid == $uid) {
            $info['is_self'] = 1;
        }

        if ($authorUid != 0) {
            $user = $userServices->get($authorUid);
            $info['author'] = $user['nickname'];
            $info['author_image'] = $user['avatar'];
            $is_open_level = sys_config('member_func_status', 0);
            if ($is_open_level && $user['level']) {
                /** @var SystemUserLevelServices $levelServices */
                $levelServices = app()->make(SystemUserLevelServices::class);
                $levelInfo = $levelServices->getOne(['id' => $user['level']], 'id,grade');
                $info['level_name'] = $levelInfo['grade'] ?? '';
            }
            //看付费会员是否开启
            $is_open_member = sys_config('member_card_status', 0);
            if ($is_open_member) {
                if ($user['is_ever_level']) {
                    $info['vip_status'] = 1;//永久会员
                } else {
                    if ($user['is_money_level'] && $user['overdue_time'] && $user['overdue_time'] > time()) {
                        $info['vip_status'] = 1;//开通了，没有到期
                    }
                }
            }
        }
        return $info;
    }

    /**
     * 关注/取消关注
     * @param int $authorUid
     * @param int $uid
     * @param int $status
     * @return \think\Response|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/26 09:35
     */
    public function setInterest(int $authorUid, int $uid, int $status)
    {
        /** @var CommunityRelevanceServices $communityRelevanceServices */
        $communityRelevanceServices = app()->make(CommunityRelevanceServices::class);
        /** @var CommunityCacheServices $communityCacheServices */
        $communityCacheServices = app()->make(CommunityCacheServices::class);
        $communityRecordServices = app()->make(CommunityRecordServices::class);

        if ($authorUid == 0) {
            $info = $this->dao->get(['type' => 0, 'relation_id' => 0]);
        } else {
            $info = $this->dao->getUserInfo($authorUid);
        }
        if (!$info)
            return app('json')->fail('内容不存在');

        //是否存在点赞
        $check = $communityRelevanceServices->checkHas($uid, $authorUid, CommunityRelevanceServices::TYPE_COMMUNITY_INTEREST);

        if ($status) {
            if ($check) throw new ValidateException('您已经关注过了～');
            $communityRelevanceServices->create($uid, $authorUid, CommunityRelevanceServices::TYPE_COMMUNITY_INTEREST, true);
            //粉丝数量+1
            $this->dao->incUpdate(['id' => $info['id']], 'fans_num');
            //自己关注数量+1
            $this->dao->incUpdate(['relation_id' => $uid], 'follow_num');
            $communityRecordServices->save([
                'uid' => $authorUid,
                'relation_id' => $uid,
                'type' => CommunityRecordServices::SET_TYPE_FOLLOW,
                'add_time' => time()
            ]);
        } else {
            if (!$check) throw new ValidateException('您还未关注哦～');
            $communityRelevanceServices->destory($uid, $authorUid, CommunityRelevanceServices::TYPE_COMMUNITY_INTEREST);
            //粉丝数量-1
            $this->dao->decUpdate(['id' => $info['id']], 'fans_num');
            //自己关注数量-1
            $this->dao->decUpdate(['relation_id' => $uid], 'follow_num');
        }
        $communityCacheServices->setLike($authorUid, $uid, $status, CommunityCacheServices::COMMUNITY_INTEREST);
    }

    /**
     * 查询10条关注用户头像,是否发新作品
     * @param int $uid
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \ReflectionException
     * User: liusl
     * DateTime: 2024/9/13 16:09
     */
    public function follow(int $uid)
    {
        /** @var CommunityRelevanceServices $communityRelevanceServices */
        $communityRelevanceServices = app()->make(CommunityRelevanceServices::class);
        /** @var CommunityCacheServices $communityCacheServices */
        $communityCacheServices = app()->make(CommunityCacheServices::class);
        /** @var CommunityUserServices $communityUserServices */
        $communityUserServices = app()->make(CommunityUserServices::class);

        $where['type'] = CommunityRelevanceServices::TYPE_COMMUNITY_INTEREST;
        $where['left_id'] = $uid;
        $list = $communityRelevanceServices->search($where)->column('right_id');
        if (!$list) return [];
        $netFollow = $followIds = [];
        foreach ($list as $k => $v) {
            if ($communityCacheServices->checkCommunityNewest($uid, $v)) {
                $netFollow[] = $v;
                unset($list[$k]);
            }
        }
        $remainingCount = 10 - count($netFollow);
        if ($remainingCount > 0) {
            $followIds = array_slice($list, 0, $remainingCount);
            $followIds = array_merge($netFollow, $followIds);
        }
        $followIds = $followIds ?: $netFollow;
        $followList = $communityUserServices->search(['relation_id' => $followIds])->orderRaw('FIELD(relation_id,' . implode(',', $followIds) . ')')->select()->toArray();
        $data = [];
        foreach ($followList as &$item) {
            $userFormat = $this->getUserFormat($item['type'], $item['relation_id']);
            $user['author'] = $userFormat['author'];
            $user['author_image'] = $userFormat['author_image'];
            $user['relation_id'] = $item['relation_id'];
            $user['is_new'] = in_array($item['relation_id'], $netFollow) ? 1 : 0;
            $data[] = $user;
        }
        unset($item);
        return $data;
    }

    /**
     * 关注/粉丝列表
     * @param int $uid
     * @param string $type
     * @return array|Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \ReflectionException User: liusl
     * DateTime: 2024/8/26 10:35
     */
    public function followList(int $uid, string $type = 'follow')
    {
        if (!$uid) {
            return app('json')->fail('参数错误');
        }
        [$page, $limit] = $this->getPageValue();

        /** @var CommunityRelevanceServices $communityRelevanceServices */
        $communityRelevanceServices = app()->make(CommunityRelevanceServices::class);
        /** @var CommunityCacheServices $communityCacheServices */
        $communityCacheServices = app()->make(CommunityCacheServices::class);
        $where['type'] = CommunityRelevanceServices::TYPE_COMMUNITY_INTEREST;
        $with = ['communityUser'];
        if ($type == 'follow') {
            $where['left_id'] = $uid;
        } else {
            $where['right_id'] = $uid;
            $with = ['communityFans'];
        }
        $list = $communityRelevanceServices->search($where)->with($with)->page($page, $limit)->order('id desc')->select()->toArray();
        $data = [];
        foreach ($list as $item) {
            $communityUser = $type == 'follow' ? ($item['communityUser'] ?? []) : ($item['communityFans'] ?? []);
            $user = [];
            if ($communityUser) {
                $userFormat = $this->getUserFormat($communityUser['type'], $communityUser['relation_id']);
                $user['author'] = $userFormat['author'];
                $user['author_image'] = $userFormat['author_image'];
                $user['community_num'] = $communityUser['community_num'];
                $user['fans_num'] = $communityUser['fans_num'];
                $user['relation_id'] = $communityUser['relation_id'];
                if ($type == 'follow') {
                    //我关注对方
                    $user['is_follow'] = 1;
                    $user['is_fans'] = $communityCacheServices->checkUserLike($uid, $communityUser['relation_id'], CommunityCacheServices::COMMUNITY_INTEREST);
                } else {
                    $user['is_fans'] = 1;
                    $user['is_follow'] = $communityCacheServices->checkUserLike($communityUser['relation_id'], $uid, CommunityCacheServices::COMMUNITY_INTEREST);
                }
            }
            $data[] = $user;
        }
        return $data;
    }

    /**
     * 我的好友
     * @param $uid
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/26 12:00
     */
    public function userFriend($uid)
    {
        /** @var UserFriendsServices $services */
        $services = app()->make(UserFriendsServices::class);
        /** @var CommunityCacheServices $communityCacheServices */
        $communityCacheServices = app()->make(CommunityCacheServices::class);

        [$page, $limit] = $this->getPageValue();
        $uids = $services->getFriendUids($uid);
        $uids = array_diff($uids, [$uid]);
        if (!$uids) {
            return [];
        }
        $list = $this->dao->getList(['relation_id' => $uids], '*', [], $page, $limit);
        foreach ($list as &$item) {
            $userFormat = $this->getUserFormat($item['type'], $item['relation_id']);
            $item['author'] = $userFormat['author'];
            $item['author_image'] = $userFormat['author_image'];
            $item['is_fans'] = $communityCacheServices->checkUserLike($uid, $item['relation_id'], CommunityCacheServices::COMMUNITY_INTEREST);
            $item['is_follow'] = $communityCacheServices->checkUserLike($item['relation_id'], $uid, CommunityCacheServices::COMMUNITY_INTEREST);
        }
        unset($item);
        return $list;
    }

    /**
     * 用户头像昵称
     * @param int $type
     * @param int $uid
     * @return string[]
     * User: liusl
     * DateTime: 2024/8/26 10:31
     */
    public function getUserFormat(int $type, int $uid, array $info = [])
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if ($type == 2) {//用户
            $userInfo = $userServices->getUserCacheInfo(($uid));
            $data['author'] = $userInfo['nickname'] ?? '用户已注销';
            $data['author_image'] = $userInfo['avatar'] ?? sys_config('h5_avatar');
            $data['logout'] = $userInfo ? 0 : 1;
        } else if ($type == 3) {//虚拟
            $data['author'] = $info['nickname'] ?? '';
            $data['author_image'] = $info['avatar'] ?? '';
        } else {//平台
            $data['author'] = sys_config('site_name');
            $data['author_image'] = sys_config('wap_login_logo');
        }
        return $data;
    }

    /**
     * 推荐
     * @param int $uid
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/26 15:44
     */
    public function recommendList(int $uid)
    {
        [$page, $limit] = $this->getPageValue();
        /** @var CommunityRelevanceServices $communityRelevanceServices */
        $communityRelevanceServices = app()->make(CommunityRelevanceServices::class);
        /** @var CommunityCacheServices $communityCacheServices */
        $communityCacheServices = app()->make(CommunityCacheServices::class);

        $ids = $communityRelevanceServices->getColumn(['left_id' => $uid, 'type' => CommunityRelevanceServices::TYPE_COMMUNITY_INTEREST], 'right_id');
        $ids = array_merge($ids, [$uid]);
        $list = $this->dao->getList(['not_relation_id' => $ids, 'is_del' => 0, 'status' => 1, 'is_community_num' => 1], 'type,relation_id,community_num,fans_num', ['community'], $page, $limit, 'fans_num desc');
        foreach ($list as &$item) {
            $userFormat = $this->getUserFormat($item['type'], $item['relation_id']);
            $item['author'] = $userFormat['author'];
            $item['author_image'] = $userFormat['author_image'];
            $item['is_follow'] = $communityCacheServices->checkUserLike($item['relation_id'], $uid, CommunityCacheServices::COMMUNITY_INTEREST);
        }
        unset($item);
        return $list;
    }

    /**
     * 用户发帖数据矫正
     * @param $id
     * @return void
     * User: liusl
     * DateTime: 2024/9/9 17:51
     */
    public function syncUserNum($id)
    {
        /** @var CommunityServices $communityServices */
        $communityServices = app()->make(CommunityServices::class);
        $uid = $communityServices->value(['id' => $id], 'relation_id');
        $count = $communityServices->getCount(['relation_id' => $uid, 'status' => 1, 'is_verify' => 1, 'is_del' => 0]);
        $this->dao->update(['relation_id' => $uid], ['community_num' => $count]);
    }

    /**
     * 用户注销后续事件
     * @param $uid
     * @return void
     * @throws \ReflectionException
     * User: liusl
     * DateTime: 2024/12/26 下午4:06
     */
    public function logoutAfter($uid)
    {
        //删除帖子
        $communityServices = app()->make(CommunityServices::class);
        $communityIds = $communityServices->getColumn(['type' => 2, 'relation_id' => $uid], 'id');
        foreach ($communityIds as $communityId) {
            event('community.delete', [$communityId]);
            //帖子操作事件
            event('community.operate', [$communityId]);
        }
        $communityServices->update(['type' => 2, 'relation_id' => $uid], ['is_del' => 1]);
        //清空关注
        $communityRelevanceServices = app()->make(CommunityRelevanceServices::class);
        $authorUids = $communityRelevanceServices->getColumn(['left_id' => $uid, 'type' => CommunityRelevanceServices::TYPE_COMMUNITY_INTEREST], 'right_id');
        $communityRelevanceServices->search(['left_id' => $uid, 'type' => CommunityRelevanceServices::TYPE_COMMUNITY_INTEREST])->delete();
        $this->dao->search(['relation_id' => $authorUids])->dec('fans_num', 1)->update();
    }
}
