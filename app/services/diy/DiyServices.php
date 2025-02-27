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

namespace app\services\diy;

use app\services\BaseServices;
use app\dao\diy\DiyDao;
use app\services\other\QrcodeServices;
use app\services\system\config\SystemGroupDataServices;
use app\services\system\config\SystemGroupServices;
use crmeb\exceptions\AdminException;
use crmeb\services\SystemConfigService;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 首页diy
 * Class DiyServices
 * @package app\services\diy
 * @mixin DiyDao
 */
class DiyServices extends BaseServices
{
    /**
     * @var string
     */
    protected string $cacheKey = 'diy_cache';

    /**
     * 数据类型
     * @var array|string[]
     */
    protected array $type = [
        1 => 'diy',//diy数据
        3 => '',//个人中心、分类、商品详情可视化数据
    ];

    /**
     * @var array|string[]
     */
    protected array $templateName = [
        'category' => '商品分类',
        'color_change' => '主题换色',
        'member' => '个人中心',
        'product_detail' => '商品详情',
    ];

    /**
     * 个人中心可视化
     * @var array
     */
    public array $member = [
        'member' => [
            'style' => 1,
            'property' => [0, 1, 2, 3, 4]
        ],
        'order' => [// 订单
            'style' => 1,// 风格
        ],
        'orderStatic' => [
            'style' => 1,//风格
            'is_show' => 1,
        ],
        'poster' => [
            'is_show' => 1,
            'list' => [],
        ],
        'menu' => [
            'title' => '我的服务',
            'is_show' => 1,
            'style' => 1,
            'list' => [],
        ],
    ];

    /**
     * 商品详情diy默认数据
     * @var array
     */
    protected array $productDetail = [
        'navList' => [0, 1, 2, 3, 4], // 顶部菜单内容
        'openShare' => 1, //是否开启分享
        'pictureConfig' => 0, //轮播图模式 0 固定方图 1 高度自适应
        'swiperDot' => 1, //是否展示轮播指示点
        'showPrice' => [0, 1], //是否显示付费会员价和等级会员
        'isOpen' => [0, 1, 2], //是否展示0 原价 1 累计销量 2 库存
        'showSvip' => 1, //是否展示付费会员卡片
        'showRank' => 1, // 是否展示 排行榜卡片
        'showService' => [0, 1, 2, 3], //服务区卡片 0 营销活动入口 1 sku选择 2 服务保障 3 商品参数
        'showReply' => 1, //是否展示评论区
        'replyNum' => 3, //评论数量
        'showMatch' => 1, //是否展示搭配购
        'matchNum' => 3, //搭配套餐数量
        'showRecommend' => 1, //是否展示推荐商品
        'recommendNum' => 12, //推荐商品数量
        'menuList' => [0, 1, 2], //底部左侧菜单
        'showCart' => 1, //是否显示购物车
        'showCommunity' => 1,//种草修 0不显示,1显示
        'communityNum' => 3,//显示数量
        'showPromoter' => 1,//分销模块显示数量
        'showPromoterType' => 0,//分销模块显示类型
        'menuStatus'=>0,///底部菜单状态 0 常规版 1 分销版
        'showMenuPromoterShare'=>0,//0 全部用户 1 分销员 2 普通用户
    ];
    /**
     * 商品分类可视化默认数据
     * @var array
     */
    protected array $productCategory = [
        'level' => 2,//二级分类
        'index' => 1,//样式1
    ];

    protected $suspended = [
        'is_show' => 1,//是否显示
        'index' => 1,//样式1
        'shifting' => 1,//位置
        'main_ago_image' => '',//主按钮图 展开前
        'main_after_image' => '',//主按钮图 展开后
        'button' => [
            [
                'img'=> '',
                'url'=> '',
            ],
            [
                'img'=> '',
                'url'=> '',
            ],
            [
                'img'=> '',
                'url'=> '',
            ],
            [
                'img'=> '',
                'url'=> '',
            ],
        ]//子按钮
    ];

    /**
     * @var DiyDao
     */
    #[Inject]
    protected DiyDao $dao;

