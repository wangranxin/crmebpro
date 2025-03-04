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

namespace app\dao\product\product;

use app\dao\BaseDao;
use app\model\product\product\StoreProductReply;

/**
 * Class StoreProductReplyDao
 * @package app\dao\product\product
 */
class StoreProductReplyDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProductReply::class;
    }

    /**
     * 后台评论列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sysPage(array $where, int $page, int $limit)
    {
        return $this->search($where)->page($page, $limit)->select()->toArray();
    }

    /**
 	* 获取最近一条最好的评论
	* @param int $productId
	* @param string $field
	* @param int $page
	* @param $limit
	* @return array
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	 */
    public function getProductReply(int $productId, string $field = '*', int $page = 0, $limit = 0)
    {
        return $this->search(['product_id' => $productId, 'is_del' => 0])
        	->field($field)
            ->with(['userInfo'])
            ->when($page && $limit, function ($query) use ($page, $limit) {
				$query->page($page, $limit);
            })->order('add_time DESC,product_score DESC,service_score DESC,add_time DESC')
            ->select()->toArray();
    }

    /**
     * 评论条件
     * @param int $id
     * @param int $type
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    public function replyWhere(int $id, int $type = 0)
    {
        return $this->search(['product_id' => $id, 'is_del' => 0])
            ->when($type == 1, function ($query) {
                $query->where('product_score', 5)->where('service_score', 5)->where('delivery_score', 5);
            })->when($type == 2, function ($query) {
                $query->where(function ($query0) {
                    $query0->where(function ($query1) {
                        $query1->where('product_score', '<>', 5)->whereOr('service_score', '<>', 5)->whereOr('delivery_score', '<>', 5);
                    })->where(function ($query2) {
                        $query2->where('service_score', '>', 2)->where('product_score', '>', 2)->where('delivery_score', '>', 2);
                    });
                });
            })->when($type == 3, function ($query) {
                $query->where(function ($query0) {
                    $query0->where('product_score', '<=', 2)->whereOr('service_score', '<=', 2)->where('delivery_score', '<=', 2);
                });
            });
    }

    /**
     * 评论条数
     * @param int $id
     * @param int $type
     * @return int
     */
    public function replyCount(int $id, int $type = 0)
    {
        return $this->replyWhere($id, $type)->count();
    }

    /**
     * 评论内容
     * @param int $id
     * @param int $type
     * @param int $page
     * @param int $limit
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function replyList(int $id, int $type = 0, int $page = 0, int $limit = 0, array $with = ['userInfo'])
    {
        return $this->replyWhere($id, $type)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->where('is_del', 0)->with($with)->order('add_time desc')->select()->toArray();
    }
}
