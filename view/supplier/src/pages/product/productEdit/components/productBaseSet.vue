<template>
  <div>
    <!-- 商品信息-->
    <FormItem label="商品名称：" required>
      <Input
        v-model="formValidate.store_name"
        placeholder="请输入商品名称"
        v-width="'50%'"
      />
    </FormItem>
    <FormItem label="商品分类：" required>
      <el-cascader
        placeholder="请选择商品分类"
        v-width="'50%'"
        size="mini"
        v-model="formValidate.cate_id"
        :options="treeSelect"
        :props="props"
        filterable
        clearable
      >
      </el-cascader>
      <span class="addClass" @click="addClass">新增分类</span>
    </FormItem>
    <FormItem label="商品品牌：">
      <div class="flex">
        <Cascader
          :data="brandData"
          placeholder="请选择商品品牌"
          change-on-select
          v-model="formValidate.brand_id"
          filterable
          v-width="'50%'"
        ></Cascader>
        <span class="addClass" @click="addBrand">新增品牌</span>
      </div>
    </FormItem>
    <FormItem label="单位：" required>
      <Select
        v-model="formValidate.unit_name"
        clearable
        filterable
        v-width="'50%'"
        placeholder="请输入单位"
        @on-change="unitChange"
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
    <FormItem label="商品编码：" prop="">
      <Input
        v-model="formValidate.code"
        placeholder="请输入商品编码"
        v-width="'50%'"
      />
    </FormItem>
    <FormItem label="商品轮播图：" required>
      <div class="acea-row">
        <div
          class="pictrue"
          v-for="(item, index) in formValidate.slider_image"
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
          v-if="formValidate.slider_image.length < 9"
          class="upLoad acea-row row-center-wrapper"
          @click="modalPicTap('duo')"
        >
          <Icon type="ios-camera-outline" size="26" />
        </div>
        <Input
          v-model="formValidate.slider_image[0]"
          class="input-display"
        ></Input>
      </div>
      <div class="tips">
        建议尺寸：800 *
        800px，可拖拽改变图片顺序，默认首张图为主图，最多上传10张
      </div>
    </FormItem>
    <FormItem label="商品标签：" class="labelClass">
      <div class="acea-row row-middle">
        <div
          class="labelInput acea-row row-between-wrapper"
          @click="openStoreLabel"
        >
          <div style="width: 90%;">
            <div v-if="formValidate.store_label_id && formValidate.store_label_id.length">
              <Tag
                closable
                v-for="(item, index) in formValidate.store_label_id"
                :key="index"
                @on-close="closeStoreLabel(item)"
                >{{ item.label_name }}</Tag
              >
            </div>
            <span class="span" v-else>选择商品标签</span>
          </div>
          <div class="iconfont iconxiayi"></div>
        </div>
        <span class="addClass" @click="addStoreLabel">新增标签</span>
      </div>
    </FormItem>
    <FormItem label="添加视频：">
      <i-switch v-model="formValidate.video_open" size="large">
        <span slot="open">开启</span>
        <span slot="close">关闭</span>
      </i-switch>
    </FormItem>
    <FormItem
      label="上传视频："
      prop="video_link"
      v-if="formValidate.video_open"
    >
      <div class="flex">
        <Input v-model="formValidate.video_link" v-width="'50%'" />
        <Upload
          :show-upload-list="false"
          :action="fileUrl2"
          :before-upload="videoSaveToUrl"
          :headers="header"
          :multiple="true"
          accept=".mp4"
        >
        <span class="addClass">上传视频</span>
        </Upload>
      </div>
      <div class="iview-video-style" v-if="formValidate.video_link">
        <video
          class="video-style"
          :src="formValidate.video_link"
          controls="controls"
        ></video>
        <div class="mark"></div>
        <Icon type="ios-trash-outline" class="iconv" @click="delVideo" />
      </div>
    </FormItem>

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
    <menus-from
      :formValidate="formBrand"
      :fromName="1"
      ref="menusFrom"
    ></menus-from>
  </div>