    /**
     * 获取DIY列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiyList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_del'] = 0;
        $list = $this->dao->getDiyList($where, $page, $limit, ['is_diy', 'template_name', 'id', 'title', 'name', 'type', 'add_time', 'update_time', 'status', 'cover_image']);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 保存资源
     * @param int $id
     * @param array $data
     * @return int
     */
    public function saveData(int $id = 0, array $data = [])
    {
        if ($id) {
            if ($data['type'] === '') {
                unset($data['type']);
            }
            $data['update_time'] = time();
            $res = $this->dao->update($id, $data);
            if (!$res) throw new AdminException('修改失败');
        } else {
            $data['add_time'] = time();
            $data['update_time'] = time();
            $data['is_diy'] = 1;
            $res = $this->dao->save($data);
            if (!$res) throw new AdminException('保存失败');
            $id = $res->id;
        }

        $this->dao->cacheTag()->clear();
        $this->dao->cacheTag()->set('index_diy_' . $id, $data['version']);
        $this->updateCacheDiyVersion();

        event('diy.update', [$id, $data]);
        return $id;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/9
     */
    public function updateCacheDiyVersion()
    {
        $diyInfo = $this->dao->get(['status' => 1, 'type' => 1, 'is_diy' => 1], ['id', 'version']);
        if (!$diyInfo) {
            $this->dao->cacheHander()->delete('index_diy_default');
        } else {
            $this->dao->cacheTag()->set('index_diy_default', $diyInfo['version']);
        }
    }

    /**
     * @return mixed
     * @throws \Throwable
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/9
     */
    public function getCacheDiyVersion()
    {
        return $this->dao->cacheTag()->remember('index_diy_default', function () {
            return $this->dao->value(['status' => 1, 'type' => 1, 'is_diy' => 1], 'version');
        });
    }

    /**
     * 删除DIY模板
     * @param int $id
     */
    public function del(int $id)
    {
        if ($id == 1) throw new AdminException('默认模板不能删除');
        $count = $this->dao->getCount(['id' => $id, 'status' => 1]);
        if ($count) throw new AdminException('该模板使用中，无法删除');
        $res = $this->dao->update($id, ['is_del' => 1]);
        if (!$res) throw new AdminException('删除失败，请稍后再试');

        $this->dao->cacheTag()->clear();
        $this->updateCacheDiyVersion();
        $this->dao->cacheHander()->delete('index_diy_' . $id);

        event('diy.update');
    }

    /**
     * 设置模板使用
     * @param int $id
     */
    public function setStatus(int $id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new AdminException('默认不存在');
        }
        if (!$info['is_show']) {
            throw new AdminException('请编辑开启页面状态后，再设置为首页');
        }
        $this->dao->update($info['type'], ['status' => 0], 'type');
        $this->dao->update($id, ['status' => 1, 'update_time' => time()]);

        $this->dao->cacheTag()->clear();
        $this->updateCacheDiyVersion();

        event('diy.update');
        return ['status' => 1, 'msg' => '设置成功'];
    }

    /**
     * 获取页面数据
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiy(string $name)
    {
        $data = [];
        if ($name == '') {
            $info = $this->dao->getOne(['status' => 1, 'type' => 1]);
        } else {
            $info = $this->dao->getOne(['template_name' => $name]);
        }
        if ($info) {
            $info = $info->toArray();
            $data = json_decode($info['value'], true);
        }
        return $data;
    }

    /**
     * 返回当前diy版本
     * @param int $id
     * @return mixed
     * @throws \Throwable
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/9
     */
    public function getDiyVersion(int $id)
    {
        if ($id) {
            return $this->dao->cacheTag()->remember('index_diy_' . $id, function () use ($id) {
                return $this->dao->value(['id' => $id], 'version');
            });
        } else {
            return $this->getCacheDiyVersion();
        }
    }

    /**
     * 获取diy详细数据
     * @param int $id
     * @return array|object
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiyInfo(int $id)
    {
        return $this->dao->cacheTag()->remember('diy_info_' . $id, function () use ($id) {
            $field = ['title', 'value', 'is_show', 'is_bg_color', 'color_picker', 'bg_pic', 'bg_tab_val', 'is_bg_pic', 'order_status'];
            $diyInfo = $this->dao->get($id ?: ['status' => 1, 'type' => 1, 'is_diy' => 1], $field);
            if (!$diyInfo) {
                $diyInfo = $this->dao->get(['template_name' => 'default'], $field);
            }
            if ($diyInfo) {
                $diyInfo = $diyInfo->toArray();
                $diyInfo['value'] = json_decode($diyInfo['value'], true);
                $value = [];
                foreach ($diyInfo['value'] as $key => $item) {
                    if ($item['name'] == 'customerService') {
                        $item['routine_contact_type'] = SystemConfigService::get('routine_contact_type', 0);
                    }
                    if ($item['name'] == 'promotionList') {
                        unset($item['titleShow']['title'],
                            $item['opriceShow']['title'],
                            $item['priceShow']['title'],
                            $item['couponShow']['title']);
                    }
                    if ($item['name'] == 'activeParty') {
                        unset($item['titleConfig']['place'],
                            $item['titleConfig']['max'],
                            $item['desConfig']['place'],
                            $item['desConfig']['max']
                        );
                        if (isset($item['menuConfig']['list']['info'])) {
                            foreach ($item['menuConfig']['list']['info'] as $k => $v) {
                                unset($v['tips'], $v['max']);
                                $item['menuConfig']['list']['info'][$k] = $v;
                            }
                        }
                    }

                    if ($item['name'] == 'pageFoot' && !$id) {
                        continue;
                    }
                    $value[$key] = $item;
                }
                $diyInfo['value'] = $value;
                return $diyInfo;
            } else {
                return [];
            }
        });
    }

    /**
     * 获取底部导航
     * @param string $template_name
     * @return array|mixed
     */
    public function getNavigation(string $template_name)
    {
        return $this->dao->cacheTag()->remember('navigation_' . $template_name, function () use ($template_name) {
            $value = $this->dao->value($template_name ? ['template_name' => $template_name] : ['status' => 1, 'type' => 1], 'value');
            if (!$value) {
                $value = $this->dao->value(['template_name' => 'default'], 'value');
            }
            $navigation = [];
            if ($value) {
                $value = json_decode($value, true);
                foreach ($value as $item) {
                    if (isset($item['name']) && strtolower($item['name']) === 'pagefoot') {
                        $navigation = $item;
                        break;
                    }
                }
            }
            return $navigation;
        });
    }

