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
namespace app\controller\supplier\product;

use app\controller\supplier\AuthController;
use app\services\product\product\StoreProductReplyServices;
use think\annotation\Inject;
use think\facade\App;

/**
 * 评论管理 控制器
 * Class StoreProductReply
 * @package app\controller\supplier\product
 */
class StoreProductReply extends AuthController
{

	/**
	* @var StoreProductReplyServices
	*/
	#[Inject]
	protected StoreProductReplyServices $services;


    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['is_reply', ''],
            ['store_name', ''],
            ['account', ''],
            ['data', ''],
            ['product_id', 0]
        ]);
		$where['type'] = 2;
        $where['relation_id'] = $this->supplierId;
        $list = $this->services->sysPage($where);
        return $this->success($list);
    }

    /**
     * 删除评论
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->services->del($id);
        return $this->success('删除成功!');
    }

    /**
     * 回复评论
     * @param $id
     * @return mixed
     */
    public function set_reply($id)
    {
        [$content] = $this->request->postMore([
            ['content', '']
        ], true);
        $this->services->setReply((int)$id, $content, 2, (int)$this->supplierId);
        return $this->success('回复成功!');
    }

}
