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

const pre = 'setting_';

export default {
    path: '/supplier/setting/',
    name: 'setting',
    header: 'setting',
    meta: {
        // 授权标识
        auth: ['supplier-setting']
    },
    redirect: {
        name: `${pre}merchant`
    },
    component: BasicLayout,
    children: [
        {
            path: 'merchant',
            name: `${pre}merchant`,
            meta: {
                auth: ['supplier-setting-merchant'],
                title: '商户设置'
            },
            component: () => import('@/pages/setting/merchant/index')
        },
        {
            path: 'managers',
            name: `${pre}managers`,
            meta: {
                auth: ['supplier-setting-managers'],
                title: '管理员列表'
            },
            component: () => import('@/pages/setting/managers/index')
        },
        {
            path: 'ticket',
            name: `${pre}ticket`,
            meta: {
                auth: ['supplier-setting-ticket'],
                title: '小票打印'
            },
            props: {
                typeMole: 'third'
            },
            component: () => import('@/components/fromSubmit/commonForm.vue')
        },
        {
            path: 'document',
            name: `${pre}document`,
            meta: {
                auth: ['supplier-setting-document'],
                title: '单据设置'
            },
            component: () => import('@/pages/setting/document')
        },
        {
            path: 'document/content',
            name: `${pre}content`,
            meta: {
                auth: ['supplier-setting-document-content'],
                title: '小票配置'
            },
            component: () => import('@/pages/setting/document/content')
        },
    ]
};
