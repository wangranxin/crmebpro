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
namespace app\controller\api\v1\product;

use app\Request;
use app\services\product\product\StoreProductReplyCommentServices;
use app\services\product\product\StoreProductReplyServices;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\annotation\Inject;

/**
 * 商品评价类
 * Class StoreProductReplyController
 * @package app\api\controller\store
 */
class StoreProductReply
{
    /**
     * 商品services
     * @var StoreProductReplyServices
     */
    #[Inject]
    protected StoreProductReplyServices $services;


    /**
     * 商品评价数量和好评度
     * @param $id
     * @return mixed
     */
    public function reply_config($id)
    {
        /** @var StoreProductReplyServices $replyService */
        $replyService = app()->make(StoreProductReplyServices::class);
        $count = $replyService->productReplyCount((int)$id);
        return app('json')->successful($count);
    }

    /**
     * 获取商品评论
     * @param Request $request
     * @param $id
     * @param $type
     * @return mixed
     */
    public function reply_list(Request $request, $id)
    {
        [$type] = $request->getMore([
            [['type', 'd'], 0]
        ], true);
        /** @var StoreProductReplyServices $replyService */
        $replyService = app()->make(StoreProductReplyServices::class);
        $list = $replyService->getProductReplyList((int)$id, (int)$type);
        return app('json')->successful(get_thumb_water($list, 'small', ['pics']));
    }

    /**
     * 评价详情
     * @param StoreProductReplyServices $services
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function replyInfo(StoreProductReplyServices $services, Request $request, $id)
    {
        if (!$id) {
            return app('json')->fail('缺少参数');
        }
        return app('json')->success($services->getReplyInfo((int)$id, (int)$request->uid()));
    }

    /**
     * 评论回复列表
     * @param StoreProductReplyCommentServices $services
     * @param Request $request
     * @param $id 评论id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function commentList(StoreProductReplyCommentServices $services, Request $request, $id)
    {
        if (!$id) {
            return app('json')->fail('缺少参数');
        }
        $data = $services->getReplCommenList((int)$id, '', (int)$request->uid(), false);
        return app('json')->success($data['list']);
    }

    /**
     * 保存评论回复
     * @param Request $request
     * @param StoreProductReplyCommentServices $services
     * @param $id
     * @return mixed
     */
    public function replyComment(Request $request, StoreProductReplyCommentServices $services, $id)
    {
        [$content] = $request->postMore([
            ['content', '']
        ], true);
        if (!$id) {
            return app('json')->fail('缺少参数');
        }
        if (!$content) {
            return app('json')->fail('缺少回复内容');
        }
        $services->saveComment((int)$request->uid(), (int)$id, $content);
        return app('json')->success('回复成功');
    }

    /**
     * 回复点赞
     * @param Request $request
     * @param StoreProductReplyCommentServices $services
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function commentPraise(Request $request, StoreProductReplyCommentServices $services, $id)
    {
        if (!$id) {
            return app('json')->fail('缺少参数');
        }

        if ($services->commentPraise((int)$id, (int)$request->uid())) {
            return app('json')->success('点赞成功');
        } else {
            return app('json')->fail('点赞失败');
        }
    }

    /**
     * 取消回复点赞
     * @param Request $request
     * @param StoreProductReplyCommentServices $services
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function unCommentPraise(Request $request, StoreProductReplyCommentServices $services, $id)
    {
        if (!$id) {
            return app('json')->fail('缺少参数');
        }

        if ($services->unCommentPraise((int)$id, (int)$request->uid())) {
            return app('json')->success('取消点赞成功');
        } else {
            return app('json')->fail('取消点赞失败');
        }
    }

    /**
     * 点赞
     * @param Request $request
     * @param StoreProductReplyServices $services
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function replyPraise(Request $request, StoreProductReplyServices $services, $id)
    {
        if (!$id) {
            return app('json')->fail('缺少参数');
        }
        if ($services->replyPraise((int)$id, (int)$request->uid())) {
            return app('json')->success('点赞成功');
        } else {
            return app('json')->fail('点赞失败');
        }
    }

    /**
     * 取消点赞
     * @param Request $request
     * @param StoreProductReplyServices $services
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function unReplyPraise(Request $request, StoreProductReplyServices $services, $id)
    {
        if (!$id) {
            return app('json')->fail('缺少参数');
        }
        if ($services->unReplyPraise((int)$id, (int)$request->uid())) {
            return app('json')->success('取消点赞成功');
        } else {
            return app('json')->fail('取消点赞失败');
        }
    }


}
