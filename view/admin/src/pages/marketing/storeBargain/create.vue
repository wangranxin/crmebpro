<template>
  <div class="form-submit">
    <div class="i-layout-page-header">
      <PageHeader class="product_tabs" hidden-breadcrumb>
        <div slot="title">
          <router-link :to="{ path: '/admin/marketing/store_bargain/index' }">
            <!-- <Button icon="ios-arrow-back" size="small"  class="mr20">返回</Button> -->
            <div class="font-sm after-line">
              <span class="iconfont iconfanhui"></span>
              <span class="pl10">返回</span>
            </div>
          </router-link>
          <span
            v-text="$route.params.id ? '编辑砍价商品' : '添加砍价商品'"
            class="mr20 ml16"
          ></span>
        </div>
      </PageHeader>
    </div>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <Row type="flex" class="mt30 acea-row row-middle row-center">
        <Col span="20">
          <Steps :current="current">
            <Step title="选择砍价商品"></Step>
            <Step title="填写基础信息"></Step>
            <Step title="修改商品详情"></Step>
            <Step title="修改砍价规则"></Step>
          </Steps>
        </Col>
        <Col span="23">
          <Col span="24">
            <div class="lines mt25"></div>
          </Col>
          <Form
            class="form mt30"
            ref="formValidate"
            :rules="ruleValidate"
            :model="formValidate"
            @on-validate="validate"
            :label-width="labelWidth"
            :label-position="labelPosition"
            @submit.native.prevent
          >
            <FormItem
              label="选择商品："
              prop="image_input"
              v-show="current === 0"
            >
              <div class="picBox" @click="changeGoods">
                <div class="pictrue" v-if="formValidate.image">
                  <img v-lazy="formValidate.image" />
                </div>
                <div class="upLoad acea-row row-center-wrapper" v-else>
                  <Icon type="ios-camera-outline" size="26" class="iconfonts" />
                </div>
              </div>
            </FormItem>
            <Row v-show="current === 1" type="flex">
              <Col span="24">
                <FormItem label="砍价活动名称：" prop="title" label-for="title">
                  <Input
                    placeholder="请输入砍价活动名称"
                    element-id="title"
                    v-model="formValidate.title"
                    class="perW40"
                  />
                </FormItem>
              </Col>
              <Col span="24">
                <FormItem label="砍价活动简介：" prop="info" label-for="info">
                  <Input
                    placeholder="请输入砍价活动简介"
                    type="textarea"
                    :rows="4"
                    element-id="info"
                    v-model="formValidate.info"
                    class="perW40"
                  />
                </FormItem>
              </Col>
              <Col span="24">
                <FormItem label="单位：" prop="unit_name" label-for="unit_name">
                  <Select
                    v-model="formValidate.unit_name"
                    clearable
                    placeholder="请输入单位"
                    class="perW40"
                  >
                    <Option
                      v-for="(item, index) in unitNameList"
                      :value="item.name"
                      :key="index"
                      >{{ item.name }}</Option
                    >
                  </Select>
                  <span class="addClass" @click="addUnit">新增单位</span>
                </FormItem>
              </Col>
              <Col span="24">
                <FormItem prop="images">
                  <div class="custom-label" slot="label">
                    <div>
                      <div>商品轮播图</div>
                    </div>
                    <div>：</div>
                  </div>
                  <div class="acea-row">
                    <div
                      class="pictrue"
                      v-for="(item, index) in formValidate.images"
                      :key="index"
                      draggable="true"
                      @dragstart="handleDragStart($event, item)"
                      @dragover.prevent="handleDragOver($event, item)"
                      @dragenter="handleDragEnter($event, item)"
                      @dragend="handleDragEnd($event, item)"
                    >
                      <img v-lazy="item" />
                      <Button
                        shape="circle"
                        icon="md-close"
                        @click.native="handleRemove(index)"
                        class="btndel"
                      ></Button>
                    </div>
                    <div
                      v-if="formValidate.images.length < 10"
                      class="upLoad acea-row row-center-wrapper"
                      @click="modalPicTap()"
                    >
                      <Icon
                        type="ios-camera-outline"
                        size="26"
                        class="iconfonts"
                      />
                    </div>
                  </div>
                  <div class="tips">
                    建议尺寸：800 *
                    800px，可拖拽改变图片顺序，默认首张图为主图，最多上传10张
                  </div>
                </FormItem>
              </Col>

              <!--<Col v-bind="grid2">-->
              <!--<FormItem label="砍价商品名称：" prop="store_name" label-for="store_name">-->
              <!--<Input placeholder="请输入砍价商品名称"  v-model="formValidate.store_name"/>-->
              <!--</FormItem>-->
              <!--</Col>-->
              <Col span="24">
                <div class="lines"></div>
              </Col>
              <Col span="24">
                <FormItem label="活动时间：" prop="section_time">
                  <div class="acea-row row-middle">
                    <DatePicker
                      :editable="false"
                      type="datetimerange"
                      format="yyyy-MM-dd HH:mm"
                      placeholder="请选择活动时间"
                      @on-change="onchangeTime"
                      class="perW40"
                      :value="formValidate.section_time"
                      v-model="formValidate.section_time"
                    ></DatePicker>
                  </div>
                  <div class="tips">
                    设置活动开启结束时间，用户可以在设置时间内发起参与砍价
                  </div>
                </FormItem>
              </Col>
              <Col span="24">
                <FormItem
                  label="砍价人数："
                  prop="people_num"
                  label-for="people_num"
                >
                  <div class="acea-row row-middle">
                    <InputNumber
                      placeholder="请输入砍价人数"
                      element-id="people_num"
                      :min="1"
                      :precision="0"
                      v-model="formValidate.people_num"
                      class="perW40"
                    />
                    <span class="ml10">人</span>
                  </div>
                  <div class="tips">需要多少人砍价成功</div>
                </FormItem>
              </Col>
              <Col span="24">
                <FormItem
                  label="帮砍次数："
                  prop="bargain_num"
                  label-for="bargain_num"
                >
                  <div class="acea-row row-middle">
                    <InputNumber
                      placeholder="请输入帮砍次数"
                      element-id="bargain_num"
                      :min="1"
                      :precision="0"
                      v-model="formValidate.bargain_num"
                      class="perW40"
                    />
                    <span class="ml10">次</span>
                  </div>
                  <div class="tips">
                    单个商品用户可以帮砍的次数，例：次数设置为1，甲和乙同时将商品A的砍价链接发给丙，丙只能帮甲或乙其中一个人砍价
                  </div>
                </FormItem>
              </Col>
              <Col span="24">
                <FormItem label="购买数量限制：" prop="num">
                  <div class="acea-row row-middle">
                    <InputNumber
                      placeholder="购买数量限制"
                      :precision="0"
                      :min="1"
                      v-model="formValidate.num"
                      class="perW40"
                    />
                  </div>
                  <div class="tips">单个活动每个用户发起砍价次数限制</div>
                </FormItem>
              </Col>
              <Col span="24">
                <FormItem label="规格选择：">
                  <Table :data="specsData" :columns="columns" border>
                    <template slot-scope="{ row, index }" slot="pic">
                      <div
                        class="acea-row row-middle row-center-wrapper"
                        @click="getPic(row)"
                      >
                        <div class="pictrue pictrueTab"  v-if="row.pic">
                          <img v-lazy="row.pic" />
                        </div>

                        <div
                          class="upLoad pictrueTab acea-row row-center-wrapper"
                          v-else
                        >
                          <Icon
                            type="ios-camera-outline"
                            size="21"
                            class="iconfonts"
                          />
                        </div>
                      </div>
                    </template>
                    <template slot-scope="{ row, index }" slot="price">
                      <InputNumber
                        v-model="specsData[index].price"
                        :min="0"
                        active-change
                        class="priceBox"
                      ></InputNumber>
                    </template>
                    <template slot-scope="{ row, index }" slot="min_price">
                      <InputNumber
                        v-model="specsData[index].min_price"
                        :min="0"
                        active-change
                        class="priceBox"
                      ></InputNumber>
                    </template>
                    <template slot-scope="{ row, index }" slot="quota">
                      <InputNumber
                        v-model="specsData[index].quota"
                        :min="1"
                        active-change
                        class="priceBox"
                      ></InputNumber>
                    </template>
                  </Table>
                </FormItem>
              </Col>
              <Col span="24">
                <div class="lines"></div>
              </Col>
              <Col v-if="formValidate.product_type == 0">
                <FormItem label="配送方式：">
                  <CheckboxGroup v-model="formValidate.delivery_type">
                    <Checkbox label="1">快递</Checkbox>
                    <!-- <Checkbox label="3">门店配送</Checkbox> -->
                    <Checkbox label="2">自提</Checkbox>
                  </CheckboxGroup>
                </FormItem>
              </Col>
              <Col span="24" v-if="formValidate.product_type == 0">
                <FormItem label="运费设置：">
                  <RadioGroup v-model="formValidate.freight">
                    <Radio :label="1">包邮</Radio>
                    <Radio :label="2">固定邮费</Radio>
                    <Radio :label="3">运费模板</Radio>
                  </RadioGroup>
                </FormItem>
              </Col>
              <Col
                span="24"
                v-if="
                  formValidate.freight == 2 && formValidate.product_type == 0
                "
              >
                <FormItem label="" prop="freight">
                  <div class="acea-row row-middle">
                    <InputNumber
                      :min="0"
                      v-model="formValidate.postage"
                      placeholder="请输入金额"
                      class="perW20 maxW"
                    />
                    <span class="ml10">元</span>
                  </div>
                </FormItem>
              </Col>
              <Col
                span="24"
                v-if="
                  formValidate.freight == 3 && formValidate.product_type == 0
                "
              >
                <FormItem label="" prop="">
                  <div class="acea-row">
                    <Select
                      v-model="formValidate.temp_id"
                      clearable
                      class="perW20 maxW"
                    >
                      <Option
                        v-for="(item, index) in templateList"
                        :value="item.id"
                        :key="index"
                        >{{ item.name }}</Option
                      >
                    </Select>
                    <Button @click="addTemp" class="ml15">添加运费模板</Button>
                  </div>
                </FormItem>
              </Col>
              <Col span="24">
                <FormItem label="排序：">
                  <InputNumber
                    placeholder="请输入排序"
                    element-id="sort"
                    :precision="0"
                    :min="0"
                    v-model="formValidate.sort"
                    class="perW20 maxW"
                  />
                </FormItem>
              </Col>
              <Col span="24">
                <FormItem label="活动状态：" props="status" label-for="status">
                  <!-- <RadioGroup element-id="status" v-model="formValidate.status">
                                <Radio :label="1" class="radio">开启</Radio>
                                <Radio :label="0">关闭</Radio>
                            </RadioGroup> -->
                  <i-switch
                    v-model="formValidate.status"
                    :true-value="1"
                    :false-value="0"
                    size="large"
                  >
                    <span slot="open">开启</span>
                    <span slot="close">关闭</span>
                  </i-switch>
                </FormItem>
              </Col>
              <Col span="24" v-if="formValidate.product_type">
                <FormItem label="支持退款：" props="status" label-for="status">
                  <i-switch
                    v-model="formValidate.is_support_refund"
                    :true-value="1"
                    :false-value="0"
                    size="large"
                  >
                    <span slot="open">开启</span>
                    <span slot="close">关闭</span>
                  </i-switch>
                </FormItem>
              </Col>
            </Row>
            <div v-if="current === 2">
              <FormItem label="内容：">
                <WangEditor
                  style="width: 90%"
                  :content="description"
                  @editorContent="getEditorContent"
                  :key="1"
                ></WangEditor>
              </FormItem>
            </div>
            <div v-if="current === 3">
              <FormItem label="规则：">
                <WangEditor
                  style="width: 90%"
                  :content="rule"
                  @editorContent="getEditorContent2"
                  :key="2"
                >
                </WangEditor>
              </FormItem>
            </div>
            <Spin size="large" fix v-if="spinShow"></Spin>
          </Form>
        </Col>
      </Row>
    </Card>
    <Card
      :bordered="false"
      dis-hover
      class="fixed-card"
      :style="{ left: `${!menuCollapse ? '200px' : isMobile ? '0' : '80px'}` }"
    >
      <Form>
        <FormItem>
          <Button
            class="submission mr15"
            @click="step"
            v-show="current !== 0"
            :disabled="
              $route.params.id && $route.params.id !== '0' && current === 1
            "
            >上一步</Button
          >
          <Button
            type="primary"
            :disabled="submitOpen && current === 3"
            class="submission"
            @click="next('formValidate')"
            v-text="current === 3 ? '提交' : '下一步'"
          ></Button>
        </FormItem>
      </Form>
    </Card>
    <!-- 选择商品-->
    <Modal
      v-model="modals"
      title="商品列表"
      footerHide
      class="paymentFooter"
      scrollable
      width="900"
      @on-cancel="cancel"
    >
      <goods-list
        ref="goodslist"
        :goodsType="1"
        @getProductId="getProductId"
      ></goods-list>
    </Modal>
   
    <freightTemplate
      :template="template"
      v-on:changeTemplate="changeTemplate"
      ref="templates"
    ></freightTemplate>
  </div>
