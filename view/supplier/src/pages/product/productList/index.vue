<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt pt10">
      <Form
        ref="formValidate"
        :model="formValidate"
        :label-width="labelWidth"
        :label-position="labelPosition"
      >
        <Row type="flex" :gutter="24">
          <Col>
            <FormItem label="商品分类：" label-for="cate_id">
              <el-cascader
                placeholder="请选择商品分类"
                style="width:250px"
                size="mini"
                v-model="formValidate.cate_id"
                :options="data"
                :props="props"
                @on-change="cascaderSearchs"
                filterable
                clearable
              >
              </el-cascader>
            </FormItem>
          </Col>
          <Col>
            <FormItem label="商品品牌：" prop="brand_id">
              <Cascader
                :data="brandData"
                placeholder="请选择商品品牌"
                change-on-select
                v-model="formValidate.brand_id"
                filterable
                class="input-add"
                @on-change="search"
              ></Cascader>
            </FormItem>
          </Col>
          <Col>
            <FormItem
              label="商品标签："
              prop="store_label_id"
              class="labelClass"
            >
              <div class="acea-row row-middle">
                <div
                  class="labelInput acea-row row-between-wrapper"
                  @click="openGoodsLabel"
                >
                  <div style="width: 90%;">
                    <div v-if="storeDataLabel.length">
                      <Tag
                        closable
                        v-for="(item, index) in storeDataLabel"
                        :key="index"
                        @on-close="closeStoreLabel(item)"
                        >{{ item.label_name }}</Tag
                      >
                    </div>
                    <span class="span" v-else>选择商品标签</span>
                  </div>
                  <div class="iconfont iconxiayi"></div>
                </div>
              </div>
            </FormItem>
          </Col>
          <Col>
            <FormItem label="商品搜索：" label-for="store_name">
              <Input
                enter-button
                style="width:250px"
                placeholder="请输入商品名称,关键字,ID"
                v-model="formValidate.store_name"
              />
            </FormItem>
          </Col>
          <Col>
            <div class="search" @click="search">搜索</div>
          </Col>
          <Col>
            <div class="reset" @click="reset">重置</div>
          </Col>
        </Row>
      </Form>
    </Card>
    <Card :bordered="false" dis-hover class="mt15 tablebox">
      <div class="product_tabs">
        <Tabs v-model="formValidate.type" @on-click="onClickTab">
          <TabPane
            v-for="(item, index) in headerList"
            :key="index"
            :label="item.name + '(' + item.count + ')'"
            :name="item.type.toString()"
          />
        </Tabs>
      </div>
      <div class="mt20">
        <!--<div class="reset mb15" @click="setStock">同步库存</div>-->
        <router-link :to="'/supplier/product/edit_product'">
          <Button type="primary" class="bnt">添加商品</Button></router-link
        >
        <Tooltip
          content="本页至少选中一项"
          :disabled="!!checkUidList.length && isAll == 0"
        >
          <Button
            class="bnt ml15"
            :disabled="!checkUidList.length && isAll == 0"
            @click="onDismount"
            v-show="formValidate.type === '1'"
            >批量下架</Button
          >
        </Tooltip>
        <Tooltip
          content="本页至少选中一项"
          :disabled="!!checkUidList.length && isAll == 0"
        >
          <Button
            class="bnt ml15"
            :disabled="!checkUidList.length && isAll == 0"
            @click="onShelves"
            v-show="formValidate.type === '2'"
            >批量上架</Button
          >
        </Tooltip>
        <Tooltip
          content="本页至少选中一项"
          :disabled="!!checkUidList.length && isAll == 0"
        >
          <Button
            class="bnt ml15"
            :disabled="!checkUidList.length && isAll == 0"
            @click="openBatch"
            >批量设置</Button
          >
        </Tooltip>
        <vxe-table
          border="inner"
          ref="xTable"
          class="mt25"
          :loading="loading"
          row-id="id"
          :checkbox-config="{ reserve: true }"
          @checkbox-all="checkboxAll"
          @checkbox-change="checkboxItem"
          :data="orderList"
        >
          <vxe-column type="checkbox" width="100">
            <template #header>
              <div>
                <Dropdown transfer @on-click="allPages">
                  <a href="javascript:void(0)" class="acea-row row-middle">
                    <span
                      >全选({{
                        isAll == 1
                          ? total - checkUidList.length
                          : checkUidList.length
                      }})</span
                    >
                    <Icon type="ios-arrow-down"></Icon>
                  </a>
                  <template #list>
                    <DropdownMenu>
                      <DropdownItem name="0">当前页</DropdownItem>
                      <DropdownItem name="1">所有页</DropdownItem>
                    </DropdownMenu>
                  </template>
                </Dropdown>
              </div>
            </template>
          </vxe-column>
          <vxe-column field="id" title="商品ID" width="70"></vxe-column>
          <vxe-column field="image" title="商品图" width="70">
            <template v-slot="{ row }">
              <viewer>
                <div class="tabBox_img">
                  <img v-lazy="row.image" />
                </div>
              </viewer>
            </template>
          </vxe-column>
          <vxe-column field="store_name" title="商品名称" min-width="250">
            <template v-slot="{ row }">
              <Tooltip
                :transfer="true"
                theme="dark"
                max-width="300"
                :delay="600"
                :content="row.store_name"
              >
                <div class="line2">{{ row.store_name }}</div>
              </Tooltip>
            </template>
          </vxe-column>
          <vxe-column
            field="settle_price"
            title="结算价"
            min-width="90"
          ></vxe-column>
          <vxe-column
            field="branch_sales"
            title="销量"
            min-width="90"
          ></vxe-column>
          <vxe-column
            field="branch_stock"
            title="库存"
            min-width="90"
          ></vxe-column>
          <vxe-column field="sort" title="排序" min-width="70"></vxe-column>
          <vxe-column field="state" title="状态" width="120">
            <template v-slot="{ row }">
              <i-switch
                v-model="row.is_show"
                :value="row.is_show"
                :true-value="1"
                :false-value="0"
                :disabled="row.is_verify == 1 ? false : true"
                @on-change="changeSwitch(row)"
                size="large"
                v-if="formValidate.type != 7"
              >
                <span slot="open">上架</span>
                <span slot="close">下架</span>
              </i-switch>
              <div v-else>
                {{ row.is_del ? "已删除" : !row.is_show ? "已下架" : "" }}
              </div>
            </template>
          </vxe-column>
          <vxe-column
            field="refusal"
            title="拒绝原因"
            min-width="150"
            v-if="formValidate.type == -1"
          ></vxe-column>
          <vxe-column
            field="refusal"
            title="下架原因"
            min-width="150"
            v-if="formValidate.type == -2"
          ></vxe-column>
          <vxe-column
            field="action"
            title="操作"
            align="center"
            width="250"
            fixed="right"
          >
            <template #default="{ row, rowIndex }">
              <a @click="edit(row)" v-if="row.pid == 0">编辑</a>
              <Divider type="vertical" v-if="row.pid == 0" />
              <a @click="detail(row.id)">详情</a>
              <Divider type="vertical" />
              <a @click="stockControl(row)" v-if="!openErp">库存管理</a>
              <Divider type="vertical" v-if="!openErp" />
              <template>
                <Dropdown
                  @on-click="changeMenu(row, $event, rowIndex)"
                  :transfer="true"
                >
                  <a href="javascript:void(0)" class="acea-row row-middle">
                    <span>更多</span>
                    <Icon type="ios-arrow-down"></Icon>
                  </a>
                  <DropdownMenu slot="list">
                    <DropdownItem name="1">查看评论</DropdownItem>
                    <!--                    <DropdownItem name="2" v-if="!openErp">库存管理</DropdownItem>-->
                    <DropdownItem name="3" v-if="row.pid == 0">{{
                      row.is_del ? "恢复商品" : "移入回收站"
                    }}</DropdownItem>
                    <DropdownItem name="4">复制</DropdownItem>
                    <DropdownItem name="5" v-if="formValidate.type == 6"
                      >删除</DropdownItem
                    >
                  </DropdownMenu>
                </Dropdown>
              </template>
              <!--             <a @click="reply(row.id)">查看评论</a>-->
              <!--             <Divider type="vertical" v-if="!openErp" />-->
              <!--             <a @click="stockControl(row)" v-if="!openErp">库存管理</a>-->
              <!--             <Divider type="vertical" v-if="row.pid == 0" />-->
              <!--             <a @click="del(row,rowIndex)" v-if="row.pid == 0">{{row.is_del ? '恢复' : '删除' }}</a>-->
              <!--             <Divider type="vertical" />-->
              <!--             <a @click="copy(row)">复制</a>-->
            </template>
          </vxe-column>
        </vxe-table>
        <vxe-pager
          class="mt20"
          border
          size="medium"
          :page-size="formValidate.limit"
          :current-page="formValidate.page"
          :total="total"
          :layouts="['PrevPage', 'JumpNumber', 'NextPage', 'FullJump', 'Total']"
          @page-change="pageChange"
        >
        </vxe-pager>
      </div>
    </Card>
    <stockEdit ref="stock" @stockChange="stockChange"></stockEdit>
    <productDetails
      :visible.sync="detailsVisible"
      :product-id="productId"
      @saved="getList"
    ></productDetails>
    <batchSet
      ref="batch"
      :checkUidList="checkUidList"
      :isAll="isAll"
      :formValidate="formValidate"
      @onConfirm="getList"
    ></batchSet>
    <Modal
      v-model="storeLabelShow"
      scrollable
      title="选择商品标签"
      :closable="true"
      width="540"
      :footer-hide="true"
      :mask-closable="false"
    >
      <labelList
        ref="storeLabel"
        @activeData="activeStoreData"
        @close="storeLabelClose"
      ></labelList>
    </Modal>
  </div>
