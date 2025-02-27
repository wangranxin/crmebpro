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
namespace app\controller\api\v1\community;


use app\services\community\CommunityCacheServices;
use app\services\community\CommunityRecordServices;
use app\services\community\CommunityServices;
use app\Request;
use app\services\community\CommunityTopicServices;
use app\services\product\product\StoreProductLogServices;
use app\services\user\UserRelationServices;
use think\annotation\Inject;


/**
 * Class StoreController
 * @package app\api\controller\v1\store
 */
class Community
{

    /**
     * @var CommunityServices
     */
    #[Inject]
    protected CommunityServices $services;

    /**
     * 获取社区配置
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/8/5 11:55
     */
    public function getConfig()
    {
        $data = [
            'community_status' => (int)sys_config('community_status', 1),//社区功能
            'community_comment_status' => (int)sys_config('community_comment_status', 1),//社区评论功能
            'community_comment_add' => (int)sys_config('community_comment_add', 1),//社区评论新增功能
            'community_exp_num' => sys_config('community_exp', 1) ? sys_config('community_exp_num') : 0,//社区经验
            'community_integral_num' => sys_config('community_integral', 1) ? sys_config('community_integral_num') : 0,//社区积分
        ];
        return app('json')->success($data);
    }

    /**
     * 获取帖子列表
     *
     * 本函数通过接收各种筛选条件来获取帖子列表。筛选条件包括话题、关键词、是否关注、
     * 作者ID、内容类型、开始ID、特定ID列表以及排序方式。排序方式可选最新或最热。
     * 如果用户未登录或指定的作者ID与登录用户不符，则只返回已发布且已验证的帖子。
     * 如果用户登录且关注条件被激活，则用登录用户ID作为关注者筛选条件。
     *
     * @param Request $request 包含筛选条件的请求对象
     * @return mixed 返回帖子列表的JSON响应
     */
    public function list(Request $request)
    {
        $where = $request->postMore([
            ['topic_id', ''],//话题
            ['keyword', ''],//关键词
            ['is_follow', ''],//是否关注
            ['relation_id', ''],//作者 id
            ['content_type', ''],//内容类型：1：图文2：视频
            ['start_id', ''],//从哪个id开始
            ['ids', ''],//
            ['order', 2],//1最新2最热
        ]);
        $where['topic_id'] = stringToIntArray($where['topic_id']);
        $order = $where['order'] == 2 ? 'star desc,add_time desc' : 'add_time desc';
        unset($where['order']);
        $where['is_del'] = 0;
        $uid = $request->uid();
        // 如果未指定作者ID或指定的作者ID与当前用户不符，则只返回已发布且已验证的帖子
        if (!$where['relation_id'] || $uid != $where['relation_id']) {
            $where['status'] = 1;
            $where['is_verify'] = 1;
        }
        //我的关注
        if ($where['is_follow']) {
            if (!$uid) {
                return app('json')->success([]);
            }
            $where['is_follow'] = $uid;
        }

        if ($where['ids']) {
            $where['id'] = explode(',', $where['ids']);
        }
        unset($where['ids']);
        return app('json')->success($this->services->getApiList($where, $uid, $order));
    }

    /**
     * 获取话题
     * @param CommunityTopicServices $services
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/5 12:13
     */
    public function getTopic(CommunityTopicServices $services, Request $request)
    {
        $where = $request->postMore([
            ['name', ''],//话题名称
            ['is_recommend', ''],//话题推荐
            ['is_community', ''],//是否查帖子条数
        ]);
        $where['status'] = 1;
        return app('json')->success($services->getAllTopic($where));
    }

    /**
     * 用户商品列表
     * @param Request $request
     * @param StoreProductLogServices $services
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/6 09:51
     */
    public function getProductList(Request $request, StoreProductLogServices $services, UserRelationServices $userRelationServices)
    {
        $where = $request->getMore([
            ['type', 'pay'],//已购pay,收藏collect,浏览visit
            ['keyword', ''],
        ]);
        $uid = $request->uid();
        $where['uid'] = $uid;
        if ($where['type'] == 'collect') {
            $list = $userRelationServices->getUserRelationList($uid, 'product', 'collect');
        } else {
            $list = $services->getProduct($where);
        }
        return app('json')->success($list);
    }

    /**
     * 新增编辑社区内容
     * @param $id
     * @param Request $request
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/8/7 14:53
     */
    public function communitySave(Request $request)
    {
        $data = $this->checkParams($request);
        $data['relation_id'] = $request->uid();
        $this->services->saveData($data);
        return app('json')->success('添加社区内容成功!');
    }

