<template>
<!-- 财务-提现申请 -->
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="card_pd">
        <!-- 查询条件 -->
        <Form
        ref="formValidate"
        inline
        :model="formValidate"
        :label-width="labelWidth"
        :label-position="labelPosition"
        @submit.native.prevent
      >
        <FormItem label="时间选择：">
          <DatePicker
            :editable="false"
            @on-change="onchangeTime"
            :value="timeVal"
            format="yyyy/MM/dd HH:mm"
            type="datetimerange"
            placement="bottom-end"
            placeholder="自定义时间"
           class="input-width"
            :options="options"
          ></DatePicker>
        </FormItem>

        <FormItem label="提现状态：">
          <Select clearable v-model="formValidate.status" class="input-add">
            <Option
              v-for="(itemn, indexn) in treeData.withdrawal"
              :value="itemn.value"
              :key="indexn"
              >{{ itemn.title }}</Option
            >
          </Select>
        </FormItem>

        <FormItem label="提现方式：">
          <Select clearable v-model="formValidate.extract_type" class="input-add">
            <Option
              v-for="(itemn, indexn) in treeData.payment"
              :value="itemn.value"
              :key="indexn"
              >{{ itemn.title }}</Option
            >
          </Select>
        </FormItem>

        <FormItem label="搜索：">
          <div class="acea-row row-middle">
            <Input
              placeholder="微信昵称/姓名/支付宝账号/银行卡号"
              element-id="name"
              v-model="formValidate.nireid"
               class="input-add"
            />
          </div>
        </FormItem>
        <FormItem>
          <Button type="primary" @click="selChange" class="btn-add">查询</Button>
          <Button @click="reset">重置</Button>
        </FormItem>
      </Form>
      </div>
    </Card>
    <cards-data :cardLists="cardLists" v-if="extractStatistics"></cards-data>
    <Card :bordered="false" dis-hover>
      <!-- 表格 -->
      <Table
        ref="table"
        :columns="columns"
        :data="tabList"
        class="ivu-mt"
        :loading="loading"
        no-data-text="暂无数据"
        no-filtered-data-text="暂无筛选结果"
      >
        <template slot-scope="{ row }" slot="nickname">
          <div>
            用户昵称: {{ row.nickname }} <br />
            用户id:{{ row.uid }}
          </div>
        </template>
        <template slot-scope="{ row }" slot="extract_price">
          <div>{{ row.extract_price }}</div>
        </template>
        <template slot-scope="{ row }" slot="extract_fee">
          <div>{{ row.extract_fee }}</div>
        </template>
        <template slot-scope="{ row }" slot="add_time">
          <span> {{ row.add_time | formatDate }}</span>
        </template>
        <template slot-scope="{ row }" slot="extract_type">
          <div class="type" v-if="row.extract_type === 'bank'">
            <div class="item">姓名:{{ row.real_name }}</div>
            <div class="item">银行卡号:{{ row.bank_code }}</div>
            <div class="item">银行开户地址:{{ row.bank_address }}</div>
          </div>
          <div class="type" v-if="row.extract_type === 'weixin'">
            <div class="item">昵称:{{ row.nickname }}</div>
            <div class="item">微信号:{{ row.wechat }}</div>
          </div>
          <div class="type" v-if="row.extract_type === 'alipay'">
            <div class="item">姓名:{{ row.real_name }}</div>
            <div class="item">支付宝号:{{ row.alipay_code }}</div>
          </div>
          <div class="type" v-if="row.extract_type === 'balance'">
            <div class="item">姓名:{{ row.real_name }}</div>
            <div class="item">提现方式：佣金转入余额</div>
          </div>
        </template>
        <template slot-scope="{ row }" slot="qrcode_url">
          <div v-if="['weixin','alipay'].includes(row.extract_type) && row.qrcode_url">
            <div class="tabBox_img" v-viewer>
              <img v-lazy="row.qrcode_url" />
            </div>
          </div>
          <span v-else>——</span>
        </template>
        <template slot-scope="{ row, index }" slot="status">
          <div class="status" v-if="row.status === 0">
            <div class="statusVal">申请中</div>
            <div>
              <Button
                type="error"
                icon="md-close"
                size="small"
                class="item"
                @click="invalid(row)"
                >无效</Button
              >
              <Button
                type="info"
                icon="md-checkmark"
                size="small"
                class="item"
                @click="adopt(row, '审核通过', index)"
                >通过</Button
              >
            </div>
          </div>
          <div class="statusVal" v-if="row.status === 1">提现通过</div>
          <div class="statusVal" v-if="row.status === -1">
            提现未通过<br />未通过原因：{{ row.fail_msg }}
          </div>
        </template>
        <template slot-scope="{ row }" slot="mark">
          <span>{{row.mark || '——'}}</span>
        </template>
        <template slot-scope="{ row }" slot="wechat_state_text">
          <span>{{row.wechat_state_text || row.fail_reason || '——'}}</span>
        </template>
        <template
          slot-scope="{ row }"
          slot="createModalFrame"
        >
          <a v-if="row.extract_type != 'balance'" href="javascript:void(0);" @click="edit(row)">编辑</a>
          <Divider v-if="row.extract_type != 'balance'" type="vertical" />
          <a href="javascript:void(0);"  @click="remarkRow(row)" >备注</a>
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

    <!-- 编辑表单-->
    <edit-from
      ref="edits"
      :FromData="FromData"
      @submitFail="submitFail"
    ></edit-from>
    <!-- 拒绝通过-->
    <Modal
      v-model="modals"
      scrollable
      closable
      title="未通过原因"
      :mask-closable="false"
    >
      <Input
        v-model="fail_msg.message"
        type="textarea"
        :rows="4"
        placeholder="请输入未通过原因"
      />
      <div slot="footer">
        <Button
          type="primary"
          size="large"
          long
          :loading="modal_loading"
          @click="oks"
          >确定</Button
        >
      </div>
    </Modal>
  </div>
