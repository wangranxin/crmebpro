<template>
  <!-- <div style="width: 100%"> -->
  <Drawer
    :closable="false"
    width="1000"
    class-name="order_box"
    v-model="modals"
    :styles="{ padding: 0 }"
  >
    <div class="acea-row user-row">
      <div class="avatar mr15">
        <img :src="psInfo.avatar" />
      </div>
      <div class="user-row-text">
        <div>
          <span class="nickname"
            >{{ psInfo.nickname || "-"
            }}{{ psInfo.delete_time != null ? " (已注销)" : "" }}</span
          >
          <i
            :class="{
              iconxiaochengxu: psInfo.user_type === 'routine',
              icongongzhonghao: psInfo.user_type === 'wechat',
              iconPC: psInfo.user_type === 'pc',
              iconh5: psInfo.user_type === 'h5',
              iconapp: psInfo.user_type === 'app',
            }"
            class="iconfont"
          ></i>
        </div>
        <div class="level">
          <img
            v-if="psInfo.is_money_level"
            src="@/assets/images/svip-user.png"
          />
          <span v-if="psInfo.level" class="vip">V{{ psInfo.level }}</span>
        </div>
      </div>
      <div
        class="user-row-action"
        v-if="fromType !== 'order' && psInfo.delete_time == null"
      >
        <Button v-show="isEdit" @click="isEdit = false">取消</Button>
        <Button v-show="isEdit" type="primary" @click="finish">完成</Button>
        <Button
          v-show="!isEdit && activeName === 'info'"
          type="primary"
          @click="isEdit = true"
          >编辑</Button
        >
        <Button type="success" @click="changeMenu('2')">积分余额</Button>
        <Button @click="changeMenu('3')">赠送会员</Button>
      </div>
    </div>
    <div class="acea-row info-row">
      <div
        v-for="(item, index) in detailsData"
        :key="index"
        class="info-row-item"
      >
        <div class="info-row-item-title">{{ item.title }}</div>
        <div>{{ item.value }}{{ item.key }}</div>
      </div>
    </div>
    <Tabs v-model="activeName">
      <TabPane
        v-for="(item, index) in list"
        :key="index"
        :label="item.label"
        :name="item.val"
      >
        <template v-if="item.val === 'info'">
          <user-form
            v-if="isEdit"
            ref="userForm"
            :ps-info="psInfo"
            @change-menu="changeMenu"
          ></user-form>
          <user-info
            v-else
            :ps-info="psInfo"
            :workMemberInfo="workMemberInfo"
            :workClientInfo="workClientInfo"
          ></user-info>
        </template>
        <template v-else>
          <Table
            :columns="columns"
            :data="userLists"
            ref="table"
            :loading="loading"
            no-userFrom-text="暂无数据"
            no-filtered-userFrom-text="暂无筛选结果"
          >
            <template slot-scope="{ row }" slot="coupon_price">
              <span v-if="row.coupon_type == 1">{{ row.coupon_price }}元</span>
              <span v-if="row.coupon_type == 2"
                >{{ parseFloat(row.coupon_price) / 10 }}折（{{
                  row.coupon_price.toString().split(".")[0]
                }}%）</span
              >
            </template>
            <template slot-scope="{ row }" slot="product">
              <div class="product">
                <div class="image" v-viewer>
                  <img v-lazy="row.image" />
                </div>
                <div class="title">{{ row.store_name }}</div>
              </div>
            </template>
          </Table>
          <div class="acea-row row-right page">
            <Page
              :total="total"
              :current.sync="userFrom.page"
              show-elevator
              show-total
              @on-change="pageChange"
              :page-size="userFrom.limit"
            />
          </div>
        </template>
      </TabPane>
    </Tabs>
  </Drawer>
  <!-- <Modal v-model="modals" scrollable footer-hide closable title="用户详情" :mask-closable="false" width="1000" :z-index="2"> -->
  <!-- <Spin size="large" fix v-if="spinShow"></Spin> -->
  <!-- <div class="acea-row">
                <div class="avatar mr15"><img :src="psInfo.avatar"></div>
                <div class="dashboard-workplace-header-tip">
                    <p class="dashboard-workplace-header-tip-title" v-text="psInfo.nickname || '-'"></p>
                    <div class="dashboard-workplace-header-tip-desc">
                        <span class="dashboard-workplace-header-tip-desc-sp" v-for="(item, index) in detailsData" :key="index">{{item.title+'：'+item.value}}</span>
                    </div>
                </div>
            </div> -->
  <!-- <Row type="flex" justify="space-between" class="mt25">
                <Col span="4" class="user_menu">
                    <Menu :theme="theme2" :active-name="activeName" @on-select="changeType">
                        <MenuItem :name="item.val" v-for="(item, index) in list" :key="index" >
                            {{item.label}}
                        </MenuItem>
                    </Menu>
                </Col>
                <Col span="20">
                    <Table :columns="columns" :data="userLists" max-height="400"
                           ref="table"
                           :loading="loading"
                           no-userFrom-text="暂无数据"
                           no-filtered-userFrom-text="暂无筛选结果"
                    ></Table>
                    <div class="acea-row row-right page">
                        <Page :total="total" :current="userFrom.page" show-elevator show-total @on-change="pageChange"
                              :page-size="userFrom.limit"  /></div>
                </Col>
            </Row> -->
  <!-- </Modal> -->
  <!-- </div> -->