    /**
     * 获取换色/分类数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getColorChange(string $name)
    {
        $key = 'color_change_' . $name . '_3';
        return $this->dao->cacheStrRemember($key, function () use ($name) {
            return $this->dao->value(['template_name' => $name, 'type' => 3], 'value');
        });
    }

    /**
     * 取单个diy小程序预览二维码
     * @param int $id
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRoutineCode(int $id)
    {
        $diy = $this->dao->getOne(['id' => $id, 'is_del' => 0]);
        if (!$diy) {
            throw new AdminException('数据不存在');
        }
        $type = $diy['status'] ? 99 : 106;
        $name = $type == 106 ? $id . 'routine_annex_index_code.jpg' : $id . 'routine_index_code.jpg';
        /** @var QrcodeServices $QrcodeService */
        $QrcodeService = app()->make(QrcodeServices::class);
        $image = $QrcodeService->getRoutineQrcodePath($id, 0, $type, $name, true);
        return $image;
    }

    /**
     * 获取个人中心数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMemberData()
    {
        $key = 'diy_data_member_4';
        $info = $this->dao->cacheRemember($key, function () {
            $info = $this->dao->get(['template_name' => 'member', 'type' => 3]);
            if ($info) {
                return $info->toArray();
            } else {
                return null;
            }
        });
        if (!$info) {
            $member = $this->member;
        } else {
            $member = json_decode($info['value'], true);
            $member = $member ?: $this->member;
        }
        $data = [];
        $data['member'] = $member;
        $data['color_change'] = (int)$this->getColorChange('color_change');
        /** @var SystemGroupDataServices $systemGroupDataServices */
        $systemGroupDataServices = app()->make(SystemGroupDataServices::class);
        /** @var SystemGroupServices $systemGroupServices */
        $systemGroupServices = app()->make(SystemGroupServices::class);
        $menus_gid = $systemGroupServices->value(['config_name' => 'routine_my_menus'], 'id');
        $banner_gid = $systemGroupServices->value(['config_name' => 'routine_my_banner'], 'id');
        $routine_my_menus = $systemGroupDataServices->getGroupDataList(['gid' => $menus_gid]);
        $routine_my_menus = $routine_my_menus['list'] ?? [];
        $url = ['/kefu/mobile_list', '/pages/store_spread/index', '/pages/admin/order_cancellation/index', '/pages/admin/order/index'];
        foreach ($routine_my_menus as &$item) {
            if (!isset($item['type']) || !$item['type']) {
                $item['type'] = in_array($item['url'], $url) ? 2 : 1;
            }
        }
        $data['routine_my_menus'] = $routine_my_menus;
        $routine_my_banner = $systemGroupDataServices->getGroupDataList(['gid' => $banner_gid]);
        $routine_my_banner = $routine_my_banner['list'] ?? [];
        $data['routine_my_banner'] = $routine_my_banner;
        $data['h5_avatar'] = sys_config('h5_avatar', '');
        return $data;
    }

    /**
     * 保存个人中心数据配置
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function memberSaveData(array $data)
    {
        $template_name = 'member';
        $info = $this->dao->get(['template_name' => $template_name, 'type' => 3]);
        $value = $data['member'];
        $routine_my_banner = $data['routine_my_banner'] ?? [];
        $routine_my_menus = $data['routine_my_menus'] ?? [];
        $update['value'] = json_encode($value);
        if ($info) {
            $update['update_time'] = time();
            if (!$this->dao->update($info['id'], $update)) {
                throw new AdminException('编辑保存失败');
            }
            $data = array_merge($info->toArray(), $update);
            $key = 'diy_data_member_4';
            $this->dao->cacheUpdate($data, $key);
        } else {
            $update['name'] = '个人中心';
            $update['title'] = '个人中心可视化';
            $update['template_name'] = $template_name;
            $update['type'] = 3;
            $update['add_time'] = time();
            $this->dao->save($update);
        }
        /** @var SystemGroupDataServices $systemGroupDataServices */
        $systemGroupDataServices = app()->make(SystemGroupDataServices::class);
