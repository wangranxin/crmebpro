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

use app\dao\community\CommunityCommentDao;
use app\services\BaseServices;
use app\services\user\UserRelationServices;
use app\services\user\UserServices;
use crmeb\basic\BaseModel;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Route as Url;
use think\Model;


/**
 * 社区评价
 * Class CommunityCommentServices
 * @package app\services\community
 * @mixin CommunityCommentDao
 */
class CommunityCommentServices extends BaseServices
{
    /**
     * @var CommunityCommentDao
     */
    #[Inject]
    protected CommunityCommentDao $dao;

    /**
     * 后台评价列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sysPage(array $where)
    {
        $where['is_del'] = 0;
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, '*', ['communityTitle'], $page, $limit);
        $verifyList = [];
        if ($where['is_reply'] == 1) {
            $verifyIds = array_column($list, 'id');
            $verifyList = $this->dao->search(['reply_id' => $verifyIds, 'is_del' => 0])->group('reply_id')->field('count(*) as count,reply_id')->select()->toArray();
            $verifyList = array_column($verifyList, 'count', 'reply_id');
        }
        $commentReplyIds = array_unique(array_filter(array_column($list, 'comment_reply_id')));
        $commentReplyList = [];
        if ($commentReplyIds) {
            $commentReplyList = $this->dao->search(['id' => $commentReplyIds, 'is_del' => 0])->column('content', 'id');
        }
        /** @var CommunityUserServices $communityUserServices */
        $communityUserServices = app()->make(CommunityUserServices::class);
        if ($list) {
            foreach ($list as &$item) {
                //待审核数
                $item['verify_count'] = $verifyList[$item['id']] ?? 0;
                //回复上级内容
                $item['comment_reply_content'] = $commentReplyList[$item['comment_reply_id']] ?? '-';
                $userFormat = $communityUserServices->getUserFormat($item['type'], $item['uid'], $item);
                $item['author'] = $userFormat['author'];
                $item['author_image'] = $userFormat['author_image'];
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 移动端列表
     * @param $where
     * @param $field
     * @param $with
     * @param int $uid
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/29 11:00
     */
    public function getList($where, $field = '*', $with = [], int $uid = 0)
    {
        /** @var CommunityCacheServices $communityCacheServices */
        $communityCacheServices = app()->make(CommunityCacheServices::class);
        /** @var CommunityUserServices $communityUserServices */
        $communityUserServices = app()->make(CommunityUserServices::class);

        $order = isset($where['reply_id']) && $where['reply_id'] ? 'add_time ASC' : 'add_time DESC';
        $where['author_uid'] = $uid;
        //
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $field, $with, $page, $limit, $order);
        $count = $this->dao->count($where);

        foreach ($list as &$item) {
            $userFormat = $communityUserServices->getUserFormat($item['type'], $item['uid'], $item);
            $item['author'] = $userFormat['author'];
            $item['author_image'] = $userFormat['author_image'];
            //回复给谁
            if ($item['comment_reply_id']) {
                $userFormat = $communityUserServices->getUserFormat($item['comment_reply_uid'] == 0 ? 0 : 2, $item['comment_reply_uid']);
                $item['comment_author'] = $userFormat['author'] ?? '';
                $item['comment_author_image'] = $userFormat['author_image'] ?? '';
            }
            $item['time_text'] = timeConverter(strtotime($item['add_time']));
            if ($uid) {
                //是否点赞
                $item['isLike'] = $communityCacheServices->checkUserLike($item['id'], $uid, CommunityCacheServices::COMMUNITY_COMMENT_LIKE);
            }
        }
        unset($item);
        return compact('list', 'count');
    }

    /**
     * 评论回复表单
     * @param int $id
     * @param int $is_verify
     * @return mixed
     */
    public function replayForm(int $id)
    {
        $f[] = Form::textarea('content', '回复内容')->required('请输入回复原因');
        return create_form('回复内容', $f, Url::buildUrl('/community/comment/reply/' . $id), 'post');
    }


    /**
     * 审核表单
     * @param int $id
     * @param int $is_verify
     * @return mixed
     */
    public function verifyForm(int $id, int $is_verify = 1)
    {
        $f = [];
        if ($is_verify == 1) {
            $f[] = Form::radio('is_verify', '审核状态', 1)->options([['value' => 1, 'label' => '通过'], ['value' => -1, 'label' => '拒绝']])->appendControl(-1, [
                Form::textarea('refusal', '拒绝原因')->required('请输入拒绝原因')]);
        } else {
            $f[] = Form::hidden('is_verify', '-2');
            $f[] = Form::textarea('refusal', '下架原因')->required('请输入下架原因');
        }
        return create_form($is_verify == 1 ? '评论审核' : '强制下架', $f, Url::buildUrl('/community/comment/set_verify/' . $id), 'post');
    }

    /**
     * 创建虚拟评论表单
     * @param int $id
     * @param $store_id
     * @return mixed
     */
    public function createForm(int $id, $store_id = 0)
    {
        $field[] = Form::hidden('community_id', $id);
        $field[] = Form::radio('type', '评论类型', 0)->options([['value' => 0, 'label' => '商家评论'], ['value' => 3, 'label' => '虚拟评论']])->appendControl(3, [
            Form::frameImage('avatar', '用户头像', Url::buildUrl($store_id ? 'store/widget.images/index' : 'admin/widget.images/index', array('fodder' => 'avatar')))->icon('ios-add')->width('960px')->height('505px')->modal(['footer-hide' => true]),
            Form::input('nickname', '用户名称')->col(24),
            Form::dateTime('add_time', '评论时间', '')->placeholder('请选择评论时间(不选择默认当前添加时间)'),

        ]);
        $field[] = Form::input('content', '评价文字')->type('textarea');
        return create_form('添加虚拟评论', $field, Url::buildUrl('/community/comment/save_fictitious'), 'POST');
    }

    /**
     * 保存评论/回复
     * @param int $community_id
     * @param array $data
     * @param int $id
     * @param int $uid
     * @return BaseModel|Model
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function saveComment(int $community_id, array $data, int $id = 0, int $uid = 0)
    {
        /** @var CommunityServices $communityServices */
        $communityServices = app()->make(CommunityServices::class);
        $community = $communityServices->get($community_id);
        if (!$community) {
            throw new ValidateException('社区不存在');
        }
        if ($id) {
            //回复回复
            $comment = $this->dao->get($id);
            if (!$comment) {
                throw new ValidateException('评论不存在或已删除');
            }
            $comment_uid = $comment['uid'];

            if ($comment['is_reply'] == 1) {
                $data['reply_id'] = $id;
                $data['reply_uid'] = $comment_uid;
            } else {
                //回复评论
                $reply_comment = $this->dao->get($comment['reply_id']);
                if (!$reply_comment) {
                    throw new ValidateException('评论不存在或已删除');
                }
                $reply_comment->comment_num = $reply_comment->comment_num + 1;
                $reply_comment->save();
                $data['reply_id'] = $comment->reply_id;
                $data['reply_uid'] = $comment->reply_uid;
                $data['comment_reply_id'] = $id;
                $data['comment_reply_uid'] = $comment->uid;
            }
        } else {
            $data['is_reply'] = 1;
        }
        if (isset($data['ip']) && $data['ip']) {
            $data['city'] = $this->convertIp($data['ip']);
        }
        $time = time();
        if (isset($data['add_time']) && $data['add_time']) {
            $data['add_time'] = strtotime($data['add_time']);
            if ($data['add_time'] > $time) {
                throw new AdminException('评论时间应小于当前时间');
            }
        } else {
            $data['add_time'] = $time;
        }
        $data['uid'] = $uid;
        $data['community_id'] = $community_id;
        $res = $this->dao->save($data);
        if (!$res) throw new AdminException('保存评论失败');
        event('community.comment.operate', [$res->id, 1]);
        return $res;
    }