</template>

<script>
import { detailsApi, infoApi, visitList, spreadList } from "@/api/user";
import userForm from "./userForm";
import userInfo from "./userInfo";

export default {
  name: "userDetails",
  components: {
    userForm,
    userInfo,
  },
  props: ["levelList", "labelList", "groupList", "fromType"],
  data() {
    return {
      theme2: "light",
      list: [
        { val: "info", label: "用户信息" },
        { val: "order", label: "消费记录" },
        { val: "integral", label: "积分明细" },
        { val: "sign", label: "签到记录" },
        { val: "coupon", label: "持有优惠券" },
        { val: "balance_change", label: "余额变动" },
        { val: "spread", label: "好友关系" },
        { val: "visit", label: "浏览足迹" },
        { val: "spread_change", label: "推荐人变更记录" },
      ],
      modals: false,
      spinShow: false,
      detailsData: [],
      userId: 0,
      loading: false,
      userFrom: {
        type: "info",
        page: 1, // 当前页
        limit: 12, // 每页显示条数
      },
      total: 0,
      columns: [],
      userLists: [],
      psInfo: {},
      workMemberInfo: {},
      workClientInfo: {},
      activeName: "info",
      isEdit: false,
      groupOptions: [],
      labelOptions: [],
    };
  },
  watch: {
    activeName(value) {
      this.userFrom.page = 1;
      if (value == "info") return;
      this.isEdit = false;
      this.changeType(value);
      // if (value == 'visit') {
      //     this.changeType(value);
      // } else if (value == 'spread_change') {
      //    this.changeType(value);
      // } else {
      //     this.changeType(value);
      // }
    },
    modals(value) {
      if (value) {
        this.isEdit = false;
      }
    },
  },
  created() {},
  methods: {
    changeMenu(value) {
      if (value === "99") {
        this.getDetails(this.userId);
        this.$parent.getList();
        this.isEdit = false;
        return;
      }
      this.$parent.changeMenu(this.psInfo, value);
    },
    // 完成
    finish() {
      this.$refs.userForm[0].detailsPut();
    },
    // 推荐人变更记录
    getSpreadList() {
      this.loading = true;
      spreadList({
        id: this.userId,
        datas: {
          page: this.userFrom.page,
          limit: this.userFrom.limit,
        },
      })
        .then(async (res) => {
          if (res.status === 200) {
            let data = res.data;
            this.userLists = data.list;
            this.total = data.count;
            this.columns = [
              {
                title: "推荐人ID",
                key: "spread_uid",
                minWidth: 120,
              },
              {
                title: "推荐人",
                key: "nickname",
                minWidth: 120,
                render: (h, params) => {
                  return h("div", [
                    h("img", {
                      style: {
                        borderRadius: "50%",
                        marginRight: "10px",
                        verticalAlign: "middle",
                      },
                      attrs: {
                        with: 38,
                        height: 38,
                      },
                      directives: [
                        {
                          name: "lazy",
                          value: params.row.avatar,
                        },
                        {
                          name: "viewer",
                        },
                      ],
                    }),
                    h(
                      "span",
                      {
                        style: {
                          verticalAlign: "middle",
                        },
                      },
                      params.row.nickname
                    ),
                  ]);
                },
              },
              {
                title: "变更方式",
                key: "type",
                minWidth: 120,
              },
              {
                title: "变更时间",
                key: "spread_time",
                minWidth: 120,
              },
            ];
            this.loading = false;
          } else {
            this.loading = false;
            this.$Message.error(res.msg);
          }
        })
        .catch((res) => {
          this.loading = false;
          this.$Message.error(res.msg);
        });
    },
    // 浏览足迹
    getVisitList() {
      this.loading = true;
      visitList({
        id: this.userId,
        datas: {
          page: this.userFrom.page,
          limit: this.userFrom.limit,
        },
      })
        .then(async (res) => {
          if (res.status === 200) {
            let data = res.data;
            this.userLists = data.list;
            this.total = data.count;
            this.columns = [
              {
                title: "商品信息",
                slot: "product",
                minWidth: 400,
              },
              {
                title: "价格",
                key: "product_price",
                minWidth: 120,
                render: (h, params) => {
                  return h("div", `¥${params.row.product_price}`);
                },
              },
              {
                title: "浏览时间",
                key: "add_time",
                minWidth: 120,
              },
            ];
            this.loading = false;
          } else {
            this.loading = false;
            this.$Message.error(res.msg);
          }
        })
        .catch((res) => {
          this.loading = false;
          this.$Message.error(res.msg);
        });
    },
    // 会员详情
    getDetails(id) {
      this.userId = id;
      this.spinShow = true;
      detailsApi(id)
        .then(async (res) => {
          if (res.status === 200) {
            let data = res.data;
            this.detailsData = data.headerList;
            if (this.fromType !== "order") {
              let groupItem = this.groupList.find(
                (item) => item.id == data.ps_info.group_id
              );
              if (groupItem) {
                data.ps_info.group_name = groupItem.group_name;
              }
            }
            this.psInfo = data.ps_info;
            this.workMemberInfo = data.workMemberInfo;
            this.workClientInfo = data.workClientInfo;
            this.spinShow = false;
          } else {
            this.spinShow = false;
            this.$Message.error(res.msg);
          }
        })
        .catch((res) => {
          this.spinShow = false;
          this.$Message.error(res.msg);
        });
    },
    pageChange(index) {
      this.userFrom.page = index;
      this.changeType(this.userFrom.type);
      // switch (this.activeName) {
      //     case 'visit':
      //        this.changeType(this.userFrom.type);
      //         break;
      //     case 'spread_change':
      //        this.changeType(this.userFrom.type);
      //         break;
      //     default:
      //         this.changeType(this.userFrom.type);
      //         break;
      // }
    },
    // tab选项
    changeType(name) {
      this.loading = true;
      this.userFrom.type = name;
      this.activeName = name;
      let data = {
        id: this.userId,
        datas: this.userFrom,
      };
      infoApi(data)
        .then(async (res) => {
          if (res.status === 200) {
            let data = res.data;
            this.userLists = data.list;
            this.total = data.count;
            switch (this.userFrom.type) {
              case "order":
                this.columns = [
                  {
                    title: "订单ID",
                    key: "order_id",
                    minWidth: 160,
                  },
                  {
                    title: "收货人",
                    key: "real_name",
                    minWidth: 100,
                  },
                  {
                    title: "商品数量",
                    key: "total_num",
                    minWidth: 90,
                  },
                  {
                    title: "商品总价",
                    key: "total_price",
                    minWidth: 110,
                  },
                  {
                    title: "实付金额",
                    key: "pay_price",
                    minWidth: 120,
                  },
                  {
                    title: "交易完成时间",
                    key: "pay_time",
                    minWidth: 120,
                  },
                ];
                break;
              case "integral":
                this.columns = [
                  {
                    title: "来源/用途",
                    key: "title",
                    minWidth: 120,
                  },
                  {
                    title: "积分变化",
                    key: "number",
                    minWidth: 120,
                  },
                  {
                    title: "变化后积分",
                    key: "balance",
                    minWidth: 120,
                  },
                  {
                    title: "日期",
                    key: "add_time",
                    minWidth: 120,
                  },
                  {
                    title: "备注",
                    key: "mark",
                    minWidth: 120,
                  },
                ];
                break;
              case "sign":
                this.columns = [
                  // {
                  //     title: '动作',
                  //     key: 'title',
                  //     minWidth: 120
                  // },
                  {
                    title: "获得积分",
                    key: "number",
                    minWidth: 120,
                  },
                  {
                    title: "签到时间",
                    key: "add_time",
                    minWidth: 120,
                  },
                  {
                    title: "备注",
                    key: "mark",
                    minWidth: 120,
                  },
                ];
                break;
              case "coupon":
                this.columns = [
                  {
                    title: "优惠券名称",
                    key: "coupon_title",
                    minWidth: 120,
                  },
                  {
                    title: "面值",
                    slot: "coupon_price",
                    minWidth: 120,
                  },
                  {
                    title: "有效期(天)",
                    key: "coupon_time",
                    minWidth: 120,
                  },
                  {
                    title: "兑换时间",
                    key: "_add_time",
                    minWidth: 120,
                  },
                ];
                break;
              case "balance_change":
                this.columns = [
                  {
                    title: "动作",
                    key: "title",
                    minWidth: 120,
                  },
                  {
                    title: "变动金额",
                    key: "number",
                    minWidth: 120,
                  },
                  {
                    title: "变动后",
                    key: "balance",
                    minWidth: 120,
                  },
                  {
                    title: "创建时间",
                    key: "add_time",
                    minWidth: 120,
                  },
                  {
                    title: "备注",
                    key: "mark",
                    minWidth: 120,
                  },
                ];
                break;
              case "visit":
                this.columns = [
                  {
                    title: "商品信息",
                    slot: "product",
                    minWidth: 400,
                  },
                  {
                    title: "价格",
                    key: "product_price",
                    minWidth: 120,
                    render: (h, params) => {
                      return h("div", `¥${params.row.product_price}`);
                    },
                  },
                  {
                    title: "浏览时间",
                    key: "add_time",
                    minWidth: 120,
                  },
                ];
                break;
              case "spread_change":
                this.columns = [
                  {
                    title: "推荐人ID",
                    key: "spread_uid",
                    minWidth: 120,
                  },
                  {
                    title: "推荐人",
                    key: "nickname",
                    minWidth: 120,
                    render: (h, params) => {
                      return h("div", [
                        h("img", {
                          style: {
                            borderRadius: "50%",
                            marginRight: "10px",
                            verticalAlign: "middle",
                          },
                          attrs: {
                            with: 38,
                            height: 38,
                          },
                          directives: [
                            {
                              name: "lazy",
                              value: params.row.avatar,
                            },
                            {
                              name: "viewer",
                            },
                          ],
                        }),
                        h(
                          "span",
                          {
                            style: {
                              verticalAlign: "middle",
                            },
                          },
                          params.row.nickname
                        ),
                      ]);
                    },
                  },
                  {
                    title: "变更方式",
                    key: "type",
                    minWidth: 120,
                  },
                  {
                    title: "变更时间",
                    key: "spread_time",
                    minWidth: 120,
                  },
                ];
                break;
              default:
                this.columns = [
                  {
                    title: "ID",
                    key: "uid",
                    minWidth: 120,
                  },
                  {
                    title: "昵称",
                    key: "nickname",
                    minWidth: 120,
                  },
                  {
                    title: "等级",
                    key: "type",
                    minWidth: 120,
                  },
                  {
                    title: "加入时间",
                    key: "add_time",
                    minWidth: 120,
                  },
                ];
            }
            this.loading = false;
          } else {
            this.loading = false;
            this.$Message.error(res.msg);
          }
        })
        .catch((res) => {
          this.loading = false;
          this.$Message.error(res.msg);
        });
    },
  },
};
</script>

