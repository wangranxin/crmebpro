<template>
    <div>
       <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
			<div class="new_card_pd">
                <Form ref="formValidate" :model="formValidate" inline
                :label-width="labelWidth" :label-position="labelPosition" 
                @submit.native.prevent>
                    <FormItem label="是否显示：">
                        <Select v-model="formValidate.status" placeholder="请选择" clearable class="input-add">
                            <Option value="1">显示</Option>
                            <Option value="0">不显示</Option>
                        </Select>
                    </FormItem>
                    <FormItem label="分类名称：" label-for="status2">
                        <Input placeholder="请输入分类名称" v-model="formValidate.title" class="input-add mr14"/>
                        <Button type="primary" @click="userSearchs">查询</Button>
                    </FormItem>
                </Form>
			</div>
		</Card>
        <Card :bordered="false" dis-hover class="ivu-mt">
            <Button type="primary" @click="classAdd" class="mr20">添加配置分类</Button>
            <vxe-table
                    :border="false"
                    class="ivu-mt"
                    highlight-hover-row
                    highlight-current-row
										row-id="id"
                    :loading="loading" ref="xTable"
                    header-row-class-name="false"
                    :tree-config="{children: 'children',reserve:true}"
                    :data="classList">
                <vxe-table-column field="id" title="ID"  tooltip width="80"></vxe-table-column>
                <vxe-table-column field="title" tree-node title="分类名称" min-width="150"></vxe-table-column>
                <vxe-table-column field="eng_title" title="分类字段"  min-width="150"></vxe-table-column>
                <vxe-table-column field="statuss" title="状态" min-width="150">
                    <template v-slot="{ row }">
                        <i-switch v-model="row.status" :value="row.status" :true-value="1" :false-value="0" @on-change="onchangeIsShow(row)" size="large">
                            <span slot="open">显示</span>
                            <span slot="close">隐藏</span>
                        </i-switch>
                    </template>
                </vxe-table-column>
                <vxe-table-column field="action" title="操作"  width="180" fixed="right">
                    <template v-slot="{ row,index }">
                        <a @click="goList(row)">配置列表</a>
                        <Divider type="vertical"/>
                        <a @click="edit(row)">编辑</a>
                        <Divider type="vertical"/>
                        <a @click="del(row,'删除分类',index)">删除</a>
                    </template>
                </vxe-table-column>
            </vxe-table>
            <div class="acea-row row-right page">
                <Page :total="total" :current="formValidate.page" show-elevator show-total @on-change="pageChange"
                      :page-size="formValidate.limit"/>
            </div>
        </Card>

        <!-- 新建  编辑表单-->
        <edit-from ref="edits" :FromData="FromData" @submitFail="submitFail"></edit-from>
    </div>
</template>

<script>
    import { mapState } from 'vuex';
    import editFrom from '@/components/from/from';
    import { classListApi, classAddApi, classEditApi, setStatusApi } from '@/api/system';
    export default {
        name: 'configTab',
        components: { editFrom },
        data () {
            return {
                grid: {
                    xl: 7,
                    lg: 7,
                    md: 12,
                    sm: 24,
                    xs: 24
                },
                loading: false,
                formValidate: {
                    status: '',
                    page: 1,
                    limit: 100,
                    title: ''
                },
                total: 0,
                FromData: null,
                classId: 0,
                classList: []
            }
        },
        computed: {
            ...mapState('admin/layout', [
                'isMobile'
            ]),
            labelWidth () {
                return this.isMobile ? undefined : 75;
            },
            labelPosition () {
                return this.isMobile ? 'top' : 'right';
            }
        },
        mounted () {
            this.getList();
        },
        methods: {
            // 跳转到配置列表页面
            goList (row) {
                this.$router.push({ path: '/admin/system/config/system_config_tab/list/' + row.id });
            },
            // 添加配置分类
            classAdd () {
                classAddApi().then(async res => {
                    this.FromData = res.data;
                    this.$refs.edits.modals = true;
                }).catch(res => {
                    this.$Message.error(res.msg);
                })
            },
            // 编辑
            edit (row) {
                classEditApi(row.id).then(async res => {
                    if(res.data.status === false){
                        return this.$authLapse(res.data);
                    }
                    this.FromData = res.data;
                    this.$refs.edits.modals = true;
                }).catch(res => {
                    this.$Message.error(res.msg);
                })
            },
            // 删除
            del (row, tit, num) {
                let delfromData = {
                    title: tit,
                    num: num,
                    url: `setting/config_class/${row.id}`,
                    method: 'DELETE',
                    ids: ''
                };
                this.$modalSure(delfromData).then((res) => {
                    this.$Message.success(res.msg);
                    this.classList.splice(num, 1);
                    if (!this.classList.length) {
                      this.formValidate.page =
                          this.formValidate.page == 1 ? 1 : this.formValidate.page - 1;
                    }
                    this.getList();
                }).catch(res => {
                    this.$Message.error(res.msg);
                });
            },
            // 列表
            getList () {
                this.loading = true;
                this.formValidate.status = this.formValidate.status || '';
                classListApi(this.formValidate).then(async res => {
                    let data = res.data
                    this.classList = data.list;
                    this.total = data.count;
                    this.loading = false;
                }).catch(res => {
                    this.loading = false;
                    this.$Message.error(res.msg);
                })
            },
            pageChange (index) {
                this.formValidate.page = index;
                this.getList();
            },
            // 表格搜索
            userSearchs () {
                this.formValidate.page = 1;
                this.getList();
            },
            // 修改成功
            submitFail () {
                //this.getList();
            },
            // 修改是否显示
            onchangeIsShow (row) {
                let data = {
                    id: row.id,
                    status: row.status
                }
                setStatusApi(data).then(async res => {
                    this.$Message.success(res.msg);
                }).catch(res => {
                    this.$Message.error(res.msg);
                })
            }
        }
    }
</script>

<style scoped>
.input-add {
 width: 250px;
}
.mr14 {
 margin-right:14px
}
.vxe-table--render-default {
  font-size: 12px;
}
</style>
