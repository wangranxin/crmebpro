<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt box">
      <Form
        ref="formValidate"
        :model="formValidate"
        :label-width="labelWidth"
        :label-position="labelPosition"
        @submit.native.prevent
      >
        <FormItem label="时间选择：">
          <DatePicker
            :editable="false"
            :clearable="true"
            @on-change="onchangeTime"
            :value="timeVal"
            format="yyyy/MM/dd"
            type="datetimerange"
            placement="bottom-start"
            placeholder="自定义时间"
            style="width: 250px"
            :options="options"
          >
          </DatePicker>
        </FormItem>
      </Form>
    </Card>

    <Card :bordered="false" dis-hover class="ive-mt tablebox">
      <div class="product_tabs tabbox">
        <Tabs @on-click="onClickTab">
          <TabPane label="日账单" name="day" />
          <TabPane label="周账单" name="week" />
          <TabPane label="月账单" name="month" />
        </Tabs>
      </div>
      <div class="btnbox"></div>
      <div class="table">
        <Table
          :columns="columns"
          :data="orderList"
          ref="table"
          class="mt25"
          :loading="loading"
          highlight-row
          no-userFrom-text="暂无数据"
          no-filtered-userFrom-text="暂无筛选结果"
        >
          <template slot-scope="{ row, index }" slot="income_num">
            <span style="color: #f5222d">￥{{ row.income_num }}</span>
          </template>
          <template slot-scope="{ row, index }" slot="exp_num">
            <span style="color: #00c050">￥{{ row.exp_num }}</span>
          </template>
          <template slot-scope="{ row, index }" slot="entry_num">
            <span>￥{{ row.entry_num }}</span>
          </template>
          <template slot-scope="{ row, index }" slot="action">
            <a @click="Info(row)">账单详情</a>
            <Divider type="vertical" />
            <a @click="download(row)">下载</a>
          </template>
        </Table>
        <div class="acea-row row-right page">
          <Page
            :total="total"
            :current.sync="formValidate.page"
            show-elevator
            show-total
            @on-change="pageChange"
            :page-size="formValidate.limit"
          />
        </div>
      </div>
    </Card>
    <commission-details ref="commission"></commission-details>
  </div>
</template>

<script>
import exportExcel from '@/utils/newToExcel.js';
import commissionDetails from '../components/commissionDetails';
import { supplierFfundRecordApi, exportfundRecordApi } from '@/api/capital';
import timeOptions from '@/utils/timeOptions2';
export default {
  name: 'bill',
  components: {
    commissionDetails,
  },
  data() {
    return {
      total: 0,
      loading: false,
      tab: 'day',
      options: timeOptions,
      columns: [
        {
          title: 'ID',
          key: 'id',
          width: 60,
        },
        {
          title: '标题',
          key: 'title',
          minWidth: 80,
        },
        {
          title: '交易时间',
          key: 'add_time',
          minWidth: 150,
        },
        {
          title: '收入金额',
          slot: 'income_num',
          minWidth: 80,
        },
        {
          title: '支出金额',
          slot: 'exp_num',
          minWidth: 80,
        },
        {
          title: '供应商应入账金额',
          slot: 'entry_num',
          minWidth: 80,
        },
        {
          title: '操作',
          slot: 'action',
          fixed: 'right',
          minWidth: 120,
          align: 'center',
        },
      ],
      orderList: [
        {
          id: '1',
          order_id: '200',
          pay_price: '200',
          status: 1,
          phone: '13000000000',
          address: '100',
        },
      ],
      formValidate: {
        data: '',
        page: 1,
        limit: 20,
      },
      timeVal: [],
      fromList: {
        title: '选择时间',
        custom: true,
        fromTxt: [
          { text: '全部', val: '' },
          { text: '昨天', val: 'yesterday' },
          { text: '今天', val: 'today' },
          { text: '本周', val: 'week' },
          { text: '本月', val: 'month' },
          { text: '本季度', val: 'quarter' },
          { text: '本年', val: 'year' },
        ],
      },
    };
  },
  computed: {
    labelWidth() {
      return this.isMobile ? undefined : 80;
    },
    labelPosition() {
      return this.isMobile ? 'top' : 'left';
    },
  },
  watch: {
    tab: {
      handler() {
        this.getList();
      },
      immediate: true,
    },
    '$route': {
      handler(to){
        this.getList();
      },
      deep: true
    }
  },
  methods: {
    onClickTab(e) {
      this.tab = e;
    },
    getList() {
      this.loading = true;
      let data = {
        timeType: this.tab,
        data: this.formValidate.data,
        page: this.formValidate.page,
        limit: this.formValidate.limit,
        status: this.$route.params.type || ''
      };
      supplierFfundRecordApi(data).then((res) => {
        this.orderList = res.data.list;
        this.loading = false;
        this.total = res.data.count;
      });
    },
    // 选择时间
    selectChange(tab) {
      this.formValidate.page = 1;
      this.formValidate.data = tab;
      this.timeVal = [];
      this.getList();
    },
    // 具体日期
    onchangeTime(e) {
      this.timeVal = e;
      this.formValidate.data = this.timeVal[0] ? this.timeVal.join('-') : '';
      this.formValidate.page = 1;
      this.getList();
    },
    //分页
    pageChange(status) {
      this.formValidate.page = status;
      this.getList();
    },
    // 账单详情
    Info(row) {
      this.$refs.commission.modals = true;
      if(this.$route.params.type){
        let obj = {
          title: '完成时间',
          key: 'finish_time',
          minWidth: 150,
        };
        this.$refs.commission.columns.splice(3,0,obj);
      }
      this.$refs.commission.getList(row.ids);
    },
    //下载
    async download(row) {
      let [th, filekey, data, fileName] = [[], [], [], ''];
      let excelData = { ids: row.ids, page: 1 };
      let lebData = await this.getExcelData(excelData);
      if (!fileName) {
        fileName = lebData.filename;
      }
      if (!filekey.length) {
        filekey = lebData.filekey;
      }
      if (!th.length) {
        th = lebData.header;
      }
      data = [...data, ...lebData.export];
      exportExcel(th, filekey, fileName, data);
    },
    getExcelData(excelData) {
      return new Promise((resolve) =>
        exportfundRecordApi(excelData).then((res) => resolve(res.data))
      );
    },
  },
};
</script>

<style scoped lang="less">
/deep/.ivu-page-header,
/deep/.ivu-tabs-bar {
  border-bottom: 1px solid #ffffff;
}
/deep/.ivu-card-body {
  padding: 0;
}
/deep/.ivu-tabs-nav {
  height: 45px;
}

/deep/.ivu-tabs-bar {
  margin-bottom: 0px !important;
  border-bottom: 1px solid #e9e9e9 !important;
}
.tabbox {
  padding: 16px 20px 0px;
}
.box {
  padding: 20px;
  padding-bottom: 1px;
}
.tablebox {
  margin-top: 15px;
  padding-bottom: 10px;
}
.btnbox {
  padding: 20px 0px 0px 30px;
  .btns {
    width: 99px;
    height: 32px;
    background: #1890ff;
    border-radius: 4px;
    text-align: center;
    line-height: 32px;
    color: #ffffff;
    cursor: pointer;
  }
}
.table {
  padding: 0px 30px 15px 30px;
}
</style>
