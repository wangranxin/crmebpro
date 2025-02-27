<template>
  <!-- 商品-商品列表 -->
  <div class="article-manager">
    <searchForm 
      ref="searchForm"
      :data1="data1" 
      :brandData="brandData" 
      :supplierList="supplierList"
      :goodsDataLabel="goodsDataLabel"
      @on-change="userSearchs"></searchForm>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <!-- 相关操作 -->
      <div class="new_tab">
        <Tabs v-model="artFrom.type" @on-click="onClickTab">
          <TabPane
            :label="item.name + ' (' + item.count + ')'"
            :name="item.type.toString()"
            v-for="(item, index) in headeNum"
            :key="index"
          />
        </Tabs>
      </div>
      <div class="acea-row row-between-wrapper">
        <div class="Button">
          <Button type="primary" class="bnt mr15" @click="addTypeShow = true">添加商品</Button>
          <Button
            v-auth="['product-crawl-save']"
            type="success"
            class="bnt mr15"
            @click="onCopy"
            >商品采集
          </Button>
          <Tooltip
            content="本页至少选中一项"
            :disabled="!!checkUidList.length && isAll == 0"
          >
            <Button
              v-auth="['product-product-product_show']"
              class="bnt mr15"
              :disabled="!checkUidList.length && isAll == 0"
              @click="onDismount"
              v-show="artFrom.type === '1'"
              >批量下架
            </Button>
          </Tooltip>
          <Tooltip
            content="本页至少选中一项"
            :disabled="!!checkUidList.length && isAll == 0"
          >
            <Button
              v-auth="['product-product-product_show']"
              class="bnt mr15"
              :disabled="!checkUidList.length && isAll == 0"
              @click="onShelves"
              v-show="artFrom.type === '2'"
              >批量上架
            </Button>
          </Tooltip>
          <Tooltip
            content="本页至少选中一项"
            :disabled="!!checkUidList.length && isAll == 0"
          >
            <Button
              v-auth="['product-product-product_show']"
              class="bnt"
              :disabled="!checkUidList.length && isAll == 0"
              @click="openBatch"
              >批量设置
            </Button>
          </Tooltip>
          <Dropdown class="ml-14" @on-click="goodsMove">
            <Button>商品迁移 <Icon type="ios-arrow-down" /> </Button>
            <template #list>
              <DropdownMenu>
                <DropdownItem :name="1">商品导入</DropdownItem>
                <DropdownItem :name="2">商品导出</DropdownItem>
              </DropdownMenu>
            </template>
          </Dropdown>
          <Button
            v-auth="['export-storeProduct']"
            class="export ml-14"
            @click="exports()"
            >数据导出
          </Button>
        </div>
      </div>
      <!-- 商品列表表格 -->
      <vxe-table
        ref="xTable"
        class="mt25"
        :loading="loading"
        row-id="id"
        :expand-config="{ accordion: true }"
        :checkbox-config="{ reserve: true }"
        @checkbox-all="checkboxAll"
        @checkbox-change="checkboxItem"
        :data="tableList"
      >
        <vxe-column
          type=""
          width="0"
          v-show="artFrom.type == 1 || artFrom.type == 2"
        ></vxe-column>
        <vxe-column
          type="expand"
          width="35"
          v-if="artFrom.type == 1 || artFrom.type == 2"
        >
          <template #content="{ row }">
            <div class="tdinfo">
              <Row class="expand-row">
                <Col span="8">
                  <span class="expand-key">商品分类：</span>
                  <span class="expand-value">{{ row.cate_name }}</span>
                </Col>
                <Col span="8">
                  <span class="expand-key">商品市场价格：</span>
                  <span class="expand-value">{{ row.ot_price }}</span>
                </Col>
                <Col span="8">
                  <span class="expand-key">成本价：</span>
                  <span class="expand-value">{{ row.cost }}</span>
                </Col>
              </Row>
              <Row class="expand-row">
                <Col span="8">
                  <span class="expand-key">收藏：</span>
                  <span class="expand-value">{{ row.collect }}</span>
                </Col>
                <Col span="8">
                  <span class="expand-key">虚拟销量：</span>
                  <span class="expand-value">{{ row.ficti }} {{ name }}</span>
                </Col>
                <Col span="8" v-show="row.is_verify === -1">
                  <span class="expand-key">审核未通过原因：</span>
                  <span class="expand-value">{{ row.refusal }}</span>
                </Col>
                <Col span="8" v-show="row.is_verify === -2">
                  <span class="expand-key">强制下架原因：</span>
                  <span class="expand-value">{{ row.refusal }}</span>
                </Col>
              </Row>
            </div>
          </template>
        </vxe-column>
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
        <vxe-column field="id" title="商品ID" width="80"></vxe-column>
        <vxe-column field="image" title="商品图" width="80">
          <template v-slot="{ row }">
            <div class="tabBox_img" v-viewer>
              <img v-lazy="row.image" />
            </div>
          </template>
        </vxe-column>
        <vxe-column field="store_name" title="商品名称" min-width="250">
          <template v-slot="{ row }">
            <Tooltip
              theme="dark"
              max-width="300"
              :delay="600"
              :content="row.store_name"
              :transfer="true"
            >
              <div class="line2"><span class="text-blue">【{{row.spec_type?'多规格':'单规格'}}】</span>{{ row.store_name }}</div>
            </Tooltip>
          </template>
        </vxe-column>
        <vxe-column
          field="plate_name"
          title="商品来源"
          min-width="100"
        ></vxe-column>
        <vxe-column field="product_type" title="商品类型" min-width="100">
          <template v-slot="{ row }">
            <span v-if="row.product_type == 0">普通商品</span>
            <span v-if="row.product_type == 1">卡密商品</span>
            <span v-if="row.product_type == 3">虚拟商品</span>
            <span v-if="row.product_type == 4">次卡商品</span>
          </template>
        </vxe-column>
        <vxe-column field="price" title="商品售价" min-width="90"></vxe-column>
        <vxe-column field="sales" title="销量" min-width="90"></vxe-column>
        <vxe-column
          field="stock"
          title="库存"
          min-width="80"
          v-show="artFrom.type != 6"
        ></vxe-column>
        <vxe-column field="sort" title="排序" min-width="70"></vxe-column>
        <vxe-column field="state" title="状态" min-width="120">
          <template v-slot="{ row }">
            <i-switch
              v-model="row.is_show"
              :value="row.is_show"
              :true-value="1"
              :false-value="0"
              :disabled="artFrom.type == 6 ? true : false"
              @on-change="changeSwitch(row)"
              size="large"
            >
              <span slot="open">上架</span>
              <span slot="close">下架</span>
            </i-switch>
            <div v-if="row.auto_off_time" class="style-add">
              定时下架：<br />{{ row.auto_off_time | timeFormat }}
            </div>
          </template>
        </vxe-column>
        <vxe-column
          field="action"
          title="操作"
          align="center"
          width="220"
          fixed="right"
        >
          <template #default="{ row, rowIndex }">
            <a @click="details(row.id)">详情</a>
            <Divider type="vertical" />
            <a @click="edit(row)">编辑</a>
            <Divider type="vertical" />
            <a @click="stockControl(row)">库存</a>
            <Divider type="vertical" />
            <a v-if="artFrom.type === '0'" @click="auditGoods(row)">审核</a>
            <Divider v-if="artFrom.type === '0'" type="vertical" />
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
                  <DropdownItem name="8">会员价</DropdownItem>
                  <DropdownItem name="9">佣金管理</DropdownItem>
                  <DropdownItem name="1">查看评论</DropdownItem>
                  <DropdownItem name="2" v-if="artFrom.type === '6'"
                    >恢复商品
                  </DropdownItem>
                  <DropdownItem name="7" v-if="artFrom.type === '6'"
                    >删除商品
                  </DropdownItem>
                  <DropdownItem name="3" v-if="artFrom.type !== '6'"
                    >移到回收站</DropdownItem
                  >
                  <DropdownItem name="4">商品预览</DropdownItem
                  >
                  <DropdownItem name="6">复制商品</DropdownItem>
                </DropdownMenu>
              </Dropdown>
            </template>
          </template>
        </vxe-column>
      </vxe-table>
      <div class="acea-row row-right mt-20">
        <Page
          :total="total"
          :current="artFrom.page"
          show-elevator
          show-total
          @on-change="pageChange"
          :page-size="artFrom.limit"
        />
      </div>
      
      <attribute
        :attrTemplate="attrTemplate"
        v-on:changeTemplate="changeTemplate"
      ></attribute>
    </Card>
    <!-- 生成淘宝京东表单-->
    <Modal
      v-model="modals"
      :z-index="100"
      class="Box"
      scrollable
      footer-hide
      closable
      title="复制淘宝、天猫、京东、苏宁、1688"
      :mask-closable="false"
      width="1200"
      height="500"
    >
      <tao-bao ref="taobaos" v-if="modals" @on-close="onClose"></tao-bao>
    </Modal>
    <!-- 配送方式 -->
    <Modal
      v-model="modalsType"
      scrollable
      title="配送方式"
      :closable="false"
      class-name="vertical-center-modal"
    >
      <Form :label-width="90" @submit.native.prevent>
        <FormItem label="配送方式：" class="deliveryStyle" required>
          <CheckboxGroup v-model="delivery_type">
            <Checkbox label="1">快递</Checkbox>
            <!-- <Checkbox label="3">门店配送</Checkbox> -->
            <Checkbox label="2">自提</Checkbox>
          </CheckboxGroup>
        </FormItem>
      </Form>
      <div slot="footer">
        <Button type="primary" @click="putDelivery">提交</Button>
        <Button @click="cancelDelivery">取消</Button>
      </div>
    </Modal>
    <!-- 商品弹窗 -->
    <div v-if="isProductBox">
      <div class="bg" @click.stop="isProductBox = false"></div>
      <goodsDetail :goodsId="goodsId" :product="1"></goodsDetail>
    </div>
    <stockEdit ref="stock" @stockChange="stockChange"></stockEdit>
    <!-- 商品详情 -->
    <productDetails
      :visible.sync="detailsVisible"
      :product-id="productId"
      @saved="getDataList"
    ></productDetails>
    <!-- 批量设置 -->
    <Modal
      v-model="batchModal"
      title="批量设置"
      width="750"
      class-name="batch-modal"
      @on-visible-change="batchVisibleChange"
    >
      <Alert show-icon>每次只能修改一项，如需修改多项，请多次操作。</Alert>
      <Row type="flex" align="middle">
        <Col span="5">
          <Menu :active-name="menuActive" width="auto" @on-select="menuSelect">
            <MenuItem :name="1">商品分类</MenuItem>
            <MenuItem :name="2">商品标签</MenuItem>
            <MenuItem :name="9">商品品牌</MenuItem>
            <MenuItem :name="3">物流设置</MenuItem>
            <MenuItem :name="8">运费设置</MenuItem>
            <MenuItem :name="4">购买即送</MenuItem>
            <MenuItem :name="5">关联用户标签</MenuItem>
            <MenuItem :name="6">活动推荐</MenuItem>
            <MenuItem :name="7">自定义留言</MenuItem>
          </Menu>
        </Col>
        <Col span="19">
          <Form :model="batchData" :label-width="122">
            <FormItem v-if="menuActive === 1" label="商品分类：">
              <el-cascader
                v-model="batchData.cate_id"
                :options="data1"
                :props="props"
                size="small"
                filterable
                clearable
                :class="{ single: !batchData.cate_id.length }"
              >
              </el-cascader>
            </FormItem>
            <FormItem v-if="menuActive === 2" label="商品标签：">
              <div class="select-tag" @click="openStoreLabel">
                <div v-if="storeDataLabel.length">
                  <Tag
                    v-for="item in storeDataLabel"
                    :key="item.id"
                    closable
                    @on-close="tagClose(item.id)"
                  >
                    {{ item.label_name }}
                  </Tag>
                </div>
                <span v-else class="placeholder">请选择</span>
                <Icon type="ios-arrow-down" />
              </div>
            </FormItem>
            <FormItem v-if="menuActive === 9" label="商品品牌：">
              <el-cascader
                v-model="batchData.brand_id"
                :options="brandDataList"
                size="small"
                filterable
                clearable
              >
              </el-cascader>
            </FormItem>
            <FormItem v-if="menuActive === 3" label="物流方式：">
              <CheckboxGroup v-model="batchData.delivery_type" size="small">
                <Checkbox :label="1">快递</Checkbox>
                <!-- <Checkbox :label="3">门店配送</Checkbox> -->
                <Checkbox :label="2">自提</Checkbox>
              </CheckboxGroup>
            </FormItem>
            <FormItem v-if="menuActive === 8" label="运费设置：">
              <RadioGroup v-model="batchData.freight">
                <Radio :label="1">包邮</Radio>
                <Radio :label="2">固定邮费</Radio>
                <Radio :label="3">运费模板</Radio>
              </RadioGroup>
            </FormItem>
            <FormItem v-if="menuActive === 8 && batchData.freight === 2">
              <div class="input-number">
                <InputNumber v-model="batchData.postage" :min="0"></InputNumber>
                <span class="suffix">元</span>
              </div>
            </FormItem>
            <FormItem v-if="menuActive === 8 && batchData.freight === 3">
              <Select v-model="batchData.temp_id">
                <Option
                  v-for="item in templateList"
                  :key="item.id"
                  :value="item.id"
                  >{{ item.name }}</Option
                >
              </Select>
            </FormItem>
            <FormItem v-if="menuActive === 4" label="购买送积分：">
              <InputNumber
                v-model="batchData.give_integral"
                :min="0"
              ></InputNumber>
            </FormItem>
            <FormItem v-if="menuActive === 4" label="购买送优惠券：">
              <div class="select-tag" @click="addCoupon">
                <div v-if="couponName.length">
                  <Tag
                    v-for="item in couponName"
                    :key="item.id"
                    closable
                    @on-close="handleClose(item)"
                    >{{ item.title }}
                  </Tag>
                </div>
                <span v-else class="placeholder">请选择</span>
                <Icon type="ios-arrow-down" />
              </div>
            </FormItem>
            <FormItem v-if="menuActive === 5" label="关联用户标签：">
              <div class="select-tag" @click="openLabel">
                <div v-if="dataLabel.length">
                  <Tag
                    v-for="item in dataLabel"
                    :key="item.id"
                    closable
                    @on-close="tagClose(item.id)"
                  >
                    {{ item.label_name }}
                  </Tag>
                </div>
                <span v-else class="placeholder">请选择</span>
                <Icon type="ios-arrow-down" />
              </div>
            </FormItem>
            <FormItem v-if="menuActive === 6" label="商品推荐：">
              <CheckboxGroup v-model="batchData.recommend" size="small">
                <Checkbox label="is_hot">热卖单品</Checkbox>
                <Checkbox label="is_benefit">促销单品</Checkbox>
                <Checkbox label="is_best">精品推荐</Checkbox>
                <Checkbox label="is_new">首发新品</Checkbox>
                <Checkbox label="is_good">优品推荐</Checkbox>
              </CheckboxGroup>
            </FormItem>
            <FormItem v-if="menuActive === 7" label="自定义留言：">
              <i-switch
                v-model="customBtn"
                size="large"
                @on-change="customMessBtn"
              >
                <span slot="open">开启</span>
                <span slot="close">关闭</span>
              </i-switch>
              <div class="mt10" v-if="customBtn">
                <Select
                  v-model="batchData.system_form_id"
                  filterable
                  placeholder="请选择"
                  @on-change="changeForm"
                >
                  <Option
                    v-for="(item, index) in formList"
                    :value="item.id"
                    :key="index"
                    >{{ item.name }}
                  </Option>
                </Select>
              </div>
              <div v-if="customBtn && batchData.system_form_id">
                <Table
                  border
                  :columns="formColumns"
                  :data="formTypeList"
                  ref="table"
                  class="customTab"
                  width="100%"
                  max-height="260"
                >
                  <template slot-scope="{ row }" slot="require">
                    <span>{{ row.require ? "必填" : "不必填" }}</span>
                  </template>
                </Table>
              </div>
            </FormItem>
          </Form>
        </Col>
      </Row>
      <div slot="footer">
        <Button @click="cancelBatch">取消</Button>
        <Button type="primary" @click="saveBatch">保存</Button>
      </div>
    </Modal>
    <!-- 用户标签 -->
    <Modal
      v-model="labelShow"
      scrollable
      title="选择用户标签"
      :closable="true"
      width="540"
      :footer-hide="true"
      :mask-closable="false"
    >
      <userLabel
        ref="userLabel"
        @activeData="activeData"
        @close="labelClose"
      ></userLabel>
    </Modal>
    <!-- 商品标签 -->
    <Modal
      v-model="storeLabelShow"
      scrollable
      title="选择商品标签"
      :closable="true"
      width="540"
      :footer-hide="true"
      :mask-closable="false"
    >
      <storeLabelList
        ref="storeLabel"
        @activeData="activeStoreData"
        @close="storeLabelClose"
      ></storeLabelList>
    </Modal>
    <coupon-list
      ref="couponTemplates"
      @nameId="nameId"
      :couponids="coupon_ids"
      :updateIds="updateIds"
      :updateName="updateName"
    ></coupon-list>
    <Modal
      v-model="auditModal"
      scrollable
      title="审核商品"
      :closable="true"
      width="540"
    >
      <Form :model="auditForm" :label-width="80">
        <div>
          <Alert type="warning" show-icon
            >供应商商品首次审核时，售价、划线价默认为0，请检查后审核!</Alert
          >
        </div>
        <FormItem label="审核状态：">
          <RadioGroup v-model="auditForm.is_verify">
            <Radio :label="1">通过</Radio>
            <Radio :label="-1">拒绝</Radio>
          </RadioGroup>
        </FormItem>
        <FormItem label="上架时机：">
          <RadioGroup v-model="auditForm.is_show" @on-change="goodsOn">
            <Radio :label="1">
              <span>立即上架</span>
            </Radio>
            <Radio :label="2">
              <span>定时上架</span>
            </Radio>
            <Radio :label="0">
              <span>放入仓库</span>
            </Radio>
          </RadioGroup>
        </FormItem>
        <FormItem label="选择时间：" v-if="auditForm.is_show == 2">
          <DatePicker
            type="datetime"
            :value="auditForm.auto_on_time"
            v-model="auditForm.auto_on_time"
            placeholder="请选择上架时间"
            format="yyyy-MM-dd HH:mm"
            style="width: 260px;"
          ></DatePicker>
        </FormItem>
        <FormItem label="拒绝原因：" v-if="auditForm.is_verify == -1">
          <Input
            v-model="auditForm.refusal"
            type="textarea"
            :rows="3"
            placeholder="请输入拒绝原因"
          />
        </FormItem>
      </Form>
      <div slot="footer">
        <Button @click="cancelBatch">取消</Button>
        <Button type="primary" @click="saveAuditConfirm">保存</Button>
      </div>
    </Modal>
    <Modal
      v-model="importShow"
      scrollable
      :mask-closable="false"
      title="商品导入"
      footer-hide
      width="900"
    >
    <goodsImport v-if="importShow" @close="importShow = false"></goodsImport>
    </Modal>
    <Modal
      v-model="addTypeShow"
      scrollable
      :mask-closable="false"
      title="选择商品类型"
      footer-hide
      width="564"
      class-name="vertical-center"
    >
      <div class="flex-between-center">
        <div
            class="productType"
            :class="product_type == item.id ? 'on' : ''"
            v-for="(item, index) in productType"
            :key="index"
            @click="productTypeTap(1, item)"
          >
            <div class="name">{{ item.name }}</div>
            <div class="title">({{ item.title }})</div>
            <div
              v-if="product_type == item.id"
              class="jiao"
            ></div>
            <div
              v-if="product_type == item.id"
              class="iconfont iconduihao"
            ></div>
          </div>
      </div>
      <div class="acea-row row-right row-middle mt-30">
        <Button @click="addTypeShow = false">取消</Button>
        <Button type="primary"  @click="productTypeMenu" class="ml-14">确认</Button>
      </div>
    </Modal>
    <brokerageSet :visible="showBrokerage" :productId="productId" @close="()=>{showBrokerage = false}"></brokerageSet>
    <vipPriceSet :visible="showVipPrice" :productId="productId" @close="()=>{showVipPrice = false}"></vipPriceSet>
  </div>