    /**
     * 修改
     * @param $id
     * @param Request $request
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/8/8 10:03
     */
    public function update($id, Request $request)
    {
        $data = $this->checkParams($request);
        if (!$this->services->get($id)) {
            return app('json')->fail('社区内容不存在');
        }
        $this->services->saveData($data, $id);
        return app('json')->success('提交成功');
    }

    /**
     *获取数据
     * @param Request $request
     * @return array|\think\Response
     * User: liusl
     * DateTime: 2024/8/8 10:04
     */
    public function checkParams(Request $request)
    {
        $data = $request->postMore([
            ['content_type', 1],//内容类型1：图文2视频
            ['title', ''],//标题
            ['content', ''],//内容
            ['image', ''],//封面
            ['video_url', ''],//视频地址
            ['slider_image', []],//图集
            ['topic_id', []],//关联话题
            ['topic_name', []],//关联话题
            ['product_id', []],//关联商品
        ]);
        if ($data['content_type'] == 1) {
            $data['image'] = $data['slider_image'][0] ?? '';
        }
        $data['slider_image'] = json_encode($data['slider_image']);

        //图文视频审核状态
        if ($data['content_type'] == 1) {
            $data['is_verify'] = sys_config('community_verify', 1) ? 0 : 1;
        } else {
            $data['is_verify'] = sys_config('community_video_verify', 1) ? 0 : 1;
        }
        return $data;
    }

    /**
     * 详情
     * @param $id
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/8/9 11:28
     */
    public function detail($id, Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->success($this->services->getDetail($id, $uid));
    }

    /**
     * 点赞取消
     * @param $id
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/8/13 11:31
     */
    public function setCommunityLike($id, Request $request)
    {
        [$status] = $request->postMore([
            ['status', 1]
        ], true);

        $status = $status == 1 ? 1 : 0;
        $info = $this->services->get($id);
        if (!$info || $info['is_del'] == 1 || $info['is_verify'] != 1 || $info['status'] != 1)
            return app('json')->fail('内容不存在');
        $info = $info->toArray();
        $uid = (int)$request->uid();
        $this->services->setCommunityLike($info, $uid, $status);
        if ($status) {
            return app('json')->success('点赞成功');
        } else {
            return app('json')->success('取消点赞');
        }
    }

    /**
     * 点赞列表
     * @param Request $request
     * @return \think\Response
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/24 17:06
     */
    public function communityLikeList(Request $request)
    {
        $where = $request->getMore([
            ['keyword', '']
        ]);
        $where['uid'] = (int)$request->uid();
        return app('json')->success($this->services->getJoinCommunityList($where, $where['uid']));
    }

    /**
     * 种草秀
     * @return void
     * User: liusl
     * DateTime: 2024/8/26 16:03
     */
    public function communityElegantList(Request $request)
    {
        $where = $request->getMore([
            ['product_id', ''],
            ['order', 2],//1最新2最热
        ]);
        $order = $where['order'] == 2 ? 'c.star desc' : 'c.id desc';
        unset($where['order']);
        if (!$where['product_id']) {
            return app('json')->fail('商品不能为空');
        }
//        $where['uid'] = $request->uid();
        return app('json')->success($this->services->getJoinCommunityList($where, (int)$request->uid(), 'left_id', 0, $order));
    }

    /**
     * 分享
     * @param $id
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/28 14:10
     */
    public function communityShare($id, Request $request)
    {
        $uid = (int)$request->uid();
        $this->services->communityShare($id, $uid);
        return app('json')->success('分享成功');
    }

    /**
     * 删除
     * @param $id
     * @return void
     * User: liusl
     * DateTime: 2024/8/28 14:11
     */
    public function communityDelete($id, Request $request)
    {
        $uid = (int)$request->uid();
        $this->services->communityDelete($id, $uid);
        return app('json')->success('删除成功');
    }

    /**
     * 浏览
     * @param $id
     * @param Request $request
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/9/3 10:46
     */
    public function setBrowse($id, Request $request)
    {
        $uid = (int)$request->uid();
        $this->services->setBrowse($id, $uid);
        return app('json')->success('浏览成功');
    }

    /**
     * 话题总数
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/9/21 11:24
     */
    public function topicCount($id)
    {
        $count = $this->services->topicCount($id);
        return app('json')->success(compact('count'));
    }

    /**
     * 消息列表
     * @param Request $request
     * @param CommunityRecordServices $services
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/12/5 下午3:22
     */
    public function message(Request $request, CommunityRecordServices $services)
    {
        [$type] = $request->getMore([
            ['type', '']
        ], true);
        $uid = (int)$request->uid();
        $services->update(['uid' => $uid, 'type' => $type], ['is_viewed' => 1]);
        $list = $services->getList(['uid' => $uid, 'type' => $type]);
        $isViewed = $services->isViewed($uid);
        return app('json')->success(compact('list', 'isViewed'));
    }
}