<style lang="less" scoped>
/deep/.ivu-modal-body {
  padding: 0;
}
.user-info {
  // padding: 15px;
}
.user-row {
  padding: 30px 35px 0;

  &-text {
    flex: 1;
    align-self: center;
  }

  &-action {
    .ivu-btn {
      margin-left: 12px;
      font-size: 13px !important;
      color: rgba(0, 0, 0, 0.85);

      &:first-child {
        margin-left: 0;
      }

      &.ivu-btn-primary {
        border-color: #1890ff;
        background-color: #1890ff;
        color: #ffffff;
      }

      &.ivu-btn-success {
        border-color: #00c050;
        background-color: #00c050;
        color: #ffffff;
      }
    }
  }

  .nickname {
    font-weight: 500;
    font-size: 16px;
    line-height: 16px;
    color: rgba(0, 0, 0, 0.85);
  }

  .iconfont {
    margin-left: 7px;
    font-size: 18px;

    &:nth-child(2) {
      margin-left: 9px;
    }

    &.iconxiaochengxu {
      color: #007dff;
    }

    &.icongongzhonghao {
      color: #00bf00;
    }

    &.iconPC {
      color: #f69b00;
    }

    &.iconh5 {
      color: #9f5ce3;
    }

    &.iconapp {
      color: #e36734;
    }
  }

  .level {
    margin-top: 5px;

    img {
      width: 42px;
      height: 20px;
      vertical-align: middle;

      + span {
        margin-left: 7px;
      }
    }

    .vip {
      display: inline-block;
      width: 56px;
      height: 26px;
      padding-left: 30px;
      background: url("../../../../assets/images/vip-bg.png") left top/100% 100%
        no-repeat;
      font-weight: bold;
      font-size: 9px;
      line-height: 26px;
      color: #5f7db5;
      transform-origin: left;
      transform: scale(0.75, 0.75);
      vertical-align: middle;
    }
  }
}