//        $systemGroupDataServices->saveAllData($routine_my_banner, 'routine_my_banner');
//        $systemGroupDataServices->saveAllData($routine_my_menus, 'routine_my_menus');
        return true;
    }

    /**
     * 获取商品详情diy数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductDetailDiy()
    {
        $name = 'product_detail';
        $key = $this->cacheKey . '_' . $name;
        $default = $this->productDetail;
        return $this->dao->cacheTag()->remember($key, function () use ($name, $default) {
            $info = $this->dao->get(['template_name' => $name, 'type' => 3]);
            if ($info) {
                $result = json_decode($info['value'], true);
                $result = array_merge($default, array_intersect_key($result, $default));
            } else {
                $result = $default;
            }
            return $result;
        });
    }

    /**
     * 保存商品详情diy数据
     * @param array $content
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveProductDetailDiy(array $content)
    {
        $name = 'product_detail';
        $key = $this->cacheKey . '_' . $name;
        $info = $this->dao->get(['template_name' => $name, 'type' => 3]);
        $data = [];
        $data['value'] = json_encode($content);
        $time = time();
        if ($info) {
            $data['update_time'] = $time;
            if (!$this->dao->update($info['id'], $data)) {
                throw new AdminException('编辑保存失败');
            }
        } else {
            $data['name'] = '商品详情';
            $data['title'] = '商品详情可视化';
            $data['template_name'] = 'product_detail';
            $data['type'] = 3;
            $data['add_time'] = $time;
            $this->dao->save($data);
        }
        $this->dao->cacheTag()->clear();
        return true;
    }

    /**
     * 获取商品分类diy数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductCategoryDiy()
    {
        $name = 'category';
        $key = $this->cacheKey . '_' . $name;
        $default = $this->productCategory;
        return $this->dao->cacheTag()->remember($key, function () use ($name, $default) {
            $info = $this->dao->get(['template_name' => $name, 'type' => 3]);
            if ($info) {
                $result = json_decode($info['value'], true);
                $result = $result ? array_merge($default, $result) : $default;
            } else {
                $result = $default;
            }
            return $result;
        });
    }

    /**
     * 保存商品diy数据
     * @param array $content
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveProductCategoryDiy(array $content)
    {
        $name = 'category';
        $key = $this->cacheKey . '_' . $name;
        $info = $this->dao->get(['template_name' => $name, 'type' => 3]);
        $data = [];
        $data['value'] = json_encode($content);
        $time = time();
        if ($info) {
            $data['update_time'] = $time;
            if (!$this->dao->update($info['id'], $data)) {
                throw new AdminException('编辑保存失败');
            }
        } else {
            $data['name'] = '商品分类';
            $data['title'] = '商品分类可视化';
            $data['template_name'] = $name;
            $data['type'] = 3;
            $data['add_time'] = $time;
            $this->dao->save($data);
        }
        $this->dao->cacheTag()->clear();
        return true;
    }

    public function getSuspendedDiy()
    {
        $name = 'suspended_window';
        $key = $this->cacheKey . '_' . $name;
        $default = $this->suspended;
        return $this->dao->cacheTag()->remember($key, function () use ($name, $default) {
            $info = $this->dao->get(['template_name' => $name, 'type' => 3]);
            if ($info) {
                $result = json_decode($info['value'], true);
                $result = array_merge($default, array_intersect_key($result, $default));
            } else {
                $result = $default;
            }
            return $result;
        });
    }

    public function saveSuspendedDiy(array $content)
    {
        $name = 'suspended_window';
        $key = $this->cacheKey . '_' . $name;
        $info = $this->dao->get(['template_name' => $name, 'type' => 3]);
        $data = [];
        $data['value'] = json_encode($content);
        $time = time();
        if ($info) {
            $data['update_time'] = $time;
            if (!$this->dao->update($info['id'], $data)) {
                throw new AdminException('编辑保存失败');
            }
        } else {
            $data['name'] = '悬浮窗';
            $data['title'] = '悬浮窗可视化';
            $data['template_name'] = 'suspended_window';
            $data['type'] = 3;
            $data['add_time'] = $time;
            $this->dao->save($data);
        }
        $this->dao->cacheTag()->clear();
        return true;
    }
}
