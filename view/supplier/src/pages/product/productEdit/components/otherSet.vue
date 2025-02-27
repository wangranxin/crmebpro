<template>
  <div>
    <FormItem label="配送方式：" prop="" required>
      <CheckboxGroup v-model="formValidate.delivery_type">
        <Checkbox label="1">快递</Checkbox>
      </CheckboxGroup>
    </FormItem>
    <FormItem label="运费设置：">
      <RadioGroup v-model="formValidate.freight">
        <Radio :label="1">包邮</Radio>
        <Radio :label="2">固定邮费</Radio>
        <Radio :label="3">运费模板</Radio>
      </RadioGroup>
    </FormItem>
    <FormItem label="邮费金额：" prop="freight" v-if="formValidate.freight == 2">
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
    <FormItem label="运费模板：" v-if="formValidate.freight == 3">
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
        <Button
          @click="editTemp"
          class="ml15"
          v-if="formValidate.temp_id"
          >查看运费模板</Button
        >
        <Button @click="addTemp" class="ml15" v-else
          >添加运费模板</Button
        >
      </div>
    </FormItem>
    <FormItem label="已售数量：">
      <InputNumber
        v-width="'50%'"
        :min="0"
        :max="999999"
        v-model="formValidate.ficti"
        placeholder="请输入已售数量"
      />
    </FormItem>
    <FormItem label="是否限购：">
      <i-switch
        v-model="formValidate.is_limit"
        :true-value="1"
        :false-value="0"
        size="large"
        @on-change="limitTap"
      >
        <span slot="open">开启</span>
        <span slot="close">关闭</span>
      </i-switch>
    </FormItem>
    <div v-if="formValidate.is_limit">
      <FormItem label="限购类型：">
        <RadioGroup v-model="formValidate.limit_type">
          <Radio :label="1">单次限购</Radio>
          <Radio :label="2">长期限购</Radio>
        </RadioGroup>
        <div class="tips">
          单次限购是限制每次下单最多购买的数量，长期限购是限制一个用户总共可以购买的数量
        </div>
      </FormItem>
      <FormItem label="限购数量：">
        <InputNumber
          :min="1"
          v-model="formValidate.limit_num"
          placeholder="请输入限购数量"
          class="perW20 maxW"
        />
      </FormItem>
    </div>
    <FormItem label="排序：">
      <InputNumber
        :min="0"
        :max="999999"
        v-width="'50%'"
        v-model="formValidate.sort"
        placeholder="请输入排序"
      />
    </FormItem>
    <FormItem label="商品关键字：">
      <Input
        v-model="formValidate.keyword"
        placeholder="请输入商品关键字"
        v-width="'50%'"
      />
      <div class="tips">通过命中关键字搜索对应商品，方便用户查找</div>
    </FormItem>
    <FormItem label="商品简介：" prop="">
      <Input
        v-model="formValidate.store_info"
        type="textarea"
        :rows="3"
        placeholder="请输入商品简介"
        v-width="'50%'"
      />
      <div class="tips">
        微信分享商品时，分享的卡片上会显示商品简介
        <Poptip
          placement="bottom"
          trigger="hover"
          width="256"
          transfer
          padding="8px"
        >
          <a>查看示例</a>
          <div class="exampleImg" slot="content">
            <img :src="`${baseURL}/statics/system/productDesc.png`" alt="" />
          </div>
        </Poptip>
      </div>
    </FormItem>
    <FormItem label="分销文案：" prop="">
      <Input
        v-model="formValidate.share_content"
        type="textarea"
        :rows="3"
        placeholder="请输入分销文案"
        v-width="'50%'"
      />
    </FormItem>
    <FormItem label="商品口令：">
      <Input
        v-model="formValidate.command_word"
        type="textarea"
        :rows="3"
        placeholder="请输入商品口令"
        v-width="'50%'"
      />
      <div class="tips">
        可将淘宝、抖音等平台的口令复制在此处，用户点击商城商品详情后会自动复制口令，随后打开淘宝等平台会自动弹出口令弹窗
        <Poptip
          placement="bottom"
          trigger="hover"
          width="256"
          transfer
          padding="8px"
        >
          <a>查看示例</a>
          <div class="exampleImg" slot="content">
            <div
              style="margin-bottom: 10px;font-size: 12px;line-height: 18px;color: #666666;"
            >
              商品口令需在淘宝、天猫、京东、苏<br />宁、1688上有同款商品，复制口令后<br />可在相关应用中打开(下图为淘宝展示)
            </div>
            <img
              :src="`${baseURL}/statics/system/productCommandWord.png`"
              alt=""
            />
          </div>
        </Poptip>
      </div>
    </FormItem>
    <FormItem label="商品推荐图：">
      <div class="acea-row">
        <div v-if="formValidate.recommend_image" class="pictrue">
          <img v-lazy="formValidate.recommend_image" />
          <Button
            shape="circle"
            icon="md-close"
            @click.native="formValidate.recommend_image = ''"
            class="btndel"
          ></Button>
        </div>
        <div
          v-else
          class="upLoad acea-row row-center-wrapper"
          @click="modalPicTap()"
        >
          <Icon type="ios-camera-outline" size="26" />
        </div>
      </div>
      <div class="tips">
        在特殊的商品分类样式中显示(建议图片比例5:2)
        <Poptip
          placement="bottom"
          trigger="hover"
          width="256"
          transfer
          padding="8px"
        >
          <a>查看示例</a>
          <div class="exampleImg" slot="content">
            <img
              :src="`${baseURL}/statics/system/productRecommendImage.png`"
              alt=""
            />
          </div>
        </Poptip>
      </div>
    </FormItem>
    <FormItem label="商品参数：" prop="">
      <Select
        v-model="formValidate.specs_id"
        clearable
        filterable
        v-width="'50%'"
        placeholder="请输入商品参数"
        @on-change="specsInfo"
      >
        <Option
          v-for="(item, index) in specsData"
          :value="item.id"
          :key="index"
          >{{ item.name }}</Option
        >
      </Select>
    </FormItem>
    <FormItem v-if="formValidate.specs_id">
      <Table
        border
        :columns="specsColumns"
        :data="formValidate.specs"
        ref="table"
        class="specsList"
        width="700"
      >
        <template slot-scope="{ row, index }" slot="action">
          <a @click="delSpecs(index)" v-if="index > 0">删除</a>
        </template>
      </Table>
      <Button class="mt20" @click="addSpecs">添加参数</Button>
    </FormItem>
    <FormItem label="自定义留言：">
      <i-switch v-model="customBtn" @on-change="customMessBtn" size="large">
        <span slot="open">开启</span>
        <span slot="close">关闭</span>
      </i-switch>
      <router-link class="addClass" :to="{ path: '/admin/setting/system_form' }"
        >新增表单</router-link
      >
      <div class="mt10" v-if="customBtn">
        <Select
          v-model="formValidate.system_form_id"
          filterable
          v-width="'50%'"
          placeholder="请选择"
          @on-change="changeForm"
        >
          <Option
            v-for="(item, index) in formList"
            :value="item.id"
            :key="index"
            >{{ item.name }}</Option
          >
        </Select>
      </div>
    </FormItem>
    <FormItem v-if="customBtn && formValidate.system_form_id">
      <Table
        border
        :columns="formColumns"
        :data="formTypeList"
        ref="table"
        class="specsList on"
      >
        <template slot-scope="{ row }" slot="require">
          <span>{{ row.require ? "必填" : "不必填" }}</span>
        </template>
      </Table>
    </FormItem>
  </div>
