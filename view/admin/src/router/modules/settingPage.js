// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
import BasicLayout from "@/layouts/basic-layout";

const meta = {
  auth: true,
};

const pre = "devise_";

export default {
  path: "/admin/setting/pages",
  name: "devise",
  header: "devise",
  // redirect: {
  //     name: `${pre}devise`
  // },
  component: BasicLayout,
  children: [
    {
      path: "/admin/setting/pages/devise",
      name: `${pre}devise`,
      meta: {
        auth: ["admin-setting-pages-devise"],
        title: "店铺装修",
      },
      component: () => import("@/pages/setting/devise/list"),
    },
    {
      path: "/admin/setting/pages/home",
      name: `${pre}devise`,
      meta: {
        auth: ["admin-setting-pages-devise"],
        title: "个人中心",
      },
      component: () => import("@/pages/setting/devise/users"),
    },
    {
      path: "/admin/setting/pages/product_category",
      name: `${pre}devise`,
      meta: {
        auth: ["admin-setting-pages-devise"],
        title: "商品分类",
      },
      component: () => import("@/pages/setting/devise/goodClass"),
    },
    {
      path: "/admin/setting/pages/product_detail",
      name: `${pre}devise`,
      meta: {
        auth: ["admin-setting-pages-devise"],
        title: "商品详情",
      },
      component: () => import("@/pages/setting/devise/newGoods"),
    },
    {
      path: "/admin/setting/theme_style",
      name: `${pre}themeStyle`,
      meta: {
        auth: ["admin-setting-theme_style"],
        title: "主题风格",
      },
      component: () => import("@/pages/setting/themeStyle/index"),
    },
    {
      path: "/admin/setting/system_visualization_data",
      name: `${pre}systemGroupData`,
      meta: {
        auth: ["admin-setting-system_visualization_data"],
        title: "开屏广告",
      },
      component: () => import("@/pages/system/group/visualization"),
    },
    {
      path: "/admin/setting/pc_group_data",
      name: `${pre}systemGroupDataGroup`,
      meta: {
        auth: ["setting-system-pc_data"],
        title: "PC商城",
      },
      component: () => import("@/pages/system/group/pc"),
    },
    {
      path: `/admin/setting/pages/fab`,
      name: `${pre}floatingAction`,
      meta: {
        auth: ['setting-system-fab'],
        title: '悬浮按钮'
      },
      component: () => import('@/pages/setting/devise/fab')
    }
  ],
};
