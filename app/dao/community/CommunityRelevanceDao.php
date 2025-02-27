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

namespace app\dao\community;

use app\dao\BaseDao;
use app\model\community\CommunityRelevance;
use app\services\community\CommunityRelevanceServices;
use crmeb\basic\BaseModel;
use think\exception\ValidateException;

/**
 * 社区关联
 * Class CommunityTopicDao
 * @package app\dao\community
 */
class CommunityRelevanceDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return CommunityRelevance::class;
    }

    /**
     * @param array $where
     * @param bool $search
     * @return BaseModel
     * @throws \ReflectionException
     */
    public function search(array $where = [], bool $search = false)
    {
        return parent::search($where, $search);
    }

    public function joinCommunityList(array $where = [], $page = 0, $limit = 0, $condition = 'right_id', $order = 'r.id DESC')
    {
        return $this->joinCommunity($where, $condition)
            ->field('r.*,c.id as c_id,c.type as c_type,c.title,c.image,c.relation_id,c.content_type,c.like_num,c.slider_image')
            ->when($limit, function ($query) use ($page, $limit) {
                if ($page) {
                    $query->page($page, $limit);
                } else {
                    $query->limit($limit);
                }
            })
            ->order($order)
            ->select()
            ->toArray();
    }

    public function joinCommunity(array $where = [], $condition = 'right_id')
    {
        return $this->getModel()->alias('r')
            ->join('community c', "r.{$condition}=c.id")
            ->when(isset($where['keyword']) && $where['keyword'], function ($query) use ($where) {
                $query->where(function ($query) use ($where) {
                    $query->whereLike('c.title|c.content', "%{$where['keyword']}%");
                })->whereOr(function ($query) use ($where) {
                    //商品
                    $query->whereIn('c.id', function ($product) use ($where) {
                        $productIds = name('product')->where('store_name|keyword', "%{$where['keyword']}%")->column('id');
                        $product->name('community_relevance')->where('type', CommunityRelevanceServices::TYPE_COMMUNITY_PRODUCT)->whereIn('right_id', $productIds)->field('left_id');
                    });
                })->whereOr(function ($query) use ($where) {
                    //用户
                    $query->whereIn('c.relation_id', function ($user) use ($where) {
                        $user->name('user')->where('nickname', "%{$where['keyword']}%")->field('uid');
                    });
                });
            })
            ->where('c.is_verify', 1)->where('c.is_del', 0)->where('c.status', 1)
            //用户
            ->when(isset($where['uid']) && $where['uid'], function ($query) use ($where) {
                $query->where('r.left_id', $where['uid'])->where('r.type', CommunityRelevanceServices::TYPE_COMMUNITY_LIKE);
            })
            //商品
            ->when(isset($where['product_id']) && $where['product_id'], function ($query) use ($where) {
                $query->where('r.right_id', $where['product_id'])->where('r.type', CommunityRelevanceServices::TYPE_COMMUNITY_PRODUCT);
            })
            //话题
            ->when(isset($where['topic_id']) && $where['topic_id'], function ($query) use ($where) {
                $query->where('r.right_id', $where['topic_id'])->where('r.type', CommunityRelevanceServices::TYPE_COMMUNITY_TOPIC);
            });
    }

    /**
     * 添加
     * @param int $leftId
     * @param int $rightId
     * @param string $type
     * @param bool $check
     * @return bool
     * User: liusl
     * DateTime: 2024/8/7 14:41
     */
    public function create(int $leftId, int $rightId, string $type, bool $check = false)
    {
        if ($check && $this->checkHas($leftId, $rightId, $type)) {
            return false;
        }
        $data = [
            'left_id' => $leftId,
            'right_id' => $rightId,
            'type' => $type,
        ];

        try {
            $this->save($data);
            return true;
        } catch (\Exception  $exception) {
            throw new ValidateException('创建失败');
        }
    }

    /**
     * 检测是否存在
     * @param int $leftId
     * @param int $rightId
     * @param string $type
     * @return mixed
     * User: liusl
     * DateTime: 2024/8/7 14:42
     */
    public function checkHas(int $leftId, int $rightId, string $type)
    {
        return $this->search([
            'left_id' => $leftId,
            'right_id' => $rightId,
            'type' => $type,
        ])->find();
    }

    /**
     * 根据左键批量删除
     * @param $leftId
     * @param $type
     * @return mixed
     * User: liusl
     * DateTime: 2024/8/7 14:43
     */
    public function batchDelete($leftId, $type)
    {
        return $this->search([
            'left_id' => $leftId,
            'type' => $type,
        ])->delete();
    }

    /**
     * 条件删除
     * @param int $leftId
     * @param int $rightId
     * @param string $type
     * @return mixed
     * User: liusl
     * DateTime: 2024/8/13 14:22
     */
    public function destory(int $leftId, int $rightId, string $type)
    {
        return $this->search([
            'left_id' => $leftId,
            'right_id' => $rightId,
            'type' => $type,
        ])->delete();
    }

    /**
     * 批量插入
     * @param int $leftId
     * @param array $rightId
     * @param string $type
     * @return void
     * User: liusl
     * DateTime: 2024/8/7 14:44
     */
    public function createMany(int $leftId, array $rightId, string $type)
    {
        if (!empty($rightId)) {
            $res = [];
            foreach ($rightId as $value) {
                $res[] = [
                    'left_id' => $leftId,
                    'right_id' => $value,
                    'type' => $type,
                ];
            }
            if (count($res) > 0) {
                $this->saveAll($res);
            }
        }
    }
}