</template>
<script>
import {
  cascaderList,
  productCreateApi,
  brandList,
  productAllUnit,
  productUnitCreate,
  productLabelAdd,
} from "@/api/product";
import storeLabelList from "@/components/labelList";
import menusFrom from "../../components/menusFrom";
import vuedraggable from "vuedraggable";
import EventBus from '@/utils/bus';
import Setting from "@/setting";
import util from "@/libs/util";
import { uploadByPieces } from "@/utils/upload"; 
export default {
  name: "productBaseSet",
  props: {
    baseInfo: {
      type: Object,
      default: () => {},
    },
    successData:{
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      fileUrl2: Setting.apiBaseURL + "/file/video_upload",
      header:{
        'Authori-zation': "Bearer " + util.cookies.get("token")
      },
      upload: {
        videoIng: false, // 是否显示进度条；
      },
      progress:0,
      videoIng: false, // 是否显示进度条；
      formValidate: {
        id: 0,
        brand_id: [],
        code: "",
        slider_image: [],
        store_name: "",
        cate_id: [],
        store_label_id: [],
        unit_name: "",
        video_link: "",
        video_open: false,
      },
      props: { emitPath: false, multiple: true, checkStrictly: true },
      //商品分类树形数据
      treeSelect: [],
      // 品牌数据
      brandData: [],
      // 单位数据
      unitNameList: [],
      storeLabelShow: false,
      formBrand: {},
      ruleValidate: {
        store_name: [
          { required: true, message: "请输入商品名称", trigger: "blur" },
        ],
        cate_id: [
          {
            required: true,
            message: "请选择商品分类",
            trigger: "change",
            type: "array",
          },
        ],
        unit_name: [
          {
            required: true,
            message: "请输入单位",
            trigger: "change",
          },
        ],
        slider_image: [
          {
            required: true,
            message: "请上传商品轮播图",
            type: "array",
          },
        ],
      },
    };
  },
  components: {
    storeLabelList,
    menusFrom,
    draggable: vuedraggable,
  },
  computed: {
    startPickOptions() {
      const that = this;
      return {
        disabledDate(time) {
          if (that.formValidate.auto_off_time) {
            return (
              time.getTime() >
              new Date(that.formValidate.auto_off_time).getTime()
            );
          }
          return "";
        },
      };
    },
    endPickOptions() {
      const that = this;
      return {
        disabledDate(time) {
          if (that.formValidate.is_show == "1") {
            return time.getTime() < Date.now();
          }
          if (that.formValidate.auto_on_time) {
            return (
              time.getTime() <
              new Date(that.formValidate.auto_on_time).getTime()
            );
          }
          return "";
        },
      };
    },
  },
  watch: {
    successData: {
      handler(val) {
        if(val){
          let keys = Object.keys(this.formValidate);
          keys.map((i) => {
            this.formValidate[i] = this.baseInfo[i];
          });
        }
      },
      immediate: true,
      deep: true,
    }
  },
  created() {
    this.goodsCategory();
    this.getBrandList();
    this.getAllUnit();
  },
  methods: {
    videoSaveToUrl(file) {
      let imgTypeArr = ["video/mp4"];
      let imgType = imgTypeArr.indexOf(file.type) !== -1;
      if (!imgType) {
        return this.$Message.warning({
          content: "文件  " + file.name + "  格式不正确, 请选择格式正确的视频",
          duration: 5,
        });
      }
      uploadByPieces({
        randoms: "", // 随机数，这里作为给后端处理分片的标识 根据项目看情况 是否要加
        file: file, // 视频实体
        pieceSize: 3, // 分片大小
        success: (data) => {
          this.formValidate.video_link = data.file_path;
          this.progress = 100;
        },
        error: (e) => {
          this.$Message.error(e.msg);
        },
        uploading: (chunk, allChunk) => {
          this.videoIng = true;
          let st = Math.floor((chunk / allChunk) * 100);
          this.progress = st;
        },
      });
      return false;
    },
    // 品牌列表
    getBrandList() {
      brandList()
        .then((res) => {
          //initBran()函数作用iview中规定value必须是字符串，后台返回成了数字，用于处理这个，给了个递归；
          this.initBran(res.data);
          this.brandData = res.data;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    initBran(data) {
      data.map((item) => {
        item.value = item.value.toString();
        if (item.children && item.children.length) {
          this.initBran(item.children);
        }
      });
    },
    addBrand() {
      this.$refs.menusFrom.modals = true;
      this.$refs.menusFrom.titleFrom = "添加品牌分类";
      this.formBrand = {
        sort: 0,
        is_show: 1,
      };
      this.formBrand.fid = [0];
      this.$refs.menusFrom.type = 1;
    },
    // 商品分类；
    goodsCategory() {
      cascaderList(1)
        .then((res) => {
          this.treeSelect = res.data;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    addClass() {
      this.$modalForm(productCreateApi()).then(() => this.goodsCategory());
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
    addUnit() {
      this.$modalForm(productUnitCreate()).then(() => this.getAllUnit());
    },
    addStoreLabel() {
      this.$modalForm(productLabelAdd()).then(() => {});
    },
    activeStoreData(storeDataLabel) {
      this.storeLabelShow = false;
      this.formValidate.store_label_id = storeDataLabel;
    },
    openStoreLabel(row) {
      this.storeLabelShow = true;
      if(this.formValidate.store_label_id.length){
        this.$refs.storeLabel.storeLabel(
          JSON.parse(JSON.stringify(this.formValidate.store_label_id))
        );
      }else{
        this.$refs.storeLabel.storeLabel([]);
      }
      
    },
    // 标签弹窗关闭
    storeLabelClose() {
      this.storeLabelShow = false;
    },
    goodsOn(e) {
      if (e == 0 || e == 1) {
        this.formValidate.auto_on_time = "";
      }
    },
    goodsOff(e) {
      if (!e) {
        this.formValidate.auto_off_time = "";
      }
    },
    //定时上架
    onchangeShow(e) {
      this.formValidate.auto_on_time = e;
    },
    //定时下架
    onchangeOff(e) {
      this.formValidate.auto_off_time = e;
    },
    // 删除视频；
    delVideo() {
      this.$set(this.formValidate, "video_link", "");
    },
    // 移动
    handleDragStart(e, item) {
      this.dragging = item;
    },
    handleDragEnd(e, item) {
      this.dragging = null;
    },
    handleDragOver(e) {
      e.dataTransfer.dropEffect = "move";
    },
    handleDragEnter(e, item) {
      e.dataTransfer.effectAllowed = "move";
      if (item === this.dragging) {
        return;
      }
      const newItems = [...this.formValidate.slider_image];
      const src = newItems.indexOf(this.dragging);
      const dst = newItems.indexOf(item);
      newItems.splice(dst, 0, ...newItems.splice(src, 1));
      this.formValidate.slider_image = newItems;
    },
    handleRemove(i) {
      this.formValidate.slider_image.splice(i, 1);
    },
    modalPicTap() {
      this.$imgModal((e) => {
        e.forEach((item) => {
          this.formValidate.slider_image.push(item.att_dir);
          this.formValidate.slider_image = this.formValidate.slider_image.splice(0, 9);
        });
      });
    },
    addVideo() {
      this.$imgModal((e) => {
        let videoUrl = e[0].att_dir;
        if (videoUrl.includes("mp4")) {
          this.formValidate.video_link = videoUrl;
        } else {
          this.$Message.error("请选择正确的视频文件");
        }
      });
    },
    unitChange(val){
      EventBus.$emit('unitChanged', val);
    }
  },
};
</script>
<style scoped lang="less">
.addClass {
  color: #1890ff;
  margin-left: 14px;
  cursor: pointer;
}
.input-display {
  display: none;
}
.labelInput {
  border: 1px solid #dcdee2;
  width: 50%;
  padding: 0 5px;
  border-radius: 5px;
  min-height: 30px;
  cursor: pointer;
  .span {
    color: #c5c8ce;
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
.iview-video-style {
  width: 40%;
  height: 180px;
  border-radius: 10px;
  background-color: #707070;
  margin-top: 10px;
  position: relative;
  overflow: hidden;
}

.iview-video-style .iconv {
  color: #fff;
  line-height: 180px;
  width: 50px;
  height: 50px;
  display: inherit;
  font-size: 26px;
  position: absolute;
  top: -74px;
  left: 50%;
  margin-left: -25px;
  cursor: pointer;
}

.iview-video-style .mark {
  position: absolute;
  width: 100%;
  height: 30px;
  top: 0;
  background-color: rgba(0, 0, 0, 0.5);
  text-align: center;
}
.video-style {
  width: 100%;
  height: 100% !important;
  border-radius: 10px;
}
.tips {
  display: inline-bolck;
  font-size: 12px;
  font-weight: 400;
  color: #999999;
  margin-top: 6px;
}
</style>