</template>

<script>
import { mapState } from "vuex";
import goodsList from "@/components/goodsList/index";
import freightTemplate from "@/components/freightTemplate";
import {
  bargainInfoApi,
  bargainCreatApi,
  productAttrsApi,
} from "@/api/marketing";
import {
  productGetTemplateApi,
  productAllUnit,
  productUnitCreate,
} from "@/api/product";
import WangEditor from "@/components/wangEditor/index.vue";
import { Debounce } from "@/utils";
export default {
  name: "storeBargainCreate",
  components: { goodsList, WangEditor, freightTemplate },
  data() {
    return {
      template: false,
      submitOpen: false,
      spinShow: false,
      myConfig: {
        autoHeightEnabled: false, // 编辑器不自动被内容撑高
        initialFrameHeight: 500, // 初始容器高度
        initialFrameWidth: "100%", // 初始容器宽度
        UEDITOR_HOME_URL: "/admin/UEditor/",
        serverUrl: "",
      },
      isChoice: "",
      current: 0,
      modals: false,
      modal_loading: false,
      images: [],
      templateList: [],
      columns: [],
      specsData: [],
      unitNameList: [],
      formValidate: {
        is_support_refund: 0,
        product_type: 0,
        freight: 1, //运费设置
        delivery_type: [],
        images: [],
        info: "",
        title: "",
        store_name: "",
        image: "",
        unit_name: "",
        price: 0,
        min_price: 0,
        bargain_max_price: 10,
        bargain_min_price: 0.01,
        cost: 0,
        bargain_num: 1,
        people_num: 2,
        stock: 1,
        sales: 0,
        sort: 0,
        num: 1,
        give_integral: 0,
        postage: 0,
        is_postage: 0,
        is_hot: 0,
        status: 0,
        section_time: [],
        description: "",
        rule: "",
        id: 0,
        product_id: 0,
        temp_id: "",
        attrs: [],
        items: [],
      },
      rule: "",
      description: "",
      ruleValidate: {
        image: [{ required: true, message: "请选择主图", trigger: "change" }],
        images: [
          {
            required: true,
            type: "array",
            message: "请选择主图",
            trigger: "change",
          },
          {
            type: "array",
            min: 1,
            message: "Choose two hobbies at best",
            trigger: "change",
          },
        ],
        title: [
          { required: true, message: "请输入砍价活动名称", trigger: "blur" },
        ],
        info: [
          { required: true, message: "请输入砍价活动简介", trigger: "blur" },
        ],
        store_name: [
          { required: true, message: "请输入砍价商品名称", trigger: "blur" },
        ],
        section_time: [
          {
            required: true,
            type: "array",
            message: "请选择活动时间",
            trigger: "change",
          },
        ],
        unit_name: [
          {
            required: true,
            message: "请输入单位",
            trigger: "change",
          },
        ],
        price: [
          {
            required: true,
            type: "number",
            message: "请输入划线价",
            trigger: "blur",
          },
        ],
        min_price: [
          {
            required: true,
            type: "number",
            message: "请输入最低购买价",
            trigger: "blur",
          },
        ],
        // bargain_max_price: [
        //     { required: true, type: 'number', message: '请输单次砍价最大金额', trigger: 'blur' }
        // ],
        // bargain_min_price: [
        //     { required: true, type: 'number', message: '单次砍价最小金额', trigger: 'blur' }
        // ],
        cost: [
          {
            required: true,
            type: "number",
            message: "请输入成本价",
            trigger: "blur",
          },
        ],
        bargain_num: [
          {
            required: true,
            type: "number",
            message: "请输入帮砍次数",
            trigger: "blur",
          },
        ],
        people_num: [
          {
            required: true,
            type: "number",
            message: "请输入砍价人数",
            trigger: "blur",
          },
        ],
        stock: [
          {
            required: true,
            type: "number",
            message: "请输入库存",
            trigger: "blur",
          },
        ],
        num: [
          {
            required: true,
            type: "number",
            message: "请输入单次允许购买数量",
            trigger: "blur",
          },
        ],
      },
      currentid: "",
      picTit: "",
      tableIndex: 0,
      copy: 0,
    };
  },
  computed: {
    ...mapState("admin/layout", ["isMobile", "menuCollapse"]),
    labelWidth() {
      return this.isMobile ? undefined : 135;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
  },
  mounted() {
    if (this.$route.params.id !== "0" && this.$route.params.id) {
      this.copy = this.$route.params.copy;
      this.current = 1;
      this.getInfo();
    }
    this.productGetTemplate();
    this.getAllUnit();
  },
  methods: {
    changeTemplate(msg) {
      this.template = msg;
    },
    // 添加运费模板
    addTemp() {
      this.$refs.templates.isTemplate = true;
    },
    addUnit() {
      this.$modalForm(productUnitCreate()).then(() => this.getAllUnit());
    },
    getAllUnit() {
      productAllUnit()
        .then((res) => {
          this.unitNameList = res.data;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    getEditorContent(data) {
      this.formValidate.description = data;
    },
    getEditorContent2(data) {
      this.formValidate.rule = data;
    },
    // 砍价规格；
    productAttrs(row) {
      let that = this;
      productAttrsApi(row.id, 2)
        .then((res) => {
          let data = res.data.info;
          let radio = {
            title: "选择",
            key: "chose",
            width: 60,
            align: "center",
            render: (h, params) => {
              let uid = params.index;
              let flag = false;
              if (this.currentid === uid) {
                flag = true;
              } else {
                flag = false;
              }
              let self = this;
              return h("div", [
                h("Radio", {
                  props: {
                    value: flag,
                  },
                  on: {
                    "on-change": () => {
                      self.currentid = uid;
                      let attrs = [];
                      attrs.push(params.row);
                      self.formValidate.attrs = attrs;
                    },
                  },
                }),
              ]);
            },
          };
          that.columns = data.header;
          that.columns.unshift(radio);
          that.specsData = data.attrs;
          that.formValidate.items = data.items;
        })
        .catch((res) => {
          that.$Message.error(res.msg);
        });
    },
    // 获取运费模板；
    productGetTemplate() {
      productGetTemplateApi().then((res) => {
        this.templateList = res.data;
      });
    },
    // 商品id
    getProductId(row) {
      this.modal_loading = false;
      this.modals = false;
      setTimeout(() => {
        this.formValidate = {
          is_support_refund: row.is_support_refund,
          product_type: row.product_type,
          images: row.slider_image,
          info: row.store_info,
          title: row.store_name,
          store_name: row.store_name,
          image: row.image,
          unit_name: row.unit_name,
          price: 0, // 不取商品中的原价
          min_price: 0,
          bargain_max_price: 10,
          bargain_min_price: 0.01,
          cost: row.cost,
          bargain_num: 1,
          people_num: 2,
          stock: row.stock,
          sales: row.sales,
          sort: row.sort,
          num: 1,
          give_integral: row.give_integral,
          postage: row.postage,
          is_postage: row.is_postage,
          is_hot: row.is_hot,
          status: 0,
          section_time: [],
          description: row.description, // 不取商品中的
          rule: "",
          id: 0,
          product_id: row.id,
          temp_id: row.temp_id,
          freight: 1, //运费设置
          delivery_type: [],
        };
        this.description = row.description;
        this.rule = row.rule;
        this.productAttrs(row);
      }, 500);
    },
    cancel() {
      this.modals = false;
    },
    // 移动
    handleDragStart(e, item) {
      this.dragging = item;
    },
    handleDragEnd(e, item) {
      this.dragging = null;
    },
    // 首先把div变成可以放置的元素，即重写dragenter/dragover
    handleDragOver(e) {
      e.dataTransfer.dropEffect = "move";
    },
    handleDragEnter(e, item) {
      e.dataTransfer.effectAllowed = "move";
      if (item === this.dragging) {
        return;
      }
      const newItems = [...this.formValidate.images];
      const src = newItems.indexOf(this.dragging);
      const dst = newItems.indexOf(item);
      newItems.splice(dst, 0, ...newItems.splice(src, 1));
      this.formValidate.images = newItems;
    },
    // 具体日期
    onchangeTime(e) {
      this.formValidate.section_time = e;
    },
    // 详情
    getInfo() {
      this.spinShow = true;
      bargainInfoApi(this.$route.params.id)
        .then(async (res) => {
          let that = this;
          let info = res.data.info;
          this.formValidate = info;
          this.description = info.description;
          this.rule = info.rule;
          this.formValidate.rule = info.rule === null ? "" : info.rule;
          this.$set(this.formValidate, "items", info.attrs.items);
          this.columns = info.attrs.header;
          let radio = {
            title: "选择",
            key: "chose",
            width: 60,
            align: "center",
            render: (h, params) => {
              let uid = params.index;
              let flag = false;
              if (this.currentid === uid) {
                flag = true;
              } else {
                flag = false;
              }
              let self = this;
              return h("div", [
                h("Radio", {
                  props: {
                    value: flag,
                  },
                  on: {
                    "on-change": () => {
                      self.currentid = uid;
                      let attrs = [];
                      attrs.push(params.row);
                      self.formValidate.attrs = attrs;
                    },
                  },
                }),
              ]);
            },
          };
          that.columns.unshift(radio);
          this.specsData = info.attrs.value;
          let defaultAttrs = [];
          info.attrs.value.forEach(function(item, index) {
            if (item.opt) {
              defaultAttrs.push(item);
              that.$set(that, "currentid", index);
              that.$set(that.formValidate, "attrs", defaultAttrs);
            }
          });
          this.spinShow = false;
        })
        .catch((res) => {
          this.spinShow = false;
          this.$Message.error(res.msg);
        });
    },
    // 下一步
    next: Debounce(function(name) {
      if (this.current === 3) {
        this.rule = this.formValidate.rule;
        this.$refs[name].validate((valid) => {
          if (valid) {
            if (this.currentid === "") {
              return this.$Message.error("请选择属性规格");
            } else {
              let val = this.specsData[this.currentid];
              let formValidate = this.formValidate.attrs[0];
              formValidate.price = val.price;
              formValidate.min_price = val.min_price;
              formValidate.quota = val.quota;
              if (this.formValidate.attrs[0].quota <= 0) {
                return this.$Message.error("砍价限量必须大于0");
              }
            }
            if (this.copy == 1) this.formValidate.copy = 1;
            this.formValidate.id = this.$route.params.id || 0;
            this.formValidate.image = this.formValidate.images[0];
            this.submitOpen = true;
            if (!this.formValidate.product_type) {
              this.formValidate.is_support_refund = 1;
            }
            bargainCreatApi(this.formValidate).then(async (res) => {
                this.submitOpen = false;
                this.$Message.success(res.msg);
                setTimeout(() => {
                  this.$router.push({
                    path: "/admin/marketing/store_bargain/index",
                  });
                }, 500);
              })
              .catch((res) => {
                this.submitOpen = false;
                this.$Message.error(res.msg);
              });
          } else {
            return false;
          }
        });
      } else if (this.current === 2) {
        this.description = this.formValidate.description;
        this.rule = this.formValidate.rule;
        this.current += 1;
      } else if (this.current === 1) {
        this.$refs[name].validate((valid) => {
          if (valid) {
            if (this.formValidate.people_num == 1) {
              return this.$Message.error("砍价人数必须大于1");
            }
            if (
              this.formValidate.product_type == 0 &&
              !this.formValidate.delivery_type.length
            ) {
              return this.$Message.warning("请选择配送方式");
            }
            if (
              this.formValidate.product_type == 0 &&
              this.formValidate.freight == 2 &&
              this.formValidate.postage <= 0
            ) {
              return this.$Message.warning("物流设置-固定邮费不能为0");
            }
            if (
              this.formValidate.product_type == 0 &&
              this.formValidate.freight == 3 &&
              !this.formValidate.temp_id
            ) {
              return this.$Message.warning("物流设置-运费模板不能为空");
            }
            this.current += 1;
          } else {
            return this.$Message.warning("请完善商品信息");
          }
        });
      } else {
        if (this.formValidate.image) {
          this.current += 1;
        } else {
          this.$Message.warning("请选择商品");
        }
      }
    }),
    // 上一步
    step() {
      this.current--;
    },
    // 内容
    getContent(val) {
      this.formValidate.description = val;
    },
    // 规则
    getRole(val) {
      this.formValidate.rule = val;
    },
    // 点击商品图
    modalPicTap() {
      this.$imgModal((e) => {
        e.forEach((item) => {
          this.formValidate.images.push(item.att_dir);
          this.formValidate.images = this.formValidate.images.splice(0, 9);
        });
      });
    },
    // 获取单张图片信息
    getPic(row) {
      this.$imgModal((e) => {
        let imgUrl = e[0].att_dir;
        if (imgUrl.includes("mp4")) {
          this.$Message.error("请选择正确的图片文件");
        } else {
          row.pic = imgUrl;
        }
      });
    },
    // 获取多张图信息
    getPicD(pc) {
      this.images = pc;
      this.images.map((item) => {
        this.formValidate.images.push(item.att_dir);
        this.formValidate.images = this.formValidate.images.splice(0, 10);
      });
      this.modalPic = false;
    },
    handleRemove(i) {
      this.images.splice(i, 1);
      this.formValidate.images.splice(i, 1);
    },
    // 选择商品
    changeGoods() {
      this.modals = true;
      this.$refs.goodslist.getList();
      this.$refs.goodslist.goodsCategory();
    },
    // 表单验证
    validate(prop, status, error) {
      if (status === false) {
        this.$Message.error(error);
      }
    },
    // 添加自定义弹窗
    addCustomDialog(editorId) {
      window.UE.registerUI(
        "test-dialog",
        function(editor, uiName) {
          // 创建 dialog
          let dialog = new window.UE.ui.Dialog({
            // 指定弹出层中页面的路径，这里只能支持页面，路径参考常见问题 2
            iframeUrl: "/admin/widget.images/index.html?fodder=dialog",
            // 需要指定当前的编辑器实例
            editor: editor,
            // 指定 dialog 的名字
            name: uiName,
            // dialog 的标题
            title: "上传图片",
            // 指定 dialog 的外围样式
            cssRules: "width:1200px;height:500px;padding:20px;",
          });
          this.dialog = dialog;
          var btn = new window.UE.ui.Button({
            name: "dialog-button",
            title: "上传图片",
            cssRules: `background-image: url(../../../assets/images/icons.png);background-position: -726px -77px;`,
            onclick: function() {
              // 渲染dialog
              dialog.render();
              dialog.open();
            },
          });
          return btn;
        },
        37
      );
    },
  },
};
</script>

<style scoped lang="stylus">
    .custom-label
        display inline-flex
        line-height 1.5
    .grey
        color #999;
    .maxW /deep/.ivu-select-dropdown{
        max-width 600px;
    }
    .ivu-table-wrapper
        border-left: 1px solid #dcdee2;
        border-top: 1px solid #dcdee2;
    .tabBox_img
        width 50px;
        height 50px;
    .tabBox_img img
        width 100%;
        height 100%;
    .priceBox
        width 100%
    .form
        .picBox
            display: inline-block;
            cursor: pointer;
        .pictrue
            width:60px;
            height:60px;
            border:1px dotted rgba(0,0,0,0.1);
            margin-right:15px;
            display: inline-block;
            position: relative;
            cursor: pointer;
            img
              width 100%
              height 100%
            .btndel
                position: absolute;
                z-index: 9;
                width :20px !important;
                height: 20px !important;
                left: 46px;
                top: -4px;
        .upLoad {
            width: 58px;
            height: 58px;
            line-height: 58px;
            border: 1px dotted rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.02);
            cursor: pointer;
        }
.lines {
	  border-bottom: 1px dashed #eee;
	  margin-bottom: 20px;
	}
.tips {
	  display: inline-bolck;
	  font-size: 12px;
	  font-weight: 400;
	  color: #999999;
	  margin-top: 10px;
	  line-height: initial;
	}
.ivu-mt {
  min-height: 500px;
}
.addClass{
		color: #1890FF;
		margin-left 14px;
		padding 9px 0;
		cursor pointer;
	}

.form-submit {
	  /deep/.ivu-card{
	  	border-radius: 0;
	  }
    margin-bottom: 79px;

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
}
</style>