</template>
<script>
import Setting from "@/setting";
import {
  productAllSpecs,
  allSystemForm,
  productGetTemplateApi
} from "@/api/product";
import { systemFormInfo } from "@/api/setting";
export default {
  name: "otherSet",
  props: {
    baseInfo: {
      type: Object,
      default: () => {},
    },
    successData:{
      type: Boolean,
      default: false
    },
    product_type:{
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      baseURL: Setting.apiBaseURL.replace(/adminapi/, ""),
      formValidate: {
        delivery_type:["1"],
        freight: 1, //运费设置
        postage: 0, //设置运费金额
        temp_id:"",
        keyword: "",
        store_info: "",
        command_word: "",
        recommend_image: "",
        specs_id: "",
        is_support_refund: 0,
        system_form_id: "",
        specs:[],
        share_content: "",
        ficti: 0,
        is_limit: 0,
        limit_type: 1,
        limit_num: 0,
        sort:0
        
      },
      templateList: [],
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
      specsData: [],
      customBtn: false,
      specsColumns: [
        {
          title: "参数名称",
          key: "name",
          align: "center",
          width: 150,
          render: (h, params) => {
            return h("div", [
              h("Input", {
                props: {
                  value: params.row.name,
                  placeholder: "请输入参数名称",
                },
                on: {
                  "on-change": (e) => {
                    params.row.name = e.target.value;
                    this.specs[params.index].name = e.target.value;
                  },
                },
              }),
            ]);
          },
        },
        {
          title: "参数值",
          key: "value",
          align: "center",
          width: 300,
          render: (h, params) => {
            return h("div", [
              h("Input", {
                props: {
                  value: params.row.value,
                  placeholder: "请输入参数值",
                },
                on: {
                  "on-change": (e) => {
                    params.row.value = e.target.value;
                    this.specs[params.index].value = e.target.value;
                  },
                },
              }),
            ]);
          },
        },
        {
          title: "排序",
          key: "sort",
          align: "center",
          width: 100,
          render: (h, params) => {
            return h("div", [
              h("InputNumber", {
                props: {
                  value: parseInt(params.row.sort) || 0,
                  placeholder: "排序",
                  precision: 0,
                },
                on: {
                  "on-change": (e) => {
                    params.row.sort = e;
                    this.specs[params.index].sort = e;
                  },
                },
              }),
            ]);
          },
        },
        {
          title: "操作",
          slot: "action",
          align: "center",
          minWidth: 120,
        },
      ],
    };
  },
  watch: {
    successData: {
      handler(val) {
        if(val){
          let keys = Object.keys(this.formValidate);
          keys.map((i) => {
            this.formValidate[i] = this.baseInfo[i];
            if (this.formValidate.system_form_id) {
              this.customBtn = true;
              this.changeForm(this.formValidate.system_form_id);
            }
          });
        }
      },
      immediate: true,
      deep: true,
    }
  },
  created() {
    this.getProductAllSpecs();
    this.productGetTemplate();
    this.allFormList();
  },
  methods: {
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
    specsInfo(e) {
      this.specsData.forEach((item) => {
        if (item.id == e) {
          this.formValidate.specs = item.specs;
        }
      });
    },
    delSpecs(index) {
      this.formValidate.specs.splice(index, 1);
    },
    addSpecs() {
      let obj = { name: "", value: "", sort: 0 };
      this.formValidate.specs.push(obj);
    },
    customMessBtn(e) {
      if (!e) {
        this.formValidate.system_form_id = 0;
      }
    },
    getProductAllSpecs() {
      productAllSpecs()
        .then((res) => {
          this.specsData = res.data;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    modalPicTap() {
      this.$imgModal((e) => {
        let imgUrl = e[0].att_dir;
        if (imgUrl.includes("mp4")) {
          this.$Message.error("请选择正确的图片文件");
        } else {
          this.formValidate.recommend_image = imgUrl;
        }
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
    // 获取运费模板；
    productGetTemplate() {
      // productGetTemplateApi({ id: this.formData.id }).then((res) => {
      //   this.templateList = res.data;
      // });
    },
    // 添加运费模板
    addTemp() {
      this.$refs.templates.isTemplate = true;
    },
    //查看、编辑运费模板
    editTemp() {
      this.$refs.templates.isTemplate = true;
      this.$refs.templates.editFrom(this.formData.temp_id);
    },
    changeTemplate(msg) {
      this.template = msg;
    },
    limitTap(e) {
      if (e) {
        this.formValidate.limit_type =
          this.formValidate.is_limit && !this.formValidate.limit_type ? 1 : 0;
        this.formValidate.limit_num =
          this.formValidate.is_limit && this.formValidate.limit_num == 0
            ? 1
            : 0;
      } else {
        this.formValidate.limit_type = 0;
        this.formValidate.limit_num = 0;
      }
    },
  },
};
</script>
<style lang="less" scoped>
.tips {
  display: inline-bolck;
  font-size: 12px;
  font-weight: 400;
  color: #999999;
  margin-top: 6px;
}
.checkAlls /deep/.ivu-checkbox-inner {
  width: 14px;
  height: 14px;
}
.checkAlls /deep/.ivu-checkbox-wrapper {
  font-size: 12px;
}
.addClass {
  color: #1890ff;
  margin-left: 14px;
  cursor: pointer;
}
</style>