.info-row {
  flex-wrap: nowrap;
  padding: 20px 35px 24px;

  &-item {
    flex: none;
    width: 155px;
    font-size: 14px;
    line-height: 14px;
    color: rgba(0, 0, 0, 0.85);

    &-title {
      margin-bottom: 12px;
      font-size: 13px;
      line-height: 13px;
      color: #666666;
    }
  }
}

.ivu-tabs {
  color: rgba(0, 0, 0, 0.85);

  /deep/ .ivu-tabs-bar {
    border-bottom: 0;
    margin-bottom: 0;
    background-color: #f5f7fa;

    .ivu-tabs-nav-container {
      font-size: 13px;
    }

    .ivu-tabs-ink-bar {
      display: none;
    }

    .ivu-tabs-tab {
      padding: 7px 19px !important;
      margin-right: 0;
      line-height: 26px;
    }

    .ivu-tabs-tab-active {
      background-color: #ffffff;
      color: rgba(0, 0, 0, 0.85);

      &:before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #1890ff;
      }
    }
  }

  /deep/ .ivu-tabs-content {
    .ivu-tabs-tabpane {
      padding: 15px 15px !important;

      &:first-child {
        padding: 0 25px !important;
      }
    }
  }

  .product {
    display: flex;

    .image {
      width: 50px;
      height: 50px;
    }

    img {
      width: 100%;
      height: 100%;
      border-radius: 4px;
    }

    .title {
      flex: 1;
      padding-left: 13px;
      text-align: left;
    }
  }
}

.avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  overflow: hidden;
  img {
    width: 100%;
    height: 100%;
  }
}
.dashboard-workplace {
  &-header {
    &-avatar {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      margin-right: 16px;
      font-weight: 600;
    }

    &-tip {
      width: 82%;
      display: inline-block;
      vertical-align: middle;

      &-title {
        font-size: 13px;
        color: #000000;
        margin-bottom: 12px;
      }

      &-desc {
        &-sp {
          width: 33.33%;
          color: #17233d;
          font-size: 13px;
          display: inline-block;
        }
      }
    }

    &-extra {
      .ivu-col {
        p {
          text-align: right;
        }

        p:first-child {
          span:first-child {
            margin-right: 4px;
          }

          span:last-child {
            color: #808695;
          }
        }

        p:last-child {
          font-size: 22px;
        }
      }
    }
  }
}
</style>
<style scoped lang="stylus">
.user_menu >>> .ivu-menu
   width 100% !important
</style>
