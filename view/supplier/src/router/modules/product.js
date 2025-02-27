// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
import BasicLayout from '@/layouts/basic-layout';
import Setting from '@/setting';

const pre = 'product_';

export default {
    path: `/supplier/product/`,
    name: 'product',
    header: 'product',
    meta: {
        // 授权标识
        auth: ['supplier-product']
    },
    redirect: {
        name: `${pre}productList`
    },
    component: BasicLayout,
    children: [
        {
            path: 'index',
            name: `${pre}productList`,
            meta: {
                title: '商品列表',
                auth: ['supplier-product-index']
            },
            component: () => import('@/pages/product/productList')
        },
        {
            path: 'product_attr',
            name: `${pre}productAttr`,
            meta: {
                auth: ['supplier-product-product_attr'],
                title: '商品规格'
            },
            component: () => import('@/pages/product/productAttr')
        },
        {
            path: 'product_reply',
            name: `${pre}productReply`,
            meta: {
                title: '商品评价',
                auth: ['supplier-product-product_reply']
            },
            component: () => import('@/pages/product/productReply')
        },
        {
            path: 'shipping_template',
            name: `${pre}productAttr`,
            meta: {
                auth: ['supplier-shipping_template'],
                title: '运费模板'
            },
            component: () => import('@/pages/product/shippingTemplates')
        },
        {
            path: 'edit_product/:id?',
            name: `${pre}productEdit`,
            meta: {
                auth: ['supplier-edit_product'],
                title: '添加商品',
            },
            component: () => import('@/pages/product/productEdit')
        }
    ]
};
