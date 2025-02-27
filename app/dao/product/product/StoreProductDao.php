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
use app\model\product\product\StoreProduct;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\combination\StorePinkServices;
use app\services\activity\seckill\StoreSeckillServices;
use crmeb\basic\BaseModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Config;
use think\Model;

/**
 * Class StoreProductDao
 * @package app\dao\product\product
 */
class StoreProductDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProduct::class;
    }

    /**
     * @param array $where
     * @param bool $search
     * @return BaseModel
     * @throws \ReflectionException
     */
    public function search(array $where = [], bool $search = false)
    {
        return parent::search($where, $search)
            //商品关键词搜索
            ->when(isset($where['store_name']) && $where['store_name'] && is_string($where['store_name']), function ($query) use ($where) {
                if (isset($where['field_key']) && in_array($where['field_key'], ['product_id', 'bar_code', 'store_name', 'keyword', 'code'])) {
                    switch ($where['field_key']) {
                        case 'product_id':
                            $query->where('id', trim($where['store_name']));
                            break;
                        case 'store_name':
                            $query->where('store_name', 'like', '%' . trim($where['store_name']) . '%');
                            break;
                        case 'keyword':
                            $query->where('keyword', 'like', '%' . trim($where['store_name']) . '%');
                            break;
                        case 'code':
                            $query->where('code', trim($where['store_name']));
                            break;
                        case 'bar_code':
                            $query->where(function ($query) use ($where) {
                                $query->where('bar_code', trim($where['store_name']))->whereOr('id', 'IN', function ($q) use ($where) {
                                    $q->name('store_product_attr_value')->field('product_id')->where('bar_code', trim($where['store_name']))->select();
                                });
                            });
                            break;
                    }
                } else {
                    $query->where(function ($q) use ($where) {
                        $q->where('id|keyword|store_name|store_info|bar_code', 'LIKE', '%' . trim($where['store_name']) . '%')->whereOr('id', 'IN', function ($q) use ($where) {
                            $q->name('store_product_attr_value')->field('product_id')->where('bar_code', trim($where['store_name']))->select();
                        });
                    });
                }
            })
            //销量区间
            ->when(isset($where['sales_range']) && $where['sales_range'], function ($query) use ($where) {
                $sales_range = explode('-', $where['sales_range']);
                if (count($sales_range) == 2) {
                    $query->whereBetween('sales', $sales_range);
                }
            })
            //库存区间
            ->when(isset($where['stock_range']) && $where['stock_range'], function ($query) use ($where) {
                $sales_range = explode('-', $where['stock_range']);
                if (count($sales_range) == 2) {
                    $query->whereBetween('stock', $sales_range);
                }
            })
            //售价区间
            ->when(isset($where['price_range']) && $where['price_range'], function ($query) use ($where) {
                $price_range = explode('-', $where['price_range']);
                if (count($price_range) == 2) {
                    $query->whereBetween('price', $price_range);
                }
            })
            //收藏区间
            ->when(isset($where['collect_range']) && $where['collect_range'], function ($query) use ($where) {
                $price_range = explode('-', $where['collect_range']);
                if (count($price_range) == 2) {
                    $query->whereBetween('collect', $price_range);
                }
            })
            //添加时间区间
            ->when(isset($where['create_range']) && $where['create_range'], function ($query) use ($where) {
                $create_range = explode('-', $where['create_range']);
                $start_time = strtotime($create_range[0]);
                $end_time = strtotime($create_range[1]);
                if ($start_time && $end_time) {
                    $query->whereBetween('price', [$start_time, $end_time]);
                }
            })
            //活动类型
            ->when(isset($where['activity_type']) && $where['activity_type'] !== '', function ($query) use ($where) {
                //1:秒杀.2:砍价,3 拼团
                $ids = [];
                switch ($where['activity_type']){
                    case 1:
                        $ids = app()->make(StoreSeckillServices::class)->search(['is_del' => 0, 'start_status' => 1, 'status' => 1])->column('product_id');
                        break;
                    case 2:
                        $ids = app()->make(StoreBargainServices::class)->search(['is_del' => 0, 'start_status' => 1,'status' => 1])->column('product_id');
                        break;
                    case 3:
                        $ids = app()->make(StoreCombinationServices::class)->search(['is_del' => 0,'start_status' => 1, 'status' => 1])->column('product_id');
                }
                $query->whereIn('id', $ids);
            })
            ->when(isset($where['tid']) && $where['tid'], function ($query) use ($where) {//三级
                $query->whereIn('id', function ($query) use ($where) {
                    $query->name('store_product_relation')->where('type', 1)->where('relation_id', $where['tid'])->field('product_id')->select();
                });
            })->when(isset($where['sid']) && $where['sid'], function ($query) use ($where) {//二级
                $query->whereIn('id', function ($query) use ($where) {
                    $query->name('store_product_relation')->where('type', 1)->whereIn('relation_id', function ($query) use ($where) {
                        $query->name('store_product_category')->where('id|pid', $where['sid'])->field('id')->select();
                    })->field('product_id')->select();
                });
            })->when(isset($where['cid']) && $where['cid'], function ($query) use ($where) {//一级
                $query->whereIn('id', function ($query) use ($where) {
                    $query->name('store_product_relation')->where('type', 1)->whereIn('relation_id', function ($query) use ($where) {
                        $query->name('store_product_category')->where(function ($query) use ($where) {
                            $query->whereFindinSet('path', $where['cid'])->whereOr('id', $where['cid']);
                        })->field('id')->select();
                    })->field('product_id')->select();
                });
            })->when(isset($where['brand_id']) && $where['brand_id'], function ($query) use ($where) {
                $query->whereIn('id', function ($query) use ($where) {
                    $query->name('store_product_relation')->where('type', 2)->whereIn('relation_id', $where['brand_id'])->field('product_id')->select();
                });
            })->when(isset($where['store_label_id']) && $where['store_label_id'], function ($query) use ($where) {
                $query->whereIn('id', function ($query) use ($where) {
                    $query->name('store_product_relation')->where('type', 3)->whereIn('relation_id', $where['store_label_id'])->field('product_id')->select();
                });
            })->when(isset($where['is_live']) && $where['is_live'] == 1, function ($query) use ($where) {
                $query->whereNotIn('id', function ($query) {
                    $query->name('live_goods')->where('is_del', 0)->where('audit_status', '<>', 3)->field('product_id')->select();
                });
            });
    }

    /**
     * 条件获取数量
     * @param array $where
     * @return int
     * @throws DbException
     */
    public function getCount(array $where): int
    {
        if (!empty($where['cate_id']) && is_array($where['cate_id'])) {
            return $this->getModel()->alias('a')
                ->join('store_product_relation r', 'r.product_id = a.id')
                ->when(isset($where['unit_name']) && $where['unit_name'] !== '', function ($query) use ($where) {
                    $query->where('a.unit_name', $where['unit_name']);
                })->when(isset($where['ids']) && $where['ids'], function ($query) use ($where) {
                    if (!isset($where['type'])) $query->where('a.id', 'in', $where['ids']);
                })->when(isset($where['not_ids']) && $where['not_ids'], function ($query) use ($where) {
                    $query->whereNotIn('a.id', $where['not_ids']);
                })->when(isset($where['status']) && '' !== $where['status'], function ($query) use ($where) {
                    $value = $where['status'];
                    switch ((int)$value) {
                        case -2://强制下架
                            $query->where(['a.is_verify' => -2]);
                            break;
                        case -1://审核未通过
                            $query->where(['a.is_verify' => -1]);
                            break;
                        case 0://待审核
                            $query->where(['a.is_verify' => 0, 'a.is_del' => 0]);
                            break;
                        case 1:
                            $query->where(['a.is_show' => 1, 'a.is_del' => 0]);
                            break;
                        case 2:
                            $query->where(['a.is_show' => 0, 'a.is_del' => 0]);
                            break;
                        case 3:
                            $query->where(['a.is_del' => 0]);
                            break;
                        case 4:
                            $query->where(['a.is_del' => 0, 'a.is_verify' => 1, 'a.is_sold' => 1])->where(function ($query) {
                                $query->whereOr('stock', 0);
                            });
                            break;
                        case 5:
//                            if (isset($data['store_stock']) && $data['store_stock']) {
//                                $store_stock = $data['store_stock'];
//                                $query->where(['a.is_show' => 1, 'a.is_del' => 0, 'a.is_verify' => 1, 'a.is_police' => 1])->where('a.stock', '<=', $store_stock)->where('a.stock', '>', 0);
//                            } else {
                            $query->where(['a.is_show' => 1, 'a.is_del' => 0, 'a.is_verify' => 1, 'a.is_police' => 1])->where('a.stock', '>', 0);
//                            }
                            break;
                        case 6:
                            $query->where(['a.is_del' => 1]);
                            break;
                        case 7://回收站 & 下架商品
                            $query->where(function ($q) {
                                $q->where(['a.is_del' => 1])->whereOr('a.is_show', 0);
                            });
                            break;
                    };
                })->when(isset($where['pids']) && $where['pids'], function ($query) use ($where) {
                    if ((isset($where['priceOrder']) && $where['priceOrder'] != '') || (isset($where['salesOrder']) && $where['salesOrder'] != '')) {
                        $query->whereIn('a.pid', $where['pids']);
                    } else {
                        $query->whereIn('a.pid', $where['pids'])->orderField('pid', $where['pids'], 'asc');
                    }
                })->when(isset($where['not_pids']) && $where['not_pids'], function ($query) use ($where) {
                    $query->whereNotIn('a.pid', $where['not_pids']);
                })->where('r.type', 1)
                ->where('r.relation_id', 'IN', $where['cate_id'])
                ->group('a.id')
                ->count('a.id');
        } else {
            return $this->search($where)
                ->when(isset($where['unit_name']) && $where['unit_name'] !== '', function ($query) use ($where) {
                    $query->where('unit_name', $where['unit_name']);
                })->when(isset($where['ids']) && $where['ids'], function ($query) use ($where) {
                    if (!isset($where['type'])) $query->where('id', 'in', $where['ids']);
                })->when(isset($where['not_ids']) && $where['not_ids'], function ($query) use ($where) {
                    $query->whereNotIn('id', $where['not_ids']);
                })->when(isset($where['pids']) && $where['pids'], function ($query) use ($where) {
                    if ((isset($where['priceOrder']) && $where['priceOrder'] != '') || (isset($where['salesOrder']) && $where['salesOrder'] != '')) {
                        $query->whereIn('pid', $where['pids']);
                    } else {
                        $query->whereIn('pid', $where['pids'])->orderField('pid', $where['pids'], 'asc');
                    }
                })->when(isset($where['not_pids']) && $where['not_pids'], function ($query) use ($where) {
                    $query->whereNotIn('pid', $where['not_pids']);
                })->count();
        }
    }

    /**
     * 获取商品列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param array $with
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getList(array $where, int $page = 0, int $limit = 0, string $order = '', array $with = [])
    {
        $prefix = Config::get('database.connections.' . Config::get('database.default') . '.prefix');
        return $this->search($where)->order(($order ? $order . ' ,' : '') . 'sort desc,id desc')
            ->when(count($with), function ($query) use ($with) {
                $query->with($with);
            })->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->when(isset($where['ids']), function ($query) use ($where) {
                if (!isset($where['type'])) $query->where('id', 'in', $where['ids']);
            })->field([
                '*',
                '(SELECT count(*) FROM `' . $prefix . 'user_relation` WHERE `relation_id` = `' . $prefix . 'store_product`.`id` AND `category` = \'product\' AND `type` = \'collect\') as collect',
                '(SELECT count(*) FROM `' . $prefix . 'user_relation` WHERE `relation_id` = `' . $prefix . 'store_product`.`id` AND `category` = \'product\' AND `type` = \'like\') as likes',
                '(SELECT SUM(stock) FROM `' . $prefix . 'store_product_attr_value` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = 0) as stock',
                //                '(SELECT SUM(sales) FROM `' . $prefix . 'store_product_attr_value` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = 0) as sales',
                '(SELECT count(*) FROM `' . $prefix . 'store_visit` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `product_type` = \'product\') as visitor',
            ])->select()->toArray();
    }

    /**
     * 获取商品详情
     * @param int $id
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getInfo(int $id)
    {
        return $this->search()->with('coupons')->find($id);
    }

    /**
     * 获取门店商品
     * @param $where
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getBranchProduct($where)
    {
        return $this->search($where)->find();
    }

    /**
     * 条件获取商品列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @param array $field
     * @param string $order
     * @param array $with
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getSearchList(array $where, int $page = 0, int $limit = 0, array $field = ['*'], string $order = '', array $with = ['couponId', 'descriptions'])
    {
        if (isset($where['star'])) $with[] = 'star';
        return $this->search($where)->with($with)->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->when(isset($where['ids']) && $where['ids'], function ($query) use ($where) {
            if ((isset($where['priceOrder']) && $where['priceOrder'] != '') || (isset($where['salesOrder']) && $where['salesOrder'] != '')) {
                $query->whereIn('id', $where['ids']);
            } else {
                $query->whereIn('id', $where['ids'])->orderField('id', $where['ids'], 'asc');
            }
        })->when(isset($where['not_ids']) && $where['not_ids'], function ($query) use ($where) {
            $query->whereNotIn('id', $where['not_ids']);
        })->when(isset($where['pids']) && $where['pids'], function ($query) use ($where) {
            if ((isset($where['priceOrder']) && $where['priceOrder'] != '') || (isset($where['salesOrder']) && $where['salesOrder'] != '')) {
                $query->whereIn('pid', $where['pids']);
            } else {
                $query->whereIn('pid', $where['pids'])->orderField('pid', $where['pids'], 'asc');
            }
        })->when(isset($where['not_pids']) && $where['not_pids'], function ($query) use ($where) {
            $query->whereNotIn('pid', $where['not_pids']);
        })->when(isset($where['priceOrder']) && $where['priceOrder'] != '', function ($query) use ($where) {
            if ($where['priceOrder'] === 'desc') {
                $query->order("price desc");
            } else {
                $query->order("price asc");
            }
        })->when(isset($where['salesOrder']) && $where['salesOrder'] != '', function ($query) use ($where) {
            if ($where['salesOrder'] === 'desc') {
                $query->order("sales desc");
            } else {
                $query->order("sales asc");
            }
        })->when(isset($where['defaultOrder']) && in_array($where['defaultOrder'], [0, 1, 2]), function ($query) use ($where) {
            switch ($where['defaultOrder']) {
                case 0://默认排序
                    $query->order('sort desc,id desc');
                    break;
                case 1://好评
                    $query->order('star desc,sort desc');
                    break;
                case 2://新品
                    $query->order('id desc,sort desc');
                    break;
                default:
                    $query->order('sort desc,id desc');
                    break;
            }
        })->when(!isset($where['ids']) || !$where['ids'], function ($query) use ($where, $order) {
            if (isset($where['timeOrder']) && $where['timeOrder'] == 1) {
                $query->order('id desc');
            } else if ($order == 'rand') {
                $query->orderRand();
            } else if ($order) {
                $query->orderRaw($order);
            } else {
                $query->order('sort desc,id desc');
            }
        })->when(isset($where['use_min_price']) && $where['use_min_price'], function ($query) use ($where) {
            if (is_array($where['use_min_price']) && count($where['use_min_price']) == 2) {
                $query->where('price', $where['use_min_price'][0] ?? '=', $where['use_min_price'][1] ?? 0);
            }
        })->when(!$page && $limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->field($field)->select()->toArray();
    }

    /**
     * 商品列表
     * @param array $where
     * @param $limit
     * @param $field
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getProductLimit(array $where, $limit, $field)
    {
        return $this->search($where)->field($field)->order('val', 'desc')->limit($limit)->select()->toArray();

    }

    /**
     * 根据id获取商品数据
     * @param array $ids
     * @param string $field
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function idByProductList(array $ids, string $field)
    {
        return $this->getModel()->whereIn('id', $ids)->field($field)->select()->toArray();
    }

    /**
     * 获取推荐商品
     * @param array $where
     * @param array $field
     * @param int $num
     * @param int $page
     * @param int $limit
     * @param array $with
     * @param string $order
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \ReflectionException
     */
    public function getRecommendProduct(array $where, array $field = ['*'], int $num = 0, int $page = 0, int $limit = 0, array $with = ['couponId'], string $order = 'sort DESC, id DESC')
    {
        return $this->search($where)->field($field)
            ->when(count($with), function ($query) use ($with) {
                $query->with($with);
            })->when($num, function ($query) use ($num) {
                $query->limit($num);
            })->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order($order)->select()->toArray();
    }

    /**
     * 获取加入购物车的商品
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getProductCartList(array $where, int $page, int $limit, array $field = ['*'])
    {
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        return $this->search($where)->when($page, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->field($field)->order('sort DESC,id DESC')->select()->toArray();
    }

    /**
     * 获取用户购买热销榜单
     * @param array $where
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserProductHotSale(array $where, int $limit = 20)
    {
        return $this->search($where)->field(['IFNULL(sales,0) + IFNULL(ficti,0) as sales', 'store_name', 'image', 'id', 'price', 'ot_price', 'stock'])->limit($limit)->order('sales desc')->select()->toArray();
    }

    /**
     * 通过商品id获取商品分类
     * @param array $productIds
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function productIdByCateId(array $productIds)
    {
        return $this->search(['id' => $productIds])->with('cateName')->field('id')->select()->toArray();
    }

    /**
     * @param array $where
     * @param $field
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getProductListByWhere(array $where, $field)
    {
        return $this->search($where)->field($field)->select()->toArray();
    }

    /**
     * 搜索条件获取字段column
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getColumnList(array $where, string $field = 'brand_id', string $key = 'id')
    {
        return $this->search($where)
            ->when(isset($where['ids']) && $where['ids'], function ($query) use ($where) {
                $query->whereIn('id', $where['ids']);
            })->field($field)->column($field, $key);
    }

    /**
     * 自动上下架
     * @param int $is_show
     * @return BaseModel
     */
    public function overUpperShelves($is_show = 0)
    {
        return $this->getModel()->where(['is_del' => 0])
            ->when(in_array($is_show, [0, 1]), function ($query) use ($is_show) {
                if ($is_show == 1) {
                    $query->where('is_show', 0)->where('auto_on_time', '<>', 0)->where('auto_on_time', '<=', time());
                } else {
                    $query->where('is_show', 1)->where('auto_off_time', '<>', 0)->where('auto_off_time', '<', time());
                }
            })->update(['is_show' => $is_show]);
    }

    /**
     * 获取预售列表
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getPresaleList(array $where, int $page, int $limit)
    {
        $model = $this->getModel()->where('is_presale_product', 1)->where('is_del', 0)
            ->where(function ($query) use ($where) {
                switch ($where['time_type']) {
                    case 1:
                        $query->where('presale_start_time', '>', time());
                        break;
                    case 2:
                        $query->where('presale_start_time', '<=', time())->where('presale_end_time', '>=', time());
                        break;
                    case 3:
                        $query->where('presale_end_time', '<', time());
                        break;
                }
            })->when(isset($where['presale_status']), function ($query) use ($where) {
                $query->where('presale_status', $where['presale_status']);
            });
        $count = $model->count();
        $list = $model->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->when(!$page && $limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->order('add_time desc')->select()->toArray();
        return compact('list', 'count');
    }

    /**
     * 获取使用某服务保障商品数量
     * @param int $ensure_id
     * @return int
     */
    public function getUseEnsureCount(int $ensure_id)
    {
        return $this->getModel()->whereFindInSet('ensure_id', $ensure_id)->count();
    }

    /**
     * 保存数据
     * @param array $data
     * @return mixed|\think\Collection
     * @throws \Exception
     */
    public function saveAll(array $data)
    {
        return $this->getModel()->saveAll($data);
    }

    /**
     * 同步商品保存获取id
     * @param $data
     * @return int|string
     */
    public function ErpProductSave($data)
    {
        return $this->getModel()->insertGetId($data);
    }

    /**
     * 获取商品关联，品牌、标签等
     * @param array $productWhere
     * @param array $relation_type
     * @param string $field
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getProductRelation(array $productWhere, array $relation_type = [], string $field = '*'): array
    {
        return $this->getModel()->alias('p')->leftJoin('store_product_relation r', 'p.id = r.product_id')
            ->field($field)->where($productWhere)->whereIn('r,type', $relation_type)->select()->toArray();
    }
}
