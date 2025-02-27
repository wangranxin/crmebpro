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
namespace app\controller\api\v1\publics;

use app\Request;
use app\services\article\ArticleServices;
use app\services\wechat\WechatNewsCategoryServices;
use think\annotation\Inject;

/**
 * 文章类
 * Class Article
 * @package app\api\controller\publics
 */
class Article
{
    /**
     * @var ArticleServices
     */
    #[Inject]
    protected ArticleServices $services;

    /**
 	* 文章列表
	* @param WechatNewsCategoryServices $services
	* @param $cid
	* @return \think\Response
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function lst(WechatNewsCategoryServices $services, $cid)
    {
        [$page, $limit] = $this->services->getPageValue();
		$where = ['cid' => $cid];
        $where['ids'] = $services->getNewIds();
        $list = $this->services->cidByArticleList($where, $page, $limit, "id,title,image_input,visit,likes,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url");
        return app('json')->successful($list);
    }

    /**
     * 文章点赞 添加、取消
     * @param Request $request
     * @param $id
     * @return \think\Response
     */
    public function userArticleLikes(Request $request, $id)
    {
        $where = $request->getMore([
            ['status', 0]
        ]);
        $uid = $request->uid();
        $info = $this->services->userArticleLikes($id, $where, $uid);
        return app('json')->successful($info);
    }

    /**
     * 文章详情
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function details(Request $request, $id)
    {
        $uid = $request->uid();
        $info = $this->services->getInfo($uid, $id);
        return app('json')->successful($info);
    }

    /**
     * 获取热门文章
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function hot()
    {
        [$page, $limit] = $this->services->getPageValue();
        $list = $this->services->cidByArticleList(['is_hot' => 1], $page, $limit, "id,title,image_input,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url");
        return app('json')->successful($list);
    }

    /**
     * 获取最新文章
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function new()
    {
        [$page, $limit] = $this->services->getPageValue();
        $list = $this->services->cidByArticleList([], $page, $limit, "id,title,image_input,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url");
        return app('json')->successful($list);
    }

    /**
     * 获取顶部banner文章
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function banner()
    {
        [$page, $limit] = $this->services->getPageValue();
        $list = $this->services->cidByArticleList(['is_banner' => 1], $page, $limit, "id,title,image_input,visit,from_unixtime(add_time,'%Y-%m-%d %H:%i') as add_time,synopsis,url");
        return app('json')->successful($list);
    }
}