    /**
     * 获取社区内容评价列表
     * @param int $uid
     * @param int $id
     * @param int $pid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getVideoCommentList(int $uid, int $id, int $pid = 0)
    {
        $where = ['community_id' => $id, 'is_del' => 0];
        [$page, $limit] = $this->getPageValue();
        if ($limit > 20) $limit = 20;

        if ($pid) {
            $comment = $this->dao->get($pid);
            if (!$comment) {
                throw new ValidateException('评论不存在或已删除');
            }
            $where['community_id'] = $comment['community_id'];
        }
        $where['pid'] = $pid;
        $list = $this->dao->getList($where, 'id,pid,community_id,uid,nickname,avatar,content,like_num,city,add_time', ['children' => function ($query) {
            $query->field("id,pid")->with(['userVip']);
        }, 'userVip'], $page, $limit);
        if ($list) {
            $userLike = [];
            if ($uid) {
                $ids = array_column($list, 'id');
                /** @var UserRelationServices $userRelationServices */
                $userRelationServices = app()->make(UserRelationServices::class);
                $userLike = $userRelationServices->getColumn([['uid', '=', $uid], ['relation_id', 'in', $ids], ['category', '=', 'video_comment'], ['type', '=', 'like']], 'id,relation_id', 'relation_id');
            }
            foreach ($list as &$item) {
//                $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
                $item['is_like'] = isset($userLike[$item['id']]);
                $item['reply'] = [];
                $item['reply_count'] = isset($item['children']) ? (int)count($item['children']) : 0;
                $item['city'] = $this->addressHandle($item['city'])['city'] ?? '';
                unset($item['children']);
            }
        }
        return $list;
    }

    /**
     * 点赞/取消
     * @param array $info
     * @param int $uid
     * @param int $status
     * @return void
     * User: liusl
     * DateTime: 2024/8/21 10:31
     */
    public function setCommentLike(array $info, int $uid, int $status)
    {
        /** @var CommunityRelevanceServices $communityRelevanceServices */
        $communityRelevanceServices = app()->make(CommunityRelevanceServices::class);
        //是否存在点赞
        $check = $communityRelevanceServices->checkHas($uid, $info['id'], CommunityRelevanceServices::TYPE_COMMUNITY_COMMENT_LIKE);
        if ($status) {
            if ($check) throw new ValidateException('您已经点过赞了～');
            $communityRelevanceServices->create($uid, $info['id'], CommunityRelevanceServices::TYPE_COMMUNITY_COMMENT_LIKE, true);
        } else {
            if (!$check) throw new ValidateException('您还未赞过哦～');
            $communityRelevanceServices->destory($uid, $info['id'], CommunityRelevanceServices::TYPE_COMMUNITY_COMMENT_LIKE);
        }
        event('community.like', [$info, $uid, 2, $status]);
    }

    /**
     * 删除帖子
     * @param int $id
     * @param int $uid
     * @param bool $is_admin
     * @return true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/28 16:19
     */
    public function commentDelete(int $id, int $uid = 0, bool $is_admin = false)
    {
        /** @var CommunityServices $communityServices */
        $communityServices = app()->make(CommunityServices::class);

        $info = $this->dao->get(['id' => $id, 'is_del' => 0]);
        if (!$info) {
            throw new ValidateException('评论不存在或已删除');
        }
        if ($info['uid'] != $uid && !$is_admin) {
            throw new ValidateException('您无权删除该评论');
        }

        if ($info['is_reply'] == 1) {//评论帖子
            $replyId = $this->dao->getColumn(['reply_id' => $id], 'id');
            $replyId = array_merge([$id], $replyId);
            //删除评论回复
            $this->dao->update(['reply_id' => $id], ['is_del' => 1]);
        }

        $info->is_del = 1;
        $info->save();
        event('community.comment.delete', [$replyId ?? $id]);
        event('community.comment.operate', [$id]);
        return true;
    }

    /**
     * 删除后续事件
     * @param $id
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/28 16:47
     */
    public function deleteFollow($id)
    {
        /** @var CommunityRelevanceServices $relevanceServices */
        $relevanceServices = app()->make(CommunityRelevanceServices::class);
        //删除点赞列表
        $relevanceServices->batchDelete($id, CommunityRelevanceServices::TYPE_COMMUNITY_COMMENT_LIKE);
        //清理缓存
    }

    /**
     * 评论数量修正
     * @param $id
     * @return true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * User: liusl
     * DateTime: 2024/9/10 11:02
     */
    public function syncCommentNum($id): bool
    {
        $info = $this->dao->get($id);
        if (!$info) {
            return true;
        }

        //帖子评论数量
        $reply = $this->dao->count(['community_id' => $info['community_id'], 'is_verify' => 1, 'is_del' => 0]);
        /** @var CommunityServices $communityServices */
        $communityServices = app()->make(CommunityServices::class);
        $communityServices->update(['id' => $info['community_id']], ['comment_num' => $reply ?: 0]);

        if ($info['is_reply'] == 0) {
            //子帖子数量
            $reply = $this->dao->count(['reply_id' => $info['reply_id'], 'is_verify' => 1, 'is_del' => 0]);
            $this->dao->update(['id' => $info['reply_id']], ['comment_num' => $reply ?: 0]);
        }
        return true;
    }
}
