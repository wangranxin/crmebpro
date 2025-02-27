<template>
  <div>
    <Drawer
      :value="visible"
      :closable="true"
      :styles="{ padding: '0 0 60px' }"
      width="1000"
      @on-visible-change="drawerChange"
    >
      <div class="header">
        <Icon custom="iconfont iconmanjianmanzhe" size="60" />
        <div>
          <div class="title">{{ formValidate.store_name }}</div>
          <div>商品ID：{{ formValidate.id }}</div>
        </div>
      </div>
      <Tabs v-model="currentTab">
        <TabPane
          v-for="item in headTab"
          :key="item.name"
          :label="item.title"
          :name="item.name"
        >
        </TabPane>
      </Tabs>
      <div class="px-25" v-show="currentTab == 1">
        <div class="section">
          <div class="title">基础信息</div>
          <!-- <div class="item-cell flex-y-center">
            <div>商品类型：</div>
            <div class="value">{{ formValidate.product_type | productType }}</div>
          </div> -->
          <div class="item-cell flex-y-center">
            <div>商品名称：</div>
            <div class="value">{{ formValidate.store_name || "-" }}</div>
          </div>
          <div class="item-cell flex-y-center">
            <div>商品分类：</div>
            <div class="value">
              <span v-for="(item, i) in formValidate.cate_name" :key="i">{{item.cate_name}},</span>
            </div>
          </div>
          <ul class="list">
            <li class="item">
              <div>商品品牌：</div>
              <div class="value">
                <span v-for="(item, i) in formValidate.brand_name" :key="i">{{item.brand_name}},</span>
              </div>
            </li>
            <li class="item">
              <div>商品单位：</div>
              <div class="value">
                {{ formValidate.unit_name || "-" }}
              </div>
            </li>
            <li class="item">
              <div>商品编码：</div>
              <div class="value">
                {{ formValidate.code || "-" }}
              </div>
            </li>
            <li class="item" v-if="formValidate.supplier_name">
              <div>供应商：</div>
              <div class="value">{{formValidate.supplier_name}} </div>
            </li>
          </ul>
          <div class="item-cell flex-y-center">
            <div>商品标签：</div>
              <div class="value">
                <span v-for="(item, i) in formValidate.store_label_id" :key="i">{{item.label_name}},</span>
              </div>
          </div>
          <div class="item-cell flex-y-center">
            <div>商品轮播图：</div>
            <div class="flex-y-center">
              <img
                v-for="(item, i) in formValidate.slider_image"
                :key="i"
                :src="item"
                class="slider-pic"
              />
            </div>
          </div>
        </div>
        <div class="section">
          <div class="title">物流设置</div>
          <ul class="list">
            <li class="item">
              <div>配送方式：</div>
              <div class="value">
                <span>{{
                  formValidate.delivery_type.includes("1") ? "快递" : ""
                }}</span>
                <span>{{
                  formValidate.delivery_type.length == 2 ? "/" : ""
                }}</span>
                <span>{{
                  formValidate.delivery_type.includes("2") ? "到店自提" : ""
                }}</span>
              </div>
            </li>
            <li class="item">
              <div>运费设置：</div>
              <div class="value">
                <span v-show="formValidate.freight == 1">包邮</span>
                <span v-show="formValidate.freight == 2">固定邮费</span>
                <span v-show="formValidate.freight == 3">运费模板</span>
              </div>
            </li>
            <li class="item" v-show="formValidate.freight == 2">
              <div>邮费：</div>
              <div class="value">{{ formValidate.postage }}元</div>
            </li>
          </ul>
        </div>
      </div>
      <div class="px-25" v-show="currentTab == 2">
        <div class="section">
          <div class="title">规格库存</div>
          <div class="item-cell flex-y-center">
            <div>商品规格：</div>
            <div class="value">
              {{ formValidate.spec_type == 1 ? "多规格" : "单规格" }}
            </div>
          </div>
          <div class="item-cell">
            <div v-show="formValidate.spec_type == 1">商品属性：</div>
            <div class="mt-14" v-if="formValidate.spec_type == 1">
              <el-table size="small" :data="formValidate.attrs" border>
                <el-table-column
                  label="规格名称"
                  align="center"
                  min-width="140"
                >
                  <template slot-scope="scope">
                    <span>{{ scope.row.attr_arr.toString() }}</span>
                  </template>
                </el-table-column>
                <el-table-column label="图片" align="center" width="80">
                  <template slot-scope="scope">
                    <div class="pictrueBox small flex-center">
                      <div class="pictrue">
                        <img v-lazy="scope.row.pic" />
                      </div>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column
                  label="售价"
                  prop="price"
                  align="center"
                  min-width="100"
                ></el-table-column>
                <el-table-column
                  label="成本价"
                  prop="cost"
                  align="center"
                  min-width="100"
                ></el-table-column>
                <el-table-column
                  label="结算价"
                  prop="settle_price"
                  align="center"
                  min-width="100"
                  v-if="formValidate.type == 2"
                ></el-table-column>
                <el-table-column
                  label="划线价"
                  prop="ot_price"
                  align="center"
                  min-width="100"
                ></el-table-column>
                <el-table-column
                  label="库存"
                  prop="stock"
                  align="center"
                  min-width="100"
                ></el-table-column>
                <el-table-column
                  label="商品编号"
                  prop="bar_code"
                  align="center"
                  min-width="100"
                ></el-table-column>
                <el-table-column
                  label="商品条形码"
                  prop="bar_code_number"
                  align="center"
                  min-width="100"
                ></el-table-column>
                <el-table-column
                  label="重量（KG）"
                  prop="weight"
                  align="center"
                  min-width="100"
                ></el-table-column>
                <el-table-column
                  label="体积(m³)"
                  prop="volume"
                  align="center"
                  min-width="100"
                ></el-table-column>
              </el-table>
            </div>
            <div class="mt-14" v-else>
              <div class="acea-row">
                <span class="w-65">规格图片：</span>
                <img style="width: 40px;" v-viewer :src="formValidate.attr.pic" />
              </div>
              <div class="acea-row row-middle mt20">
                <span class="w-65">原价：</span>
                <span>¥{{ formValidate.attr.price }}</span>
              </div>
              <div class="acea-row row-middle mt20" v-if="formValidate.attr.settle_price">
                <span class="w-65">结算价：</span>
                <span>¥{{ formValidate.attr. settle_price }}</span>
              </div>
              <div class="acea-row row-middle mt20">
                <span class="w-65">划线价：</span>
                <span>¥{{ formValidate.attr.ot_price }}</span>
              </div>
              <div class="acea-row row-middle mt20">
                <span class="w-65">成本价：</span>
                <span>¥{{ formValidate.attr.cost }}</span>
              </div>
              <div class="acea-row row-middle mt20">
                <span class="w-65">库存：</span>
                <span>{{ formValidate.attr.stock }}</span>
              </div>
              <div class="acea-row row-middle mt20">
                <span class="w-65">商品编号：</span>
                <span>{{ formValidate.attr.code }}</span>
              </div>
              <div class="acea-row row-middle mt20">
                <span class="w-65">条形码：</span>
                <span>{{ formValidate.attr.bar_code }}</span>
              </div>
              <div class="acea-row row-middle mt20">
                <span class="w-65">重量(kg)：</span>
                <span>{{ formValidate.attr.weight }}</span>
              </div>
              <div class="acea-row row-middle mt20">
                <span class="w-65">体积(m³)：</span>
                <span>{{ formValidate.attr.volume }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="px-25" v-show="currentTab == 3">
        <div class="section">
          <div class="title">商品详情</div>
          <div class="mt-14" v-html="formValidate.description"></div>
        </div>
      </div>
      <div class="px-25" v-show="currentTab == 4">
        <div class="section">
          <div class="title">营销设置</div>
          <ul class="list">
            <li class="item">
              <div>已售数量：</div>
              <div class="value">{{ formValidate.ficti }}</div>
            </li>
            <li class="item">
              <div>排序：</div>
              <div class="value">{{ formValidate.sort }}</div>
            </li>
            <li class="item">
              <div>赠送积分：</div>
              <div class="value">{{ formValidate.give_integral }}</div>
            </li>
            <li class="item">
              <div>赠送优惠券：</div>
              <div class="value">
                <Tag
                  size="medium"
                  v-for="(item, index) in formValidate.couponName"
                  :key="index"
                  >{{ item.title }}</Tag
                >
              </div>
            </li>
            <li class="item">
              <div>关联用户标签：</div>
              <div class="value">
                <span v-for="(item, i) in formValidate.label_id" :key="i">{{item.label_name}},</span>
              </div>
            </li>
            <li class="item">
              <div>仅会员可见：</div>
              <div class="value">
                {{ formValidate.is_vip_product ? "是" : "否" }}
              </div>
            </li>
            <li class="item">
              <div>是否限购：</div>
              <div class="value">{{ formValidate.is_limit ? "是" : "否" }}</div>
            </li>
            <li class="item">
              <div>限购类型：</div>
              <div class="value">
                {{ formValidate.limit_type == 1 ? "单次限购" : "单人限购" }}
              </div>
            </li>
            <li class="item" v-if="formValidate.is_limit">
              <div>限购数量：</div>
              <div class="value">{{ formValidate.limit_num }}</div>
            </li>
            <li class="item" v-if="formValidate.product_type == 0">
              <div>预售商品：</div>
              <div class="value">
                {{ formValidate.is_presale_product ? "是" : "否" }}
              </div>
            </li>
          </ul>
          <div
            class="item-cell flex-y-center"
            v-if="formValidate.is_presale_product"
          >
            <div>预售活动时间：</div>
            <div class="value">{{ formValidate.presale_time }}</div>
          </div>
          <div
            class="item-cell flex-y-center"
            v-if="formValidate.is_presale_product"
          >
            <div>发货时间：</div>
            <div class="value">
              预售活动结束后{{ formValidate.presale_day }}天之内
            </div>
          </div>
        </div>
        <div class="section">
          <div class="title">其他设置</div>
          <div class="item-cell flex-y-center">
            <div>商品简介：</div>
            <div class="value">{{ formValidate.store_info || "--" }}</div>
          </div>
          <ul class="list">
            <li class="item">
              <div>商品口令：</div>
              <div class="value">{{ formValidate.command_word || "--" }}</div>
            </li>
            <li class="item">
              <div>商品推荐图：</div>
              <div class="value">
                <img
                  :src="formValidate.recommend_image"
                  class="slider-pic"
                  v-show="formValidate.recommend_image"
                />
                <span v-show="!formValidate.recommend_image">--</span>
              </div>
            </li>
          </ul>
          <div class="item-cell flex-y-center">
            <div>服务保障：</div>
            <div class="value">
              <CheckboxGroup v-model="formValidate.ensure_id" class="checkAlls">
                <Checkbox
                  disabled
                  :label="item.id"
                  v-for="(item, index) in ensureData"
                  :key="index"
                  >{{ item.name }}</Checkbox
                >
              </CheckboxGroup>
            </div>
          </div>
          <div class="item-cell">
            <div>商品参数：</div>
            <div class="mt-14">
              <Table
                border
                :columns="specsColumns"
                :data="formValidate.specs"
                ref="table"
                class="specsList"
                width="700"
              >
              </Table>
            </div>
          </div>
        </div>
      </div>
      <div class="px-25" v-show="currentTab == 5">
        <div class="section">
          <div class="title">商品评论</div>
          <Table
            width="940"
            ref="table"
            :columns="replyColumns"
            :data="replyData"
            class="ivu-mt"
            :loading="replyLoading"
            no-data-text="暂无数据"
            no-filtered-data-text="暂无筛选结果"
          >
            <template slot-scope="{ row }" slot="info">
              <div class="imgPic acea-row row-middle">
                <!-- <viewer>
                  <div class="pictrue"><img v-lazy="row.image" /></div>
                </viewer> -->
                <div class="info line2">{{ row.store_name }}</div>
              </div>
            </template>
            <template slot-scope="{ row }" slot="content">
              <div>用户：{{ row.nickname }}</div>
              <div>评分：{{ row.score }}</div>
              <div>
                <div class="mb5 content_font">{{ row.comment }}</div>
                <viewer>
                  <div class="flex-y-center">
                  <img class="slider-pic" v-lazy="item" v-for="(item, index) in row.pics || []" :key="index" />
                </div>
                </viewer>
              </div>
            </template>
            <template slot-scope="{ row, index }" slot="action">
              <a @click="seeReply(row)">查看</a>
              <Divider type="vertical" />
              <a @click="reply(row)">回复</a>
              <Divider type="vertical" />
              <a @click="delReply(row, '删除评论', index)">删除</a>
            </template>
          </Table>
        </div>
      </div>
    </Drawer>
    <replyList ref="replyList"></replyList>
    <Modal v-model="replyModal" scrollable title="回复内容" closable>
      <Form
        ref="replyForm"
        :model="replyForm"
        label-position="right"
        @submit.native.prevent
      >
        <FormItem prop="content">
          <Input
            v-model="replyForm.content"
            type="textarea"
            :rows="4"
            placeholder="请输入回复内容"
          />
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" @click="oks">确定</Button>
        <Button @click="cancels">取消</Button>
      </div>
    </Modal>
  </div>
</template>
<script>
import { Tag } from "view-design";
import { defaultObj } from "../productAdd/formModel.js";
import { productInfoApi, productAllEnsure, replyListApi, setReplyApi } from "@/api/product";
import replyList from "./replyList.vue";
export default {
  name: "productDetails",
  props: {
    productId: {
      type: Number,
      default: 0,
    },
    visible: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      currentTab: "1",
      headTab: [
        { title: "基础信息", name: "1" },
        { title: "规格库存", name: "2" },
        { title: "商品详情", name: "3" },
        { title: "其他设置", name: "4" },
        { title: "商品评论", name: "5" },
      ],
      spinShow: false,
      formValidate: Object.assign({}, defaultObj),
      ensureData: [],
      specsColumns: [
        {
          title: "参数名称",
          key: "name",
          align: "center",
          width: 150,
        },
        {
          title: "参数值",
          key: "value",
          align: "center",
          minWidth: 300,
        },
        {
          title: "排序",
          key: "sort",
          align: "center",
          width: 100,
        },
      ],
      replyColumns: [
        {
          title: "评论ID",
          key: "id",
          width: 80,
        },
        {
          title: "商品信息",
          slot: "info",
          minWidth: 250,
        },
        {
          title: "评价内容",
          slot: "content",
          minWidth: 300,
        },
        {
          title: "评价时间",
          key: "add_time",
          sortable: true,
          width: 150,
        },
        {
          title: "操作",
          slot: "action",
          // fixed: 'right',
          width: 150,
        },
      ],
      replyData: [],
      replyLoading: false,
      replyModal: false,
      replyForm: {
        content: "",
      },
      replyParams:{
        page:1,
        limit:15,
        product_id:0,
      },
      rows:{}
    };
  },
  components:{ replyList },
  filters: {
    productType(val) {
      let typeList = [
        { name: "普通商品", title: "物流发货", id: 0 },
        { name: "卡密/网盘", title: "自动发货", id: 1 },
        { name: "虚拟商品", title: "虚拟发货", id: 3 },
        { name: "次卡商品", title: "到店核销", id: 4 },
      ];
      return typeList.find((item) => item.id == val).name;
    },
  },
  watch:{
    currentTab(val){
      if(val == '5'){
        this.getReplyList()
      }
    }
  },
  methods: {
    // 详情
    getInfo() {
      let that = this;
      that.spinShow = true;
      productInfoApi(that.productId)
        .then(async (res) => {
          that.formValidate = res.data.productInfo;
          this.replyParams.product_id = res.data.productInfo.id;
          if (this.formValidate.spec_type == 0) {
            this.formValidate.attrs[0] = this.formValidate.attr;
          }
          this.spinShow = false;
        })
        .catch((res) => {
          this.spinShow = false;
          this.$Message.error(res.msg);
        });
    },
    getProductAllEnsure() {
      productAllEnsure()
        .then((res) => {
          this.ensureData = res.data;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    drawerChange(e) {
      if (e) {
        this.currentTab = "1";
        this.getInfo();
        this.getProductAllEnsure();
        // this.visible = true;
      } else {
        this.$emit("update:visible", false);
      }
    },
    // 商品评论
    getReplyList() {
      this.replyLoading = true;
      replyListApi(this.replyParams).then(async (res) => {
          let data = res.data;
          this.replyData = data.list;
          this.replyLoading = false;
        })
        .catch((res) => {
          this.replyLoading = false;
          this.$Message.error(res.msg);
        });
    },
    // 查看评论列表
    seeReply(row) {
      this.$refs.replyList.modals = true;
      this.$refs.replyList.getList(row.id);
    },
    // 回复评论
    reply(row) {
      this.replyModal = true;
      this.rows = row;
      this.replyForm.content = row.replyComment ? row.replyComment.content : "";
    },
    // 删除评论
    delReply(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `product/reply/${row.id}`,
        method: "DELETE",
        ids: "",
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.replyData.splice(num, 1);
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    cancels() {
      this.replyModal = false;
      this.$refs["replyForm"].resetFields();
    },
    oks() {
      this.replyModal = true;
      setReplyApi(this.replyForm, this.rows.id).then(async (res) => {
        this.$Message.success(res.msg);
        this.replyModal = false;
        this.$refs["replyForm"].resetFields();
        this.getReplyList();
      })
      .catch((res) => {
        this.$Message.error(res.msg);
      });
    },
  },
};
</script>
<style>
.header {
  display: flex;
  align-items: center;
  padding: 30px 0 25px 25px;
  font-size: 13px;
  color: #606266;

  .iconfont {
    margin-right: 12px;
    color: #2d8cf0;
  }

  .title {
    font-weight: 500;
    font-size: 16px;
    color: #000000;
  }
}
.w-65{
  width: 65px;
}
.px-25 {
  padding: 0 25px;
}
.section {
  padding: 25px 0;
  border-bottom: 1px dashed #eeeeee;

  .title {
    padding-left: 10px;
    border-left: 3px solid #1890ff;
    font-size: 15px;
    line-height: 15px;
    color: #303133;
  }

  .list {
    display: flex;
    flex-wrap: wrap;
    list-style: none;
  }

  .item {
    flex: 0 0 calc((100% / 3));
    display: flex;
    margin-top: 16px;
    font-size: 13px;
  }
  .item-cell {
    margin-top: 16px;
    font-size: 13px;
  }
}
.slider-pic {
  display: block;
  width: 40px;
  height: 40px;
  object-fit: cover;
  margin-right: 10px;
}
</style>