</template>

<script>
import goodsDetail from "@/pages/kefu/pc/components/goods_detail";
import stockEdit from "../components/stockEdit.vue";
import expandRow from "./tableExpand.vue";
import productDetails from "../components/productDetails.vue";
import storeLabelList from "@/components/storeLabelList";
import userLabel from "@/components/labelList";
import couponList from "@/components/couponList";
import brokerageSet from "../components/brokerageSet.vue";
import vipPriceSet from "../components/vipPriceSet.vue";
import attribute from "./components/attribute";
import goodsImport from "./components/goodsImport";
import searchForm from "./components/searchForm.vue"
import toExcel from "../../../utils/Excel.js";
import { mapState } from "vuex";
import taoBao from "./taoBao";
import dayjs from "dayjs";
import Setting from "@/setting";
import util from "@/libs/util";
import {
  getGoodHeade,
  getGoods,
  PostgoodsIsShow,
  treeListApi,
  productShowApi,
  productUnshowApi,
  storeProductApi,
  cascaderListApi,
  productCache,
  cacheDelete,
  setDeliveryType,
  productReviewApi,
  forcedRemovalApi,
  batchProcess,
  productGetTemplateApi,
  brandList,
  allSystemForm,
  productGetVerifyApi,
  productExportApi
} from "@/api/product";
import { systemFormInfo } from "@/api/setting";
import { getSupplierList } from "@/api/supplier";
import { erpConfig, erpProduct } from "@/api/erp";
import exportExcel from "@/utils/newToExcel.js";
export default {
  name: "product_productList",
  components: {
    expandRow,
    attribute,
    taoBao,
    goodsDetail,
    stockEdit,
    productDetails,
    storeLabelList,
    userLabel,
    couponList,
    brokerageSet,
    vipPriceSet,
    goodsImport,
    searchForm
  },
  filters: {
    timeFormat: (value) => dayjs(value * 1000).format("YYYY-MM-DD HH:mm"),
  },
  computed: {
    ...mapState("admin/layout", ["isMobile"]),
    ...mapState("admin/userLevel", ["categoryId"]),
    labelWidth() {
      return this.isMobile ? undefined : 75;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
    brandDataList() {
      const valueToString = (item) => {
        item.value = item.value.toString();
        if (item.children) {
          item.children.map(valueToString);
        }
        return item;
      };
      return this.brandData.map(valueToString);
    },
  },
  data() {
    return {
      timeVal:[],
      formTypeList: [],
      formColumns: [
        {
          title: "表单标题",
          key: "title",
          minWidth: 100,
        },
        {
          title: "表单类型",
          key: "name",
          minWidth: 100,
        },
        {
          title: "是否必填",
          slot: "require",
          minWidth: 100,
        },
      ],
      supplierList: [],
      header: {}, //请求头部信息
      erpUrl: Setting.apiBaseURL + "/file/upload/1",
      template: false,
      modals: false,
      modalsType: false,
      delivery_type: [],
      // 订单列表
      orderData: {
        page: 1,
        limit: 10,
        type: 6,
        status: "",
        time: "",
        real_name: "",
        store_id: "",
      },
      artFrom: {
        page: 1,
        limit: 15,
        sales_range: [],
        price_range: [],
        stock_range: [],
        collect_range: [],
        type: "1"
      },
      list: [],
      tableList: [],
      headeNum: [],
      treeSelect: [],
      isProductBox: false,
      loading: false,
      data: [],
      total: 0,
      props: { emitPath: false, multiple: true, checkStrictly: true },
      attrTemplate: false,
      ids: [],
      display: "none",
      formSelection: [],
      selectionCopy: [],
      checkBox: false,
      isAll: 0,
      data1: [],
      value1: [],
      alertShow: false,
      goodsId: "",
      columns3: [],
      openErp: false,
      // activeKey:1
      productId: 0,
      detailsVisible: false,
      batchModal: false,
      menuActive: 1,
      storeLabelShow: false,
      storeDataLabel: [],
      labelShow: false,
      dataLabel: [],
      coupon_ids: [],
      updateIds: [],
      updateName: [],
      couponName: [],
      //自定义留言下拉选择
      customList: [
        {
          value: "text",
          label: "文本框",
        },
        {
          value: "number",
          label: "数字",
        },
        {
          value: "email",
          label: "邮件",
        },
        {
          value: "data",
          label: "日期",
        },
        {
          value: "time",
          label: "时间",
        },
        {
          value: "id",
          label: "身份证",
        },
        {
          value: "phone",
          label: "手机号",
        },
        {
          value: "img",
          label: "图片",
        },
      ],
      customBtn: false,
      batchData: {
        system_form_id: 0, //自定义表单id
        cate_id: [],
        store_label_id: [],
        delivery_type: [],
        freight: 1,
        postage: 0,
        temp_id: 0,
        give_integral: 0,
        coupon_ids: [],
        label_id: [],
        recommend: [],
        custom_form: [],
        brand_id: [],
      },
      templateList: [],
      brandData: [],
      goodsDataLabel: [],
      isLabel: 0,
      checkUidList: [],
      isCheckBox: false,
      formList: [],
      auditForm: {
        is_verify: 1,
        refusal: "",
        id: "",
        is_show: 0,
        auto_on_time: "",
      },
      auditModal: false,
      showBrokerage: false,
      showVipPrice: false,
      importShow: false,
      productType: [
        { name: "普通商品", title: "物流发货", id: 0 },
        { name: "卡密/网盘", title: "自动发货", id: 1 },
        { name: "虚拟商品", title: "虚拟发货", id: 3 },
        { name: "次卡商品", title: "到店核销", id: 4 },
      ],
      product_type: 0,
      addTypeShow: false
    };
  },
  watch: {
    $route() {
      if (this.$route.fullPath === "/admin/product/product_list?type=5") {
        this.getPath();
      }
    },
    storeDataLabel(value) {
      this.batchData.store_label_id = value.map((item) => item.id);
      this.artFrom.store_label_id = value.map((item) => item.id);
    },
    couponName(value) {
      this.batchData.coupon_ids = value.map((item) => item.id);
    },
    dataLabel(value) {
      this.batchData.label_id = value.map((item) => item.id);
    },
    "batchData.system_form_id"(value) {
      this.customBtn = !!value;
    },
    "batchData.freight"(value) {
      switch (value) {
        case 1:
          this.batchData.postage = 0;
          this.batchData.temp_id = 0;
          break;
        case 2:
          this.batchData.temp_id = 0;
          break;
        case 3:
          this.batchData.postage = 0;
          break;
      }
    },
  },
  // created() {
  //   this.getToken();
  //   productCache()
  //     .then((res) => {
  //       const info = res.data.info;
  //       if (!Array.isArray(info)) {
  //         this.alertShow = true;
  //       }
  //     })
  //     .catch((err) => {
  //       this.$Message.error(err.msg);
  //     });
  //   this.getErpConfig();
  //   this.getBrandList();
  //   this.allFormList();
  // },
  mounted() {
    this.getToken();
    this.goodsCategory();
    this.getBrandList();
    this.allFormList();
    this.productGetTemplate();
    this.getSupplierList();
    if (this.$route.fullPath === "/admin/product/product_list?type=5") {
      this.getPath();
    } else {
      this.getDataList();
    }
    
    this.goodHeade();
  },
  activated() {
    this.getDataList();
    this.goodHeade();
  },
  beforeRouteLeave(to, from, next) {
    this.$refs.searchForm.resetForm();
    next();
  },
  methods: {
    createTime(e){
      this.timeVal = e;
    },
    allReset() {
      this.isAll = 0;
      this.isCheckBox = false;
      this.$refs.xTable.setAllCheckboxRow(false);
      this.checkUidList = [];
    },
    changeForm(e) {
      this.getSystemFormInfo(e, { type: 1 });
    },
    getSystemFormInfo(e, data) {
      systemFormInfo(e, data)
        .then((res) => {
          this.formTypeList = res.data.info;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    allFormList() {
      allSystemForm()
        .then((res) => {
          this.formList = res.data;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
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
    closeStoreLabel(label) {
      let index = this.goodsDataLabel.indexOf(
        this.goodsDataLabel.filter((d) => d.id == label.id)[0]
      );
      this.goodsDataLabel.splice(index, 1);
      // 商品标签id
      let storeActiveIds = [];
      this.goodsDataLabel.forEach((item) => {
        storeActiveIds.push(item.id);
      });
      // this.artFrom.store_label_id = storeActiveIds;
      // this.userSearchs();
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
    //获取供应商列表；
    getSupplierList() {
      getSupplierList()
        .then(async (res) => {
          this.supplierList = res.data;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    saveAuditConfirm() {
      productGetVerifyApi(this.auditForm.id).then((res) => {
        if (res.data.verify == 1) {
          this.$Modal.confirm({
            title: "提示",
            content: "商品售价小于结算价，是否通过审核",
            onOk: () => {
              productReviewApi(this.auditForm.id, this.auditForm)
                .then((res) => {
                  this.auditForm.id = "";
                  this.auditForm.refusal = "";
                  this.auditModal = false;
                  this.getDataList();
                  this.goodHeade();
                })
                .catch((err) => {
                  this.$Message.error(res.msg);
                });
            },
          });
        } else {
          productReviewApi(this.auditForm.id, this.auditForm)
            .then((res) => {
              this.auditForm.id = "";
              this.auditForm.refusal = "";
              this.auditModal = false;
              this.getDataList();
              this.goodHeade();
            })
            .catch((err) => {
              this.$Message.error(res.msg);
            });
        }
      });
    },
    goodsOn(e) {
      if (e == 0 || e == 1) {
        this.auditForm.auto_on_time = "";
      }
    },
    // 审核
    auditGoods(row) {
      this.auditForm.id = row.id;
      this.auditModal = true;
    },
    // 强制下架
    forcedRemoval(row) {
      this.$modalForm(forcedRemovalApi(row.id)).then(() => this.getDataList());
    },
    frontDownload() {
      let a = document.createElement("a"); //创建一个<a></a>标签
      a.href = "/statics/ERP商品导入模板.xlsx"; // 给a标签的href属性值加上地址，注意，这里是绝对路径，不用加 点.
      a.download = "ERP商品导入模板.xlsx"; //设置下载文件文件名，这里加上.xlsx指定文件类型，pdf文件就指定.fpd即可
      a.style.display = "none"; // 障眼法藏起来a标签
      document.body.appendChild(a); // 将a标签追加到文档对象中
      a.click(); // 模拟点击了a标签，会触发a标签的href的读取，浏览器就会自动下载了
      a.remove(); // 一次性的，用完就删除a标签
    },
    handleFormatError(file) {
      return this.$Message.error("必须上传xlsx格式文件");
    },
    // 上传头部token
    getToken() {
      this.header["Authori-zation"] = "Bearer " + util.cookies.get("token");
    },
    upFile(res) {
      erpProduct({ path: res.data.src })
        .then((res) => {
          this.$Message.success(res.msg);
          this.getDataList();
        })
        .catch((err) => {
          return this.$Message.error(err.msg);
        });
    },
    beforeUpload() {
      let promise = new Promise((resolve) => {
        this.$nextTick(function() {
          resolve(true);
        });
      });
      return promise;
    },
    //erp配置
    getErpConfig() {
      erpConfig()
        .then((res) => {
          this.openErp = res.data.open_erp;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    stockChange(stock) {
      this.tableList.forEach((item) => {
        if (this.goodsId == item.id) {
          item.stock = stock;
        }
      });
    },
    // 库存管理
    stockControl(row) {
      this.goodsId = row.id;
      this.$refs.stock.modals = true;
      this.$refs.stock.productAttrs(row);
    },
    cancelDelivery() {
      this.modalsType = false;
      this.delivery_type = [];
    },
    deliveryType() {
      this.modalsType = true;
    },
    putDelivery() {
      if (this.delivery_type.length === 0) {
        this.$Message.error("请选择要配送的商品");
      } else {
        let data = {
          all: this.isAll,
          delivery_type: this.delivery_type,
          ids: this.checkUidList,
        };
        // if (this.isAll == 0) {
        //   data.ids = this.checkUidList;
        // }
        setDeliveryType(data)
          .then((res) => {
            this.$Message.success(res.msg);
            this.modalsType = false;
            this.delivery_type = [];
            this.isAll = 0;
            this.getDataList();
          })
          .catch((res) => {
            this.$Message.error(res.msg);
          });
      }
    },
    // 商品详情
    lookGoods(id) {
      this.goodsId = id;
      this.isProductBox = true;
    },
    closeAlert() {
      cacheDelete()
        .then((res) => {
          this.$Message.success(res.msg);
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    getPath() {
      this.columns2 = [...this.columns];
      if (name !== "1" && name !== "2") {
        this.columns2.shift();
      }
      this.artFrom.page = 1;
      this.artFrom.type = this.$route.query.type.toString();
      this.getDataList();
    },
    changeMenu(row, name, index) {
      switch (name) {
        case "1":
          this.$router.push({ path: "/admin/product/product_reply/" + row.id });
          break;
        case "2":
          this.del(row, "恢复商品", index, name);
          break;
        case "3":
          this.del(row, "移入回收站", index, name);
          break;
        case "4":
          this.lookGoods(row.id);
          break;
        case "5":
          this.$modalForm(forcedRemovalApi(row.id)).then(() => {
            this.getDataList();
            this.goodHeade();
          });
          break;
        case "6":
          this.$router.push({
            path: "/admin/product/add_product",
            query: { copy: row.id },
          });
          break;
        case "7":
          this.delProduct(row, index);
          break;
        case "8":
          this.productId = row.id;
          this.showVipPrice = true;
          break;
        case "9":
          this.productId = row.id;
          this.showBrokerage = true;
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
          this.tableList.splice(num, 1);
          this.goodHeade();
          this.allReset();
        })
        .catch((res) => {
          this.$Message.error(res.msg);  
        });
    },
    // 数据导出；
    async exports(type) {
      if(!this.checkUidList.length && !this.isAll) return this.$Message.error('本页至少选中一项');
      let [th, filekey, data, fileName] = [[], [], [], ""];
      let excelData = {};
      excelData.ids = this.checkUidList.join();
      if (this.isAll == 1) {
        Object.assign(excelData, this.artFrom);
        excelData.all = 1;
      }
      let lebData = null;
      if(type){
        lebData = await this.getExportData(excelData);
      }else{
        lebData = await this.getExcelData(excelData);
      }
      if (!fileName) fileName = lebData.filename;
      filekey = lebData.filekey;
      if (!th.length) th = lebData.header; //表头
      data = data.concat(lebData.export);
      exportExcel(th, filekey, fileName, data);
    },
    getExcelData(excelData) {
      return new Promise((resolve, reject) => {
        storeProductApi(excelData).then((res) => {
          return resolve(res.data);
        });
      });
    },
    getExportData(excelData){
      return new Promise((resolve, reject) => {
        productExportApi(excelData).then((res) => {
          return resolve(res.data);
        });
      });
    },
    changeTemplate(e) {
      // this.template = e;
    },
    freight() {
      this.$refs.template.isTemplate = true;
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
          Object.assign(data, {where: this.artFrom});
        }
        productShowApi(data)
          .then((res) => {
            this.$Message.success(res.msg);
            this.goodHeade();
            this.getDataList();
            this.allReset();
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
          Object.assign(data, {where: this.artFrom});
        }
        productUnshowApi(data)
          .then((res) => {
            this.$Message.success(res.msg);
            this.goodHeade();
            this.getDataList();
            this.allReset();
          })
          .catch((res) => {
            this.$Message.error(res.msg);
          });
      }
    },
    // 添加淘宝商品成功
    onClose() {
      this.modals = false;
    },
    // 复制淘宝
    onCopy() {
      this.$router.push({
        path: "/admin/product/add_product",
        query: { type: -1 },
      });
      // this.modals = true;
    },
    // tab选择
    onClickTab(name) {
      this.allReset();
      this.artFrom.type = name;
      this.artFrom.page = 1;
      this.getDataList();
      this.goodHeade();
    },
    // 下拉树
    handleCheckChange(data) {
      let value = "";
      let title = "";
      this.list = [];
      this.artFrom.cate_id = 0;
      data.forEach((item, index) => {
        value += `${item.id},`;
        title += `${item.title},`;
      });
      value = value.substring(0, value.length - 1);
      title = title.substring(0, title.length - 1);
      this.list.push({
        value,
        title,
      });
      this.artFrom.cate_id = value;
      this.getDataList();
    },
    // 获取商品表单头数量
    goodHeade() {
      let tableForm = this.deepClone(this.artFrom);
      tableForm.sales_range = tableForm.sales_range.length ? tableForm.sales_range.join("-") : "";
      tableForm.price_range = tableForm.price_range.length ? tableForm.price_range.join("-") : "";
      tableForm.stock_range = tableForm.stock_range.length ? tableForm.stock_range.join("-") : "";
      tableForm.collect_range = tableForm.collect_range.length ? tableForm.collect_range.join("-") : "";
      getGoodHeade(tableForm)
        .then((res) => {
          this.headeNum = res.data.list;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 商品分类；
    goodsCategory() {
      cascaderListApi(1)
        .then((res) => {
          this.data1 = res.data;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    //深克隆
    deepClone(obj) {
        let newObj = Array.isArray(obj) ? [] : {}
        if (obj && typeof obj === "object") {
            for (let key in obj) {
                if (obj.hasOwnProperty(key)) {
                    newObj[key] = (obj && typeof obj[key] === 'object') ? this.deepClone(obj[key]) : obj[key];
                }
            }
        }
        return newObj
    },
    // 商品列表；
    getDataList() {
      this.loading = true;
      let tableForm = this.deepClone(this.artFrom);
      tableForm.sales_range = tableForm.sales_range ? tableForm.sales_range.join("-") : "";
      tableForm.price_range = tableForm.price_range ? tableForm.price_range.join("-") : "";
      tableForm.stock_range = tableForm.stock_range ? tableForm.stock_range.join("-") : "";
      tableForm.collect_range = tableForm.collect_range ? tableForm.collect_range.join("-") : "";
      // tableForm.create_range = this.timeVal.length ? this.timeVal.join("-") : "";
      getGoods(tableForm)
        .then((res) => {
          let data = res.data;
          this.tableList = data.list;
          this.total = data.count;
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
        .catch((res) => {
          this.loading = false;
          this.$Message.error(res.msg);
        });
    },
    pageChange(currentPage) {
      this.artFrom.page = currentPage;
      this.getDataList();
    },
    // 表格搜索
    userSearchs(e) {
      this.artFrom = e;
      this.formSelection = [];
      this.goodHeade();
      this.getDataList();
    },
    // 上下架
    changeSwitch(row) {
      PostgoodsIsShow(row.id, row.is_show)
        .then((res) => {
          this.$Message.success(res.msg);
          this.goodHeade();
          this.getDataList();
          this.allReset();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
          this.goodHeade();
          this.getDataList();
        });
    },
    // 数据导出；
    exportData: function() {
      let th = [
        "商品名称",
        "商品简介",
        "商品分类",
        "价格",
        "库存",
        "销量",
        "收藏人数",
      ];
      let filterVal = [
        "store_name",
        "store_info",
        "cate_name",
        "price",
        "stock",
        "sales",
        "collect",
      ];
      this.where.page = "nopage";
      getGoods(this.where).then((res) => {
        let data = res.data.map((v) => filterVal.map((k) => v[k]));
        let fileTime = Date.parse(new Date());
        let [fileName, fileType, sheetName] = [
          "商户数据_" + fileTime,
          "xlsx",
          "商户数据",
        ];
        toExcel({ th, data, fileName, fileType, sheetName });
      });
    },
    // 属性弹出；
    attrTap() {
      this.attrTemplate = true;
    },
    changeTemplate(msg) {
      this.attrTemplate = msg;
    },
    // 编辑
    edit(row) {
      this.$router.push({ path: "/admin/product/add_product/" + row.id });
    },
    // 确认
    del(row, tit, num, name) {
      let delfromData = {
        title: tit,
        num: num,
        url: `product/product/${row.id}`,
        method: "DELETE",
        ids: "",
        tips: `确定要移${name == 2 ? "出" : "入"}回收站吗？`,
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.tableList.splice(num, 1);
          this.goodHeade();
          this.allReset();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 删除成功
    // submitModel () {
    //     this.tableList.splice(this.delfromData.num, 1);
    //     this.goodHeade();
    // }
    // 商品详情
    details(id) {
      this.productId = id;
      this.detailsVisible = true;
    },
    openBatch() {
      this.isLabel = 0;
      this.batchModal = true;
    },
    menuSelect(name) {
      this.menuActive = name;
    },
    activeStoreData(storeDataLabel) {
      this.storeLabelShow = false;
      if (this.isLabel) {
        this.goodsDataLabel = storeDataLabel;
        // 商品标签id
        let storeActiveIds = [];
        storeDataLabel.forEach((item) => {
          storeActiveIds.push(item.id);
        });
        this.artFrom.store_label_id = storeActiveIds;
        this.userSearchs();
      } else {
        this.storeDataLabel = storeDataLabel;
      }
    },
    // 标签弹窗关闭
    storeLabelClose() {
      this.storeLabelShow = false;
    },
    openStoreLabel(row) {
      this.storeLabelShow = true;
      this.$refs.storeLabel.storeLabel(
        JSON.parse(JSON.stringify(this.storeDataLabel))
      );
    },
    
    tagClose(id) {
      if (this.menuActive == 2) {
        let index = this.storeDataLabel.findIndex((item) => item.id === id);
        this.storeDataLabel.splice(index, 1);
      } else {
        let index = this.dataLabel.findIndex((item) => item.id === id);
        this.dataLabel.splice(index, 1);
      }
    },
    activeData(dataLabel) {
      this.labelShow = false;
      this.dataLabel = dataLabel;
    },
    // 标签弹窗关闭
    labelClose() {
      this.labelShow = false;
    },
    openLabel() {
      this.labelShow = true;
      this.$refs.userLabel.userLabel(
        JSON.parse(JSON.stringify(this.dataLabel))
      );
    },
    // 添加优惠券
    addCoupon() {
      this.$refs.couponTemplates.isTemplate = true;
      this.$refs.couponTemplates.tableList();
    },
    nameId(id, names) {
      this.coupon_ids = id;
      this.couponName = this.unique(names);
    },
    handleClose(name) {
      let index = this.couponName.indexOf(name);
      this.couponName.splice(index, 1);
      let couponIds = this.coupon_ids;
      couponIds.splice(index, 1);
      this.updateIds = couponIds;
      this.updateName = this.couponName;
    },
    //对象数组去重；
    unique(arr) {
      const res = new Map();
      return arr.filter((arr) => !res.has(arr.id) && res.set(arr.id, 1));
    },
    // 添加表单
    addForm() {
      this.batchData.custom_form.push({
        key: Date.now(),
        title: "",
        label: "",
        status: 0,
      });
    },
    // 删除表单
    delForm(item) {
      let index = this.batchData.custom_form.findIndex((val) => val === item);
      if (index !== -1) {
        this.batchData.custom_form.splice(index, 1);
      }
    },
    cancelBatch() {
      this.batchModal = false;
      this.auditModal = false;
    },
    saveBatch() {
      if (this.customBtn && this.batchData.system_form_id == 0) {
        return this.$Message.warning("请选择自定义表单模板");
      }

      this.artFrom.store_label_id = [];
      let data = {
        type: this.menuActive,
        ids: this.checkUidList,
        all: this.isAll,
        where: this.artFrom,
        data: this.batchData,
      };
      batchProcess(data)
        .then((res) => {
          this.$Message.success(res.msg);
          this.batchModal = false;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 获取运费模板；
    productGetTemplate() {
      productGetTemplateApi().then((res) => {
        this.templateList = res.data;
      });
    },
    customMessBtn(e) {
      if (!e) {
        this.batchData.system_form_id = 0;
      }
    },
    batchVisibleChange() {
      this.batchData = {
        cate_id: [],
        store_label_id: [],
        delivery_type: [],
        freight: 1,
        postage: 0,
        temp_id: 0,
        give_integral: 0,
        coupon_ids: [],
        label_id: [],
        recommend: [],
        custom_form: [],
        system_form_id: 0,
      };
      this.storeDataLabel = [];
      this.couponName = [];
      this.dataLabel = [];
      this.menuActive = 1;
    },
    productTypeMenu() {
      this.addTypeShow = false;
      this.$router.push({
        path: "/admin/product/add_product",
        query: { productType: this.product_type },
      });
    },
    goodsMove(val){
      if(val === 1){
        this.importShow = true;
      }else{
        this.exports(1);
      }
    },
    productTypeTap(num, item) {
      this.product_type = item.id;
    },
  },
};
</script>
<style lang="less">
.vertical-center{
  display: flex;
  justify-content: center;
  align-items: center;
  .ivu-modal{
    top: 0;
  }
}
/deep/ .el-cascader .el-cascader__search-input {
  font-size: 12px !important;
}

/deep/ .ivu-dropdown-item {
  font-size: 12px !important;
}

/deep/ .vxe-table--render-default .vxe-cell {
  font-size: 12px;
}

.tdinfo {
  margin-left: 75px;
  margin-top: 16px;
}

.expand-row {
  margin-bottom: 16px;
  font-size: 12px;
}

/deep/ .ivu-checkbox-wrapper {
  font-size: 12px;
}

.labelClass {
  /deep/ .ivu-form-item-content {
    line-height: unset;
  }
}

.labelInput {
  position: relative;
  border: 1px solid #dcdee2;
  width: 250px;
  padding: 0 24px 0 8px;
  border-radius: 4px;
  min-height: 32px;
  cursor: pointer;

  .span {
    color: #c5c8ce;
  }

  .ivu-icon {
    position: absolute;
    top: 50%;
    right: 8px;
    line-height: 1;
    transform: translateY(-50%);
    font-size: 14px;
    color: #808695;
    transition: all 0.2s ease-in-out;
  }
}

.input-add {
  width: 250px;
  margin-right: 14px;
}

.style-add {
  margin-top: 10px;
  line-height: 1.2;
}

.line2 {
  max-height: 40px;
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

.deliveryStyle /deep/ .ivu-checkbox-wrapper {
  margin-right: 14px;
}

.Button /deep/ .ivu-upload {
  width: 105px;
  display: inline-block;
  margin-right: 10px;
}

/deep/ .ivu-modal-mask {
  z-index: 999 !important;
}

/deep/ .ivu-modal-wrap {
  z-index: 999 !important;
}

/deep/ .ivu-alert {
  margin-bottom: 20px;
}

.Box {
  /deep/ .ivu-modal-body {
    height: 700px;
    overflow: auto;
  }
}

.tabBox_img {
  width: 40px;
  height: 40px;
  border-radius: 4px;
  cursor: pointer;

  img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }
}

/deep/ .ivu-table-cell-expand-expanded {
  margin-top: -6px;
  margin-right: 33px;
  transition: none;

  .ivu-icon {
    vertical-align: 2px;
  }
}

/deep/ .ivu-table-header {
  // overflow visible
}

/deep/ .ivu-table th {
  overflow: visible;
}

/deep/ .select-item:hover {
  background-color: #f3f3f3;
}

/deep/ .select-on {
  display: block;
}

/deep/ .select-item.on {
  /* background: #f3f3f3; */
}

.new_tab {
  /deep/ .ivu-tabs-nav .ivu-tabs-tab {
    padding: 4px 16px 20px !important;
    font-weight: 500;
  }
}

.select-tag {
  position: relative;
  min-height: 32px;
  padding: 0 24px 0 4px;
  border: 1px solid #dcdee2;
  border-radius: 4px;
  line-height: normal;
  user-select: none;
  cursor: pointer;

  &:hover {
    border-color: #57a3f3;
  }

  .ivu-icon {
    position: absolute;
    top: 50%;
    right: 8px;
    line-height: 1;
    transform: translateY(-50%);
    font-size: 14px;
    color: #808695;
    transition: all .2s ease-in-out;
  }

  .ivu-tag {
    position: relative;
    max-width: 99%;
    height: 24px;
    margin: 3px 4px 3px 0;
    line-height: 22px;
  }

  .placeholder {
    display: block;
    height: 30px;
    line-height: 30px;
    color: #c5c8ce;
    font-size: 14px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding-left: 4px;
    padding-right: 22px;
  }
}

.input-number {
  position: relative;
  display: inline-block;
  vertical-align: middle;

  /deep/ .ivu-input-number-handler-wrap {
    right: 32px;
  }

  .ivu-input-number {
    width: 144px;
    margin-right: 32px;
  }

  .suffix {
    position: absolute;
    top: 0;
    right: 0;
    z-index: 1;
    width: 32px;
    height: 100%;
    text-align: center;
  }
}

// .ivu-checkbox-wrapper, .ivu-radio-wrapper {
//   margin-right: 30px;
// }

/deep/ .batch-modal {
  .ivu-modal-body {
    padding: 0;
  }

  .ivu-alert {
    margin: 12px 24px;
  }

  .ivu-col-span-5 {
    flex: none;
    width: 130px;
  }

  .ivu-col-span-19 {
    padding-right: 37px;
  }

  .ivu-input-number {
    width: 100%;
  }

  .ivu-menu-light.ivu-menu-vertical .ivu-menu-item-active:not(.ivu-menu-submenu) {
    z-index: auto;
  }

  .ivu-menu-light.ivu-menu-vertical .ivu-menu-item-active:not(.ivu-menu-submenu):after {
    right: auto;
    left: 0;
  }

  .el-cascader {
    width: 100%;
  }

  .ivu-btn-text {
    color: #2D8CF0;
  }

  .ivu-btn-text:focus {
    box-shadow: none;
  }

  .ivu-menu-item {
    padding-right: 0;
  }
}

/deep/ .el-cascader {
  &.el-cascader--small {
    vertical-align: bottom;
    line-height: 30px;
  }

  &.single {
    .el-input__inner {
      height: 32px !important;
    }
  }

  .el-input__inner {
    padding-left: 7px;
    font-size: 14px;
  }

  .el-cascader__search-input {
    margin-left: 9px;
    font-size: 14px;
  }

  .el-input__suffix {
    right: 4px;
  }

  .el-input__icon {
    color: #808695;
    font-weight: bold;
    font-size: 12px;
  }
}
.productType {
  width: 120px;
  height: 60px;
  background: #FFFFFF;
  border-radius: 3px;
  border: 1px solid #E7E7E7;
  float: left;
  text-align: center;
  padding-top: 8px;
  position: relative;
  cursor: pointer;
  line-height: 23px;

  &.on{
	  border-color: #1890FF;
  }

  .name {
    font-size: 14px;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.85);
  }

  .title {
    font-size: 12px;
    font-weight: 400;
    color: #999999;
  }

  .jiao {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 0;
    height: 0;
    border-bottom: 26px solid #1890FF;
    border-left: 26px solid transparent;
  }

  .iconfont {
    position: absolute;
    bottom: -3px;
    right: 1px;
    color: #FFFFFF;
	  font-size: 12px;
  }

}

</style>
