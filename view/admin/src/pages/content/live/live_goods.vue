<template>
  <!-- 营销-直播商品管理 -->
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="new_card_pd">
        <!-- 查询条件 -->
        <Form
          ref="formValidate"
          inline
          :model="formValidate"
          :label-width="labelWidth"
          :label-position="labelPosition"
          class="tabform"
          @submit.native.prevent
        >
          <FormItem label="审核状态：">
            <Select
              v-model="formValidate.status"
              placeholder="请选择"
              clearable
              class="input-add"
            >
              <Option
                v-for="(item, index) in treeData.withdrawal"
                :key="index"
                :value="item.value"
                >{{ item.title }}</Option
              >
            </Select>
          </FormItem>
          <FormItem label="搜索：">
            <Input
              placeholder="请输入商品名称/ID"
              element-id="name"
              v-model="formValidate.kerword"
              class="input-add mr14"
            />
            <Button type="primary" @click="selChange()" class="mr10"
              >查询</Button
            >
          </FormItem>
        </Form>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <!-- 操作 -->
      <Button
        v-auth="['setting-system_menus-add']"
        type="primary"
        @click="menusAdd('添加直播间')"
        >添加商品
      </Button>
      <!-- 直播商品管理-表格 -->
      <Table
        :columns="columns1"
        :data="tabList"
        ref="table"
        class="ivu-mt"
        :loading="loading"
        highlight-row
        no-userFrom-text="暂无数据"
        no-filtered-userFrom-text="暂无筛选结果"
      >
        <template slot-scope="{ row }" slot="name">
          <div class="product_box">
            <viewer>
              <img :src="row.product.image" alt="" />
            </viewer>
            <div class="txt">{{ row.name }}</div>
          </div>
        </template>
        <template slot-scope="{ row }" slot="status">
          <Tag color="default" size="large" v-show="row.audit_status === 0">{{
            row.audit_status | liveStatusFilter
          }}</Tag>
          <Tag color="orange" size="large" v-show="row.audit_status === 1">{{
            row.audit_status | liveStatusFilter
          }}</Tag>
          <Tag color="green" size="large" v-show="row.audit_status === 2">{{
            row.audit_status | liveStatusFilter
          }}</Tag>
          <Tag color="default" size="large" v-show="row.audit_status === 3">{{
            row.audit_status | liveStatusFilter
          }}</Tag>
        </template>
        <template slot-scope="{ row }" slot="cost_price">
          <div>{{ row.cost_price }}</div>
        </template>
        <template slot-scope="{ row }" slot="stock">
          <div>{{ row.product.stock }}</div>
        </template>
        <template slot-scope="{ row }" slot="is_mer_show">
          <i-switch
            v-model="row.is_show"
            :value="row.is_show"
            :true-value="1"
            :false-value="0"
            @on-change="onchangeIsShow(row)"
            size="large"
            :disabled="row.audit_status != 2"
          >
            <span slot="open">显示</span>
            <span slot="close">隐藏</span>
          </i-switch>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <a @click="edit(row, '编辑')">详情</a>
          <Divider type="vertical" />
          <a @click="del(row, '删除这条信息', index)">删除</a>
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
    <!--详情-->
    <Modal
      v-model="modals"
      title="商品详情"
      class="paymentFooter"
      scrollable
      width="700"
      :footer-hide="true"
    >
      <goodsFrom ref="goodsDetail" />
    </Modal>
  </div>
</template>

<script>
import { mapState } from "vuex";
import {
  liveGoods,
  liveSyncGoods,
  liveGoodsDetail,
  liveGoodsShow,
} from "@/api/live";
import goodsFrom from "./components/goods_detail";
export default {
  name: "live",
  components: {
    goodsFrom,
  },
  data() {
    return {
      grid: {
        xl: 7,
        lg: 7,
        md: 12,
        sm: 24,
        xs: 24,
      },
      formValidate: {
        status: "",
        kerword: "",
        page: 1,
        limit: 20,
      },
      treeData: {
        withdrawal: [
          // {
          //   title: "全部",
          //   value: "",
          // },
          {
            title: "待审核",
            value: 0,
          },
          {
            title: "已通过",
            value: 1,
          },
          {
            title: "未通过",
            value: -1,
          },
        ],
      },
      columns1: [
        { key: "product_id", title: "商品ID", width: 80 },
        { slot: "name", minWidth: 240, title: "商品名称" },
        { key: "price", minWidth: 90, title: "直播价" },
        { slot: "cost_price", minWidth: 90, title: "划线价" },
        { slot: "stock", minWidth: 90, title: "库存" },
        { slot: "status", minWidth: 90, title: "审核状态" },
        { slot: "is_mer_show", title: "是否显示", minWidth: 90 },
        // {"key": "sort", "title": "排序", "minWidth": 35},
        { slot: "action", title: "操作", minWidth: 120 },
      ],
      tabList: [],
      loading: false,
      modals: false,
      total: 0,
    };
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
  mounted() {
    this.getList();
  },
  methods: {
    // 分页
    pageChange(index) {
      this.formValidate.page = index;
      this.getList();
    },
    // 直播间显示隐藏
    onchangeIsShow({ id, is_show }) {
      liveGoodsShow(id, is_show)
        .then((res) => {
          this.$Message.success(res.msg);
        })
        .catch((error) => {
          this.$Message.error(error.msg);
        });
    },
    // 列表数据
    getList() {
      this.loading = true;
      liveGoods(this.formValidate)
        .then((res) => {
          this.total = res.data.count;
          this.tabList = res.data.list;
          this.loading = false;
        })
        .catch((error) => {
          this.$Message.error(error.msg);
          this.loading = false;
        });
    },
    // 选择
    selChange() {
      this.formValidate.page = 1;
      this.getList();
    },
    // 添加商品
    menusAdd() {
      this.$router.push({
        path: "/admin/content/live/add_live_goods",
      });
    },
    // 同步商品
    syncGoods() {
      liveSyncGoods()
        .then((res) => {
          this.$Message.success(res.msg);
          this.getList();
        })
        .catch((error) => {
          this.$Message.error(res.msg);
        });
    },
    edit(row) {
      this.modals = true;
      this.$refs.goodsDetail.getData(row.id);
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `live/goods/del/${row.id}`,
        method: "DELETE",
        ids: "",
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.tabList.splice(num, 1);
          if (!this.tabList.length) {
            this.formValidate.page =
              this.formValidate.page == 1 ? 1 : this.formValidate.page - 1;
          }
          this.getList();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
  },
};
</script>

<style scoped lang="stylus">
.product_box {
  display: flex;
  align-items: center;

  img {
    width: 36px;
    height: 36px;
  }

  .txt {
    margin-left: 10px;
    color: #000;
    font-size: 12px;
  }
}
</style>