</template>

<script>
import Setting from "@/setting";
import { mapState } from "vuex";
import goodsDetail from "../components/goodsDetail.vue";
import stockEdit from "../components/stockEdit.vue";
import productDetails from "../components/productDetails.vue";
import batchSet from "../components/batchSet.vue";
import labelList from "@/components/labelList";
import {
  productListInfo,
  productHeaderInfo,
  cascaderList,
  setShowApi,
  productShowApi,
  productUnshowApi,
  brandList,
} from "@/api/product.js";
import { erpConfig } from "@/api/erp";
export default {
  name: "index",
  components: {
    goodsDetail,
    stockEdit,
    productDetails,
    batchSet,
    labelList,
  },
  data() {
    return {
      props: { emitPath: false, multiple: true, checkStrictly: true },
      openErp: false,
      goodsId: "",
      data: [],
      headerList: [],
      total: 0,
      loading: false,
      orderList: [],
      formValidate: {
        store_label_id: [],
        brand_id: [],
        store_name: "",
        cate_id: [],
        type: "1",
        page: 1,
        limit: 15,
      },
      // datanew: [],
      // dataid: [],
      product_status: 1,
      detailsVisible: false,
      productId: 0,
      isAll: 0,
      isCheckBox: false,
      checkUidList: [],
      isLabel: 0,
      brandData: [],
      storeLabelShow: false,
      storeDataLabel: [],
    };
  },
  watch: {
    $route() {
      if (this.$route.fullPath === `/supplier/product/index?type=5`) {
        this.getPath();
      }
    },
  },
  computed: {
    ...mapState("store/layout", ["isMobile"]),
    labelWidth() {
      return this.isMobile ? undefined : 80;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
  },
  mounted() {
    this.goodsCategory();
    this.getHeader();
    this.getErpConfig();
    this.getBrandList();
  },
  methods: {
    //获取商品分类列表
    cascaderSearchs(value) {
      this.formValidate.cate_id = value[value.length - 1];
      this.search();
    },
    // 标签弹窗关闭
    storeLabelClose() {
      this.storeLabelShow = false;
    },
    getLabelId() {
      let storeActiveIds = [];
      this.storeDataLabel.forEach((item) => {
        storeActiveIds.push(item.id);
      });
      this.formValidate.store_label_id = storeActiveIds;
      this.search();
    },
    activeStoreData(storeDataLabel) {
      this.storeLabelShow = false;
      this.storeDataLabel = storeDataLabel;
      this.getLabelId();
    },
    closeStoreLabel(label) {
      let index = this.storeDataLabel.indexOf(
        this.storeDataLabel.filter((d) => d.id == label.id)[0]
      );
      this.storeDataLabel.splice(index, 1);
      this.getLabelId();
    },
    openGoodsLabel(row) {
      this.storeLabelShow = true;
      this.$refs.storeLabel.userLabel(
        JSON.parse(JSON.stringify(this.storeDataLabel))
      );
    },
    // 品牌列表
    getBrandList() {
      brandList()
        .then((res) => {
          this.brandData = res.data;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    changeMenu(row, name, index) {
      switch (name) {
        case "1":
          this.reply(row.id);
          break;
        case "2":
          this.stockControl(row);
          break;
        case "3":
          this.del(row, index);
          break;
        case "4":
          this.copy(row);
          break;
        case "5":
          this.delProduct(row, index);
          break;
      }
    },
    delProduct(row, num) {
      let delfromData = {
        title: "删除商品",
        num: num,
        url: `product/product/thorough/${row.id}`,
        method: "DELETE",
        ids: "",
        tips: `确定要删除该商品吗？`,
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.getHeader();
          this.allReset();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    //批量设置
    openBatch() {
      this.isLabel = 0;
      this.$refs.batch.checkUidList = this.checkUidList;
      this.$refs.batch.isAll = this.isAll;
      this.$refs.batch.formValidate = this.formValidate;
      this.$refs.batch.batchModal = true;
    },
    checkboxItem(e) {
      let id = parseInt(e.rowid);
      let index = this.checkUidList.indexOf(id);
      if (index !== -1) {
        this.checkUidList = this.checkUidList.filter((item) => item !== id);
      } else {
        this.checkUidList.push(id);
      }
    },
    checkboxAll() {
      // 获取选中当前值
      let obj2 = this.$refs.xTable.getCheckboxRecords(true);
      // 获取之前选中值
      let obj = this.$refs.xTable.getCheckboxReserveRecords(true);
      if (
        this.isAll == 0 &&
        this.checkUidList.length <= obj.length &&
        !this.isCheckBox
      ) {
        obj = [];
      }
      obj = obj.concat(obj2);
      let ids = [];
      obj.forEach((item) => {
        ids.push(parseInt(item.id));
      });
      this.checkUidList = ids;
      if (!obj2.length) {
        this.isCheckBox = false;
      }
    },
    allPages(e) {
      this.isAll = e;
      if (e == 0) {
        this.$refs.xTable.toggleAllCheckboxRow();
        // this.checkboxAll();
      } else {
        if (!this.isCheckBox) {
          this.$refs.xTable.setAllCheckboxRow(true);
          this.isCheckBox = true;
          this.isAll = 1;
        } else {
          this.$refs.xTable.setAllCheckboxRow(false);
          this.isCheckBox = false;
          this.isAll = 0;
        }
        this.checkUidList = [];
      }
    },
    allReset() {
      this.isAll = 0;
      this.isCheckBox = false;
      this.$refs.xTable.setAllCheckboxRow(false);
      this.checkUidList = [];
      this.formValidate.page = 1;
    },
    // 批量上架
    onShelves() {
      if (this.isAll != 1 && this.checkUidList.length === 0) {
        this.$Message.warning("请选择要上架的商品");
      } else {
        let data = {
          all: this.isAll,
          ids: this.checkUidList,
        };
        if (this.isAll == 1) {
          data.where = {
            cate_id: this.formValidate.cate_id,
            brand_id: this.formValidate.brand_id,
            store_label_id: this.formValidate.store_label_id,
            store_name: this.formValidate.store_name,
            type: this.formValidate.type,
          };
        }
        productShowApi(data)
          .then((res) => {
            this.$Message.success(res.msg);
            this.allReset();
            this.getHeader();
            this.getList();
          })
          .catch((res) => {
            this.$Message.error(res.msg);
          });
      }
    },
    // 批量下架
    onDismount() {
      if (this.isAll != 1 && this.checkUidList.length === 0) {
        this.$Message.warning("请选择要下架的商品");
      } else {
        let data = {
          all: this.isAll,
          ids: this.checkUidList,
        };
        if (this.isAll == 1) {
          data.where = {
            cate_id: this.formValidate.cate_id,
            brand_id: this.formValidate.brand_id,
            store_label_id: this.formValidate.store_label_id,
            store_name: this.formValidate.store_name,
            type: this.formValidate.type,
          };
        }
        productUnshowApi(data)
          .then((res) => {
            this.$Message.success(res.msg);
            this.allReset();
            this.getHeader();
            this.getList();
          })
          .catch((res) => {
            this.$Message.error(res.msg);
          });
      }
    },
    //erp配置
    getErpConfig() {
      erpConfig()
        .then((res) => {
          this.openErp = res.data.open_erp;
          this.product_status = res.data.product_status;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    stockChange(stock) {
      this.orderList.forEach((item) => {
        if (this.goodsId == item.id) {
          item.branch_stock = stock;
        }
      });
    },
    // 库存管理
    stockControl(row) {
      this.goodsId = row.id;
      this.$refs.stock.modals = true;
      this.$refs.stock.productAttrs(row);
    },
    //跳转刷新
    getPath() {
      this.formValidate.page = 1;
      this.formValidate.type = this.$route.query.type.toString();
      this.getList();
    },
    // 上下架
    changeSwitch(row) {
      setShowApi(row.id, row.is_show)
        .then((res) => {
          this.$Message.success(res.msg);
          this.getHeader();
          this.allReset();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    //获取列表
    getList() {
      this.loading = true;
      productListInfo(this.formValidate)
        .then((res) => {
          this.orderList = res.data.list;
          this.total = res.data.count;
          this.loading = false;
          this.$nextTick(function() {
            if (this.isAll == 1) {
              if (this.isCheckBox) {
                this.$refs.xTable.setAllCheckboxRow(true);
              } else {
                this.$refs.xTable.setAllCheckboxRow(false);
              }
            } else {
              let obj = this.$refs.xTable.getCheckboxReserveRecords(true);
              if (
                !this.checkUidList.length ||
                this.checkUidList.length <= obj.length
              ) {
                this.$refs.xTable.setAllCheckboxRow(false);
              }
            }
          });
        })
        .catch((err) => {
          this.loading = false;
          this.$Message.error(err.msg);
        });
    },
    //头部列表
    getHeader() {
      this.loading = true;
      productHeaderInfo(this.formValidate).then((res) => {
        this.headerList = res.data.list;
        if (this.$route.fullPath === `/supplier/product/index?type=5`) {
          this.getPath();
        } else {
          this.getList();
        }
      });
    },
    // 商品分类；
    goodsCategory() {
      cascaderList(1)
        .then((res) => {
          this.data = res.data;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    //详情
    detail(id) {
      // this.goodsId = id;
      // this.isProductBox = true;
      this.detailsVisible = true;
      this.productId = id;
    },
    // 编辑
    edit(row) {
      this.$router.push({ path: "/supplier/product/edit_product/" + row.id });
    },
    // 编辑
    reply(id) {
      this.$router.push({ path: "/supplier/product/product_reply?id=" + id });
    },
    // 删除
    del(row, num) {
      let delfromData = {
        title: row.is_del ? "恢复商品" : "移入回收站",
        num: num,
        url: `product/product/${row.id}`,
        method: "DELETE",
        ids: "",
        tips: row.is_del ? "确定恢复商品吗" : "确定移入回收站吗",
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.orderList.splice(num, 1);
          this.getHeader();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 复制
    copy(row) {
      this.$router.push({
        path: `/supplier/product/edit_product/`,
        query: { copy: row.id },
      });
    },
    //搜索
    search() {
      this.allReset();
      this.formValidate.page = 1;
      this.getHeader();
    },
    //重置
    reset() {
      this.formValidate.page = 1;
      this.formValidate.store_label_id = [];
      this.formValidate.brand_id = [];
      this.formValidate.store_name = "";
      this.formValidate.cate_id = [];
      this.formValidate.type = "1";
      this.storeDataLabel = [];
      this.getHeader();
    },
    //切换头部列表
    onClickTab(e) {
      this.allReset();
      this.formValidate.type = e;
      this.formValidate.page = 1;
      this.getHeader();
      this.getList();
    },
    //分页
    pageChange(page) {
      this.formValidate.page = page.currentPage;
      this.getList();
    },
  },
};
</script>

<style scoped lang="less">
/deep/.el-cascader {
  .el-input__inner {
    min-height: 32px !important;
  }
  .el-input {
    .el-icon-arrow-down {
      font-size: 12px;
      color: #000;
    }
  }
  .el-cascader__search-input {
    margin-left: 7px !important;
    font-size: 12px;
  }
}
.labelInput {
  border: 1px solid #dcdee2;
  width: 250px;
  padding: 0 5px;
  border-radius: 5px;
  min-height: 30px;
  cursor: pointer;
  .span {
    color: #c5c8ce;
    font-size: 12px;
  }
  .iconxiayi {
    font-size: 12px;
  }
}
.labelClass {
  /deep/.ivu-form-item-content {
    line-height: unset;
  }
}
/deep/.ivu-form-label-left .ivu-form-item-label {
  text-align: right;
}

/deep/.ivu-page-header,
/deep/.ivu-tabs-bar {
  margin-bottom: 0px !important;
  border-bottom: 1px solid #e9e9e9;
}

/deep/.ivu-card-body {
  padding: 14px 20px 10px 20px !important;
}

/deep/.ivu-tabs-nav {
  height: 45px;
}

.bg {
  z-index: 100;
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
}

.box {
  padding: 20px;
  padding-bottom: 0px;
}

.tablebox {
  margin-top: 15px;
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
.ivu-table {
  background-color: #182328;
  color: #fff;
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

.search {
  width: 86px;
  height: 32px;
  background: #1890ff;
  border-radius: 4px;
  text-align: center;
  line-height: 32px;
  font-size: 13px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  color: #ffffff;
  cursor: pointer;
}

.reset {
  width: 86px;
  height: 32px;
  border-radius: 4px;
  border: 1px solid rgba(151, 151, 151, 0.36);
  text-align: center;
  line-height: 32px;
  font-size: 13px;
  font-family: PingFangSC-Regular, PingFang SC;
  font-weight: 400;
  font-weight: 500;
  color: #515a6e;
  // background: #2D8CF0;

  cursor: pointer;
}
</style>
