<template>
  <div>
    <PageHeader
      class="product_tabs"
      :title="$route.meta.title"
      hidden-breadcrumb
    >
      <div slot="title">
        <router-link :to="{ path: '/admin/content/live/live_goods' }">
          <div class="font-sm after-line">
            <span class="iconfont iconfanhui"></span>
            <span class="pl10">返回</span>
          </div>
        </router-link>
        <span v-text="$route.meta.title" class="mr20 ml16"></span>
      </div>
    </PageHeader>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <Form
        ref="formValidate"
        :model="formValidate"
        :label-width="labelWidth"
        :label-position="labelPosition"
        class="tabform"
        @submit.native.prevent
      >
        <Row :gutter="24" type="flex">
          <Col span="24">
            <FormItem label="选择商品：">
              <div class="box">
                <div
                  class="box-item"
                  v-for="(item, index) in goodsList"
                  :key="index"
                >
                  <img :src="item.image" alt="" />
                  <Icon
                    type="ios-close-circle"
                    size="20"
                    @click="bindDelete(index)"
                  />
                </div>
                <div class="upload-box" @click="modals = true">
                  <Icon type="ios-camera-outline" size="36" />
                </div>
              </div>
            </FormItem>
          </Col>
        </Row>
      </Form>
      <div class="active-btn" v-if="goodsList.length > 0">
        <Button type="success" @click="liveGoods">生成直播商品</Button>
      </div>
      <div class="table-box" v-if="isShowBox">
        <Table
          :columns="columns1"
          :data="tabList"
          ref="table"
          class="mt25"
          :loading="loading"
          no-userFrom-text="暂无数据"
          no-filtered-userFrom-text="暂无筛选结果"
        >
          <template slot-scope="{ row }" slot="img">
            <div class="product_box">
              <img :src="row.image" alt="" />
              <span class="goods_title line1">{{ row.store_name }}</span>
            </div>
          </template>
          <template slot-scope="{ row, index }" slot="action">
            <a @click="del(row, index)">删除</a>
          </template>
        </Table>
      </div>
      <Card :bordered="false" dis-hover class="fixed-card">
        <div class="acea-row row-center">
          <Button type="primary" style="width:8%" @click="bindSub">提交</Button>
        </div>
      </Card>
    </Card>
    <Modal
      v-model="modals"
      title="商品列表"
      class="paymentFooter"
      scrollable
      width="900"
      :footer-hide="true"
    >
      <goods-list
        ref="goodslist"
        @getProductId="getProductId"
        v-if="modals"
        :ischeckbox="true"
        :isLive="true"
      ></goods-list>
    </Modal>
  </div>
</template>

<script>
import { mapState } from "vuex";
import goodsList from "@/components/goodsList";
import { liveGoodsCreat, liveGoodsAdd } from "@/api/live";
export default {
  name: "add_goods",
  components: {
    goodsList,
  },
  computed: {
    ...mapState("admin/layout", ["isMobile"]),
    labelWidth() {
      return this.isMobile ? undefined : 100;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
  },
  data() {
    return {
      isShowBox: false,
      loading: false,
      modals: false,
      goodsList: [],
      tempGoods: {},
      formValidate: {},
      columns1: [
        { key: "id", title: "商品ID", width: 80 },
        { slot: "img", title: "商品信息", minWidth: 250 },
        {
          key: "price",
          title: "直播售价",
          minWidth: 100,
          render: (h, params) => {
            return h("Input", {
              props: {
                type: "number",
                value: params.row.price,
              },
              on: {
                input: (val) => {
                  this.tabList[params.index].price = val;
                },
              },
            });
          },
        },
        {
          key: "cost_price",
          title: "划线价",
          minWidth: 100,
          render: (h, params) => {
            return h("Input", {
              props: {
                type: "number",
                value: params.row.cost_price,
              },
              on: {
                input: (val) => {
                  this.tabList[params.index].cost_price = val;
                },
              },
            });
          },
        },
        { key: "stock", title: "库存", minWidth: 80 },
        { slot: "action", fixed: "right", title: "操作", width: 100 },
      ],
      tabList: [],
    };
  },
  methods: {
    // 生成直播商品
    liveGoods() {
      let array = [];
      this.goodsList.map((el) => {
        array.push(el.product_id);
      });
      liveGoodsCreat({
        product_id: array,
      })
        .then((res) => {
          this.tabList = res.data;
          this.isShowBox = true;
        })
        .catch((error) => {
          this.$Message.error(error.msg);
        });
    },
    //对象数组去重；
    unique(arr) {
      const res = new Map();
      return arr.filter(
        (arr) => !res.has(arr.product_id) && res.set(arr.product_id, 1)
      );
    },
    getProductId(data) {
      let list = this.goodsList.concat(data);
      let uni = this.unique(list);
      this.goodsList = uni;
      this.$nextTick((res) => {
        setTimeout(() => {
          this.modals = false;
        }, 300);
      });
    },
    bindDelete(index) {
      this.goodsList.splice(index, 1);
    },
    del(row, index) {
      this.tabList.splice(index, 1);
    },
    // 提交
    bindSub() {
      liveGoodsAdd({
        goods_info: this.tabList,
      })
        .then((res) => {
          this.$Message.success("添加成功");
          setTimeout(() => {
            this.$router.push({ path: "/admin/content/live/live_goods" });
          }, 500);
        })
        .catch((error) => {
          this.$Message.error(error.msg);
        });
    },
  },
};
</script>

<style lang="stylus" scoped>
.upload-box
    display flex
    align-items center
    justify-content center
    width 60px
    height 60px
    background #ccc
.box
    display flex
    flex-wrap wrap
    .box-item
        position relative
        margin-right 20px
        .ivu-icon
            position absolute
            right: -10px;
            top: -8px;
            color #999
            cursor pointer
    .upload-box,.box-item
        width 60px
        height 60px
        margin-bottom 10px
        img
            width 100%
            height 100%
.active-btn
    padding-left 96px
.table-box
    margin 0 107px
.sub_btn
    margin-top 10px
.product_box
    display flex
    img
        width 36px
        height 36px
        margin-right 10px
.goods_title{
    height:36px;
    line-height: 36px;
    width:250px;
}
.fixed-card {
    position: fixed;
    right: 0;
    bottom: 0;
    left: 200px;
    z-index: 99;
    box-shadow: 0 -1px 2px rgb(240, 240, 240);

    /deep/ .ivu-card-body {
        padding: 15px 16px 14px;
    }

    .ivu-form-item {
        margin-bottom: 0;
    }

    /deep/ .ivu-form-item-content {
        margin-right: 124px;
        text-align: center;
    }

    .ivu-btn {
        height: 36px;
        padding: 0 20px;
    }
}
</style>