</template>
<script>
import cardsData from "@/components/cards/cards";
import searchFrom from "@/components/publicSearchFrom";
import { mapState } from "vuex";
import { cashListApi, cashEditApi, refuseApi, remarkRowApi } from "@/api/finance";
import { formatDate } from "@/utils/validate";
import editFrom from "@/components/from/from";
import timeOptions from "@/utils/timeOptions";
export default {
  name: "cashApply",
  components: { cardsData, searchFrom, editFrom },
  filters: {
    formatDate(time) {
      if (time !== 0) {
        let date = new Date(time * 1000);
        return formatDate(date, "yyyy-MM-dd hh:mm");
      }
    },
  },
  data() {
    return {
      modal_loading: false,
      options: timeOptions,
      fail_msg: {
        message: "输入信息不完整或有误!",
      },
      modals: false,
      total: 0,
      cardLists: [],
      loading: false,
      columns: [
        {
          title: "ID",
          key: "id",
          width: 80,
        },
        {
          title: "用户信息",
          slot: "nickname",
          minWidth: 180,
        },
        {
          title: "提现到账金额",
          slot: "extract_price",
          minWidth: 90,
        },
        {
          title: "手续费",
          slot: "extract_fee",
          minWidth: 90,
        },
        {
          title: "提现方式",
          slot: "extract_type",
          minWidth: 150,
        },
        {
          title: "收款码",
          slot: "qrcode_url",
          minWidth: 150,
        },
        {
          title: "添加时间",
          slot: "add_time",
          minWidth: 120,
        },
        {
          title: "备注",
          slot: "mark",
          minWidth: 140,
        },
        {
          title: "微信转账状态",
          slot: "wechat_state_text",
          minWidth: 180,
        },
        {
          title: "审核状态",
          slot: "status",
          minWidth: 180,
        },
        {
          title: "操作",
          slot: "createModalFrame",
          fixed: "right",
          width: 100,
        },
      ],
      tabList: [],
      fromList: {
        title: "选择时间",
        custom: true,
        fromTxt: [
          { text: "全部", val: "" },
          { text: "昨天", val: "yesterday" },
          { text: "今天", val: "today" },
          { text: "本周", val: "week" },
          { text: "本月", val: "month" },
          { text: "本季度", val: "quarter" },
          { text: "本年", val: "year" },
        ],
      },
      treeData: {
        withdrawal: [
          // {
          //   title: "全部",
          //   value: "",
          // },
          {
            title: "未通过",
            value: -1,
          },
          {
            title: "申请中",
            value: 0,
          },
          {
            title: "已通过",
            value: 1,
          },
        ],
        payment: [
          // {
          //   title: "全部",
          //   value: "",
          // },
          {
            title: "支付宝",
            value: "alipay",
          },
          {
            title: "银行卡",
            value: "bank",
          },
          {
            title: "微信",
            value: "wx",
          },
          {
          	title: '提现到余额',
          	value: 'balance'
          }
        ],
      },
      formValidate: {
        status: "",
        extract_type: "",
        nireid: "",
        data: "",
        page: 1,
        limit: 20,
      },
      extractStatistics: {},
      timeVal: [],
      FromData: null,
      extractId: 0,
    };
  },
  watch: {
    $route() {
      if (
        this.$route.fullPath === "/admin/finance/user_extract/index?status=0"
      ) {
        this.getPath();
      }
    },
  },
  computed: {
    ...mapState("admin/layout", ["isMobile"]),
    labelWidth() {
      return this.isMobile ? undefined : 96;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
  },
  // created() {
  //     if(this.$route.query.key == 0){
  //         this.formValidate.status = parseInt(this.$route.query.key)
  //     }
  // },
  mounted() {
    if (this.$route.fullPath === "/admin/finance/user_extract/index?status=0") {
      this.getPath();
    } else {
      this.getList();
    }
    // this.getList();
  },
  methods: {
    remarkRow(row){
      this.$modalForm(remarkRowApi(row.id)).then(() => this.orderSearch());
    },
    getPath() {
      this.formValidate.page = 1;
      this.formValidate.status = parseInt(this.$route.query.status);
      this.getList();
    },
    // 无效
    invalid(row) {
      this.extractId = row.id;
      this.modals = true;
    },
    // 确定
    oks() {
      this.modal_loading = true;
      refuseApi(this.extractId, this.fail_msg)
        .then(async (res) => {
          this.$Message.success(res.msg);
          this.modal_loading = false;
          this.modals = false;
          this.getList();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 通过
    adopt(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `finance/extract/adopt/${row.id}`,
        method: "put",
        ids: "",
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.getList();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 具体日期
    onchangeTime(e) {
      this.timeVal = e;
      this.formValidate.data = this.timeVal[0] ? this.timeVal.join("-") : "";
      this.formValidate.page = 1;
      this.getList();
    },
    // 选择时间
    selectChange(tab) {
      this.formValidate.page = 1;
      this.formValidate.data = tab;
      this.timeVal = [];
      this.getList();
    },
    // 选择
    selChange() {
      this.formValidate.page = 1;
      this.getList();
    },
    reset(){
      this.formValidate = {
        status: "",
        extract_type: "",
        nireid: "",
        data: "",
        page: 1,
        limit: 20
      };
      this.timeVal = [];
      // this.$refs.formValidate.resetFields()
      this.getList();
    },
    // 列表
    getList() {
      this.loading = true;
      cashListApi(this.formValidate)
        .then(async (res) => {
          let data = res.data;
          this.tabList = data.list.list;
          this.total = data.list.count;
          this.extractStatistics = data.extract_statistics;
          this.cardLists = [
            {
              col: 6,
              count: this.extractStatistics.price,
              name: "待提现金额",
              className: "md-basket",
            },
            {
              col: 6,
              count: this.extractStatistics.brokerage_count,
              name: "佣金总金额",
              className: "md-pricetags",
            },
            {
              col: 6,
              count: this.extractStatistics.priced,
              name: "已提现金额",
              className: "md-cash",
            },
            {
              col: 6,
              count: this.extractStatistics.brokerage_not,
              name: "未提现金额",
              className: "ios-cash",
            },
          ];
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
    // 编辑
    edit(row) {
      cashEditApi(row.id)
        .then(async (res) => {
          if (res.data.status === false) {
            return this.$authLapse(res.data);
          }
          this.FromData = res.data;
          this.$refs.edits.modals = true;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 编辑提交成功
    submitFail() {
      this.getList();
    },
  },
};
</script>
<style scoped lang="stylus">
.btn-add {
margin:0 14px 0 -90px;
}
.ivu-mt .type .item {
  margin: 3px 0;
}
.card_pd{
  padding:20px 20px 0;
}
.Refresh {
  font-size: 12px;
  color: #1890FF;
  cursor: pointer;
}
.status >>> .item~.item {
  margin-left: 6px;
}

.status >>> .statusVal {
  margin-bottom: 7px;
}

/* .ivu-mt >>> .ivu-table-header */
/* border-top:1px dashed #ddd!important */
.type {
  padding: 3px 0;
  box-sizing: border-box;
}

.tabBox_img {
  width: 36px;
  height: 36px;
  border-radius: 4px;
  cursor: pointer;

  img {
    width: 100%;
    height: 100%;
  }
}
</style>
