<template>
  <div>
    <div class="i-layout-page-header">
      <PageHeader class="product_tabs" hidden-breadcrumb>
        <div slot="title" class="acea-row row-middle">
          <router-link :to="{ path: '/admin/user/list' }">
            <div class="font-sm after-line">
              <span class="iconfont iconfanhui"></span>
              <span class="pl10">返回</span>
            </div>
          </router-link>
          <span class="mr20 ml16">用户导入记录</span>
        </div>
      </PageHeader>
    </div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="new_card_pd">
        <!-- 筛选条件 -->
        <Form
          ref="formValidate"
          inline
          :model="formValidate"
          :label-width="80"
          label-position="right"
          @submit.native.prevent
        >
          <FormItem label="报表名称：">
            <Input size="default" placeholder="请输入" clearable
              v-model="formValidate.name"
              class="w-250"
            />
          </FormItem>
          <FormItem label="创建时间：">
            <DatePicker
              :editable="false"
              @on-change="onchangeTime"
              :value="timeVal"
              format="yyyy/MM/dd"
              type="datetimerange"
              placement="bottom-start"
              placeholder="自定义时间"
              class="w-250"
              :options="options"
            ></DatePicker>
          </FormItem>
          <Button type="primary" @click="pageChange(1)">查询</Button>
          <Button @click="reloadPage" class="ml-14">刷新</Button>
        </Form>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <!-- 商品评论表格 -->
      <Table
        ref="table"
        :columns="columns"
        :data="tableList"
        class="ivu-mt"
        :loading="loading"
        no-data-text="暂无数据"
        no-filtered-data-text="暂无筛选结果"
      >
        <template slot-scope="{ row, index }" slot="status">
          <Tag color="default" size="medium" v-if="row.status == 0">正在导入</Tag>
          <Tag color="success" size="medium" v-else>导入完成</Tag>
        </template>
        <template slot-scope="{ row, index }" slot="id">
          <span>{{ row.total_count - row.fail_count }}</span>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <a @click="delRecord(row, '删除记录', index)" v-show="row.status == 1">删除</a>
          <Divider type="vertical" v-show="row.status == 1 && row.fail_count > 0" />
          <a @click="exports(row)" v-show="row.status == 1 && row.fail_count > 0">下载失败报表</a>
        </template>
      </Table>
      <div class="acea-row row-right page">
        <Page
          :total="total"
          :current="formValidate.page"
          show-elevator
          show-total
          @on-change="pageChange"
          :page-size="formValidate.limit"
        />
      </div>
    </Card>
  </div>
</template>
<script>
import { userImportRecordApi, userDownRecordApi } from "@/api/user";
import timeOptions from "@/utils/timeOptions";
import exportExcel from "@/utils/newToExcel.js";
export default {
  name: "userImportRecord",
  data(){
    return {
      options: timeOptions,
      timeVal:[],
      formValidate: {
        name: "",
        data: "",
        page: 1,
        limit: 20
      },
      tableList: [],
      total: 0,
      loading: false,
      columns: [
        {
          title: "报表名称",
          key: "name",
          minWidth: 150,
        },
        {
          title: "创建时间",
          key: "add_time",
          minWidth: 180,
        },
        {
          title: "导入状态",
          slot: "status",
          minWidth: 80,
        },
        {
          title: "预计导入数",
          key: "total_count",
          minWidth: 80,
        },
        {
          title: "实际导入数",
          slot: "id",
          minWidth: 80,
        },
        {
          title: "失败数",
          key: "fail_count",
          minWidth: 80,
        },
        {
          title: "下载次数",
          key: "down_count",
          minWidth: 80,
        },
        {
          title: "操作",
          slot: "action",
          fixed: "right",
          minWidth: 150,
        },
      ]
    }
  },
  mounted() {
    this.getList();
  },
  methods:{
    onchangeTime(e) {
      this.timeVal = e;
      this.formValidate.time = this.timeVal[0] ? this.timeVal.join("-") : "";
      this.formValidate.page = 1;
      this.getList();
    },
    getList() {
      this.loading = true;
      userImportRecordApi(this.formValidate).then(async (res) => {
          let data = res.data;
          this.tableList = data.list;
          this.total = res.data.count;
          this.loading = false;
        })
        .catch((res) => {
          this.loading = false;
          this.$Message.error(res.msg);
        });
    },
    pageChange(index) {
      this.formValidate.page = index;
      this.getList();
    },
    // 删除
    delRecord(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `user/import_user/delete/${row.id}`,
        method: "DELETE",
        ids: "",
      };
      this.$modalSure(delfromData).then((res) => {
          this.$Message.success("删除成功");
          this.getList();
        }).catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 数据导出；
    async exports(row) {
      let [th, filekey, data, fileName] = [[], [], [], ""];
      let lebData = await this.getExcelData(row.id);
      if (!fileName) fileName = lebData.filename;
      filekey = lebData.filekey;
      if (!th.length) th = lebData.header; //表头
      data = data.concat(lebData.export);
      exportExcel(th, filekey, fileName, data);
    },
    getExcelData(id) {
      return new Promise((resolve, reject) => {
        userDownRecordApi({record_id: id}).then((res) => {
          return resolve(res.data);
        })
      });
    },
    reloadPage(){
      window.location.reload();
    }
  }
}
</script>
