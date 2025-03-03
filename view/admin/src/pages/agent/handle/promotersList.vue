<template>
    <div>
        <Modal v-model="modals" scrollable footer-hide closable :title="listTitle === 'man' ? '统计推广人列表' : '推广订单'"
            :mask-closable="false" width="900" @on-cancel="onCancel">
            <div class="table_box">
                <Form ref="formValidate" inline :model="formValidate" :label-width="labelWidth"
                    :label-position="labelPosition" class="tabform" @submit.native.prevent>
                    <FormItem label="时间选择：">
                        <DatePicker :editable="false" clearable @on-change="onchangeTime" :value="timeVal" format="yyyy/MM/dd"
                            type="datetimerange" placement="bottom-start" placeholder="自定义时间" class="input-add"
                            :options="options"></DatePicker>
                    </FormItem>
                    <FormItem label="用户类型：">
                        <Select v-model="formValidate.type" clearable class="input-add" 
                            @on-change="userSearchs">
                            <Option v-for="(item, i) in userTypeList" :key="i" :value="item.val">{{ item.text }}
                            </Option>
                        </Select>
                    </FormItem>
                    <FormItem label="搜索：">
                        <Input placeholder="请输入请姓名、电话、UID" clearable v-model="formValidate.nickname" 
                        class="input-add"></Input>
                    </FormItem>
                    <FormItem label="订单号：" v-if="listTitle !== 'man'">
                        <Input placeholder="请输入请订单号" v-model="formValidate.order_id" clearable 
                        class="input-add mr14"></Input>
                    </FormItem>
                    <Button type="primary" @click="userSearchs()">查询</Button>
                </Form>
            </div>
            <Table ref="selection" :columns="columns4" :data="tabList" :loading="loading" no-data-text="暂无数据"
                highlight-row max-height="400" no-filtered-data-text="暂无筛选结果">
                <template slot-scope="{ row }" slot="brokerage_price">
                    <span v-if="row.division_id == uid">{{ row.division_brokerage }}</span>
                    <span v-else-if="row.division_agent_id == uid">{{ row.division_agent_brokerage }}</span>
                    <span v-else-if="row.division_staff_id == uid">{{ row.division_staff_brokerage }}</span>
                    <span v-else>{{ row.brokerage_price }}</span>
                </template>
            </Table>
            <div class="acea-row row-right page">
                <Page :total="total" show-elevator show-total @on-change="pageChange" :page-size="formValidate.limit" />
            </div>
        </Modal>
    </div>
</template>

<script>
import { mapState } from 'vuex';
import { stairListApi, getAgentOptionApi } from '@/api/agent';
import timeOptions from "@/utils/timeOptions";
export default {
    name: 'promotersList',
    data() {
        return {
            uid: '',
            modals: false,
            options: timeOptions,
            fromList: {
                title: '选择时间',
                custom: true,
                fromTxt: [
                    { text: '全部', val: '' },
                    { text: '今天', val: 'today' },
                    { text: '昨天', val: 'yesterday' },
                    { text: '最近7天', val: 'lately7' },
                    { text: '最近30天', val: 'lately30' },
                    { text: '本月', val: 'month' },
                    { text: '本年', val: 'year' }
                ]
            },
            formValidate: {
                limit: 15,
                page: 1,
                nickname: '',
                data: '',
                type: '',
                order_id: '',
                uid: 0
            },
            loading: false,
            tabList: [],
            total: 0,
            timeVal: [],
            columns4: [],
            listTitle: '',
            userTypeList:[]
        }
    },
    computed: {
        ...mapState('admin/layout', [
            'isMobile'
        ]),
        labelWidth() {
            return this.isMobile ? undefined : 100;
        },
        labelPosition() {
            return this.isMobile ? 'top' : 'right';
        }
    },
    methods: {
        onCancel() {
            this.formValidate = {
                limit: 15,
                page: 1,
                nickname: '',
                data: '',
                type: '',
                order_id: '',
                uid: 0
            };
            this.timeVal = [];
        },
        // 具体日期
        onchangeTime(e) {
            this.timeVal = e;
            this.formValidate.data = this.timeVal.join('-');
        },
        getOption(type){
          getAgentOptionApi(type).then(res=>{
            this.userTypeList = res.data.list;
          })
        },
        // 列表
        getList(row, tit) {
            this.listTitle = tit;
            this.rowsList = row;
            this.loading = true;
            let url = '';
            if (this.listTitle === 'man') {
                url = 'agent/stair';
            } else {
                url = 'agent/stair/order';
            }
            this.formValidate.uid = row.uid
            stairListApi(url, this.formValidate).then(async res => {
                let data = res.data
                this.tabList = data.list;
                this.total = data.count;
                if (this.listTitle === 'man') {
                    this.columns4 = [
                        {
                            title: 'UID',
                            minWidth: 80,
                            key: 'uid'
                        },
                        {
                            title: '头像',
                            key: 'avatar',
                            minWidth: 80,
                            render: (h, params) => {
                                return h('viewer', [
                                    h('div', {
                                        style: {
                                            width: '36px',
                                            height: '36px',
                                            borderRadius: '4px',
                                            cursor: 'pointer'
                                        }
                                    }, [
                                        h('img', {
                                            attrs: {
                                                src: params.row.avatar ? params.row.avatar : require('../../../assets/images/moren.jpg')
                                            },
                                            style: {
                                                width: '100%',
                                                height: '100%'
                                            }
                                        })
                                    ])
                                ]);
                            }
                        },
                        {
                            title: '用户信息',
                            key: 'nickname',
                            minWidth: 120
                        },
                        {
                            title: '是否推广员',
                            key: 'promoter_name',
                            minWidth: 100
                        },
                        {
                            title: '推广人数',
                            key: 'spread_count',
                            sortable: true,
                            minWidth: 90
                        },
                        {
                            title: '订单数',
                            key: 'order_count',
                            sortable: true,
                            minWidth: 90
                        },
                        {
                            title: '关注时间',
                            key: 'add_time',
                            sortable: true,
                            minWidth: 130
                        }
                    ];
                } else {
                    this.columns4 = [
                        {
                            title: '订单ID',
                            key: 'order_id'
                        },
                        {
                            title: '用户信息',
                            key: 'user_info'
                        },
                        {
                            title: '时间',
                            key: '_add_time'
                        },
                        {
                            title: '返佣金额',
                            slot: 'brokerage_price',
                            // render: (h, params) => {
                            //     return h('viewer', [
                            //         h('span', params.row.brokerage_price || 0)
                            //     ]);
                            // }
                        }
                    ];
                }
                this.loading = false;
            }).catch(res => {
                this.loading = false;
                this.tabList = [];
                this.$Message.error(res.msg);
            })
        },
        pageChange(page) {
            this.formValidate.page = page;
            this.getList(this.rowsList, this.listTitle);
        },
        // 搜索
        userSearchs() {
            this.formValidate.page = 1;
            this.getList(this.rowsList, this.listTitle);
        }
    }
}
</script>

<style scoped></style>
