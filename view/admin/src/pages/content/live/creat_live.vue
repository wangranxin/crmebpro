<template>
  <!-- 添加直播间 -->
  <div class="form-submit">
    <PageHeader class="product_tabs" hidden-breadcrumb>
      <div slot="title">
        <router-link :to="{ path: '/admin/content/live/live_room' }">
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
        <Col span="24">
          <Alert
            type="warning"
            show-icon
            style="width: 590px;margin-left: 17px;margin-bottom: 25px;"
            >必须前往微信小程序官方后台开通直播权限，关注<span
              style="color: red;cursor: pointer;"
              @click="codeImg"
              >【小程序直播】</span
            >须知直播状态</Alert
          >
        </Col>
        <Col span="24">
          <FormItem label="选择主播：" required>
            <Select
              v-model="formValidate.anchor_wechat"
              filterable
              clearable
              class="width30"
              @on-change="anchorName"
            >
              <Option
                v-for="(item, index) in liveList"
                :value="item.wechat"
                :key="index"
                >{{ item.wechat }}</Option
              >
            </Select>
          </FormItem>
        </Col>
        <Row :gutter="24" type="flex">
          <Col span="24">
            <FormItem label="直播间名称：" required>
              <Input
                enter-button
                placeholder="请输入直播间名称"
                element-id="name"
                v-model="formValidate.name"
                class="width30"
              />
            </FormItem>
          </Col>
          <Col span="24">
            <div style="display: flex">
              <FormItem label="背景图：" required>
                <div @click="modalPicTap(0)" class="box">
                  <img
                    :src="formValidate.cover_img"
                    alt=""
                    v-if="formValidate.cover_img"
                  />
                  <div class="upload-box" v-else>
                    <Icon type="ios-camera-outline" size="36" />
                  </div>
                </div>
              </FormItem>
              <span style="margin-left: 20px;color:#b0bac5;"
                >尺寸：1080*1920px</span
              >
            </div>
          </Col>
          <Col span="24">
            <div style="display: flex">
              <FormItem label="分享图：" required>
                <div @click="modalPicTap(1)" class="box">
                  <img
                    :src="formValidate.share_img"
                    alt=""
                    v-if="formValidate.share_img"
                  />
                  <div class="upload-box" v-else>
                    <Icon type="ios-camera-outline" size="36" />
                  </div>
                </div>
              </FormItem>
              <span style="margin-left: 20px;color:#b0bac5;"
                >尺寸：800*640px</span
              >
            </div>
          </Col>
          <Col span="24">
            <FormItem label="联系电话：">
              <Input
                enter-button
                placeholder="请输入主播联系电话"
                element-id="phone"
                v-model="formValidate.phone"
                class="width30"
              />
            </FormItem>
          </Col>
          <Col span="24">
            <FormItem label="直播时间：">
              <DatePicker
                type="datetimerange"
                format="yyyy-MM-dd HH:mm:ss"
                placeholder="请选择直播时间"
                class="width30"
                :value="timeVal"
                @on-change="selectDate"
              ></DatePicker>
            </FormItem>
          </Col>
          <Col span="24">
            <FormItem label="排序：">
              <Input
                type="number"
                enter-button
                placeholder="0"
                element-id="phone"
                v-model="formValidate.sort"
                class="width30"
              />
            </FormItem>
          </Col>
          <Col span="24">
            <FormItem label="显示样式：">
              <RadioGroup v-model="formValidate.screen_type">
                <Radio
                  :label="item.label"
                  v-for="(item, index) in screen_type"
                  :key="index"
                >
                  <span>{{ item.value }}</span>
                </Radio>
              </RadioGroup>
            </FormItem>
          </Col>
          <Col span="24">
            <FormItem label="直播间类型：">
              <RadioGroup v-model="formValidate.type">
                <Radio
                  :label="item.label"
                  v-for="(item, index) in type"
                  :key="index"
                >
                  <span>{{ item.value }}</span>
                </Radio>
              </RadioGroup>
            </FormItem>
          </Col>
          <Col span="24">
            <FormItem label="直播间点赞：">
              <RadioGroup v-model="formValidate.close_like">
                <Radio
                  :label="item.label"
                  v-for="(item, index) in close_like"
                  :key="index"
                >
                  <span>{{ item.value }}</span>
                </Radio>
              </RadioGroup>
            </FormItem>
          </Col>
          <Col span="24">
            <FormItem label="直播卖货：">
              <RadioGroup v-model="formValidate.close_goods">
                <Radio
                  :label="item.label"
                  v-for="(item, index) in close_goods"
                  :key="index"
                >
                  <span>{{ item.value }}</span>
                </Radio>
              </RadioGroup>
            </FormItem>
          </Col>
          <Col span="24">
            <FormItem label="直播间评论：">
              <RadioGroup v-model="formValidate.close_comment">
                <Radio
                  :label="item.label"
                  v-for="(item, index) in close_comment"
                  :key="index"
                >
                  <span>{{ item.value }}</span>
                </Radio>
              </RadioGroup>
            </FormItem>
          </Col>
        </Row>
      </Form>
    </Card>
    <Card
      :bordered="false"
      dis-hover
      class="fixed-card"
      :style="{ left: `${!menuCollapse ? '200px' : isMobile ? '0' : '80px'}` }"
    >
      <Form>
        <FormItem>
          <Button type="primary" @click="handleSubmit('formItem')">提交</Button>
        </FormItem>
      </Form>
    </Card>
    <div>
      <Modal
        v-model="modalPic"
        width="960px"
        scrollable
        footer-hide
        closable
        title="上传商品图"
        :mask-closable="false"
        :z-index="1"
      >
        <uploadPictures
          :isChoice="isChoice"
          @getPic="getPic"
          :gridBtn="gridBtn"
          :gridPic="gridPic"
          v-if="modalPic"
        ></uploadPictures>
      </Modal>
    </div>
    <Modal v-model="modal3" title="二维码" @on-cancel="cancel" footer-hide>
      <div class="acea-row row-around">
        <viewer class="QRpic">
          <img
            src="https://res.wx.qq.com/op_res/9rSix1dhHfK4rR049JL0PHJ7TpOvkuZ3mE0z7Ou_Etvjf-w1J_jVX0rZqeStLfwh"
          />
        </viewer>
        <!-- <div v-viewer class="QRpic">
                    <img src="https://res.wx.qq.com/op_res/9rSix1dhHfK4rR049JL0PHJ7TpOvkuZ3mE0z7Ou_Etvjf-w1J_jVX0rZqeStLfwh" />
                </div> -->
      </div>
    </Modal>
  </div>
</template>

<script>
import { mapState } from "vuex";
import uploadPictures from "@/components/uploadPictures";
import { liveAdd, liveAuchorList } from "@/api/live";
export default {
  name: "creat_live",
  components: {
    uploadPictures,
  },
  computed: {
    ...mapState("admin/layout", ["isMobile", "menuCollapse"]),
    labelWidth() {
      return this.isMobile ? undefined : 100;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
  },
  data() {
    return {
      gridBtn: {
        xl: 4,
        lg: 8,
        md: 8,
        sm: 8,
        xs: 8,
      },
      gridPic: {
        xl: 6,
        lg: 8,
        md: 12,
        sm: 12,
        xs: 12,
      },
      grid: {
        xl: 10,
        lg: 16,
        md: 18,
        sm: 24,
        xs: 24,
      },
      formValidate: {
        name: "",
        anchor_name: "",
        anchor_wechat: "",
        phone: "",
        screen_type: 0,
        close_like: 1,
        close_goods: 1,
        close_comment: 1,
        cover_img: "",
        share_img: "",
        sort: 0,
        type: 0,
        start_time: "",
      },
      screen_type: [
        {
          value: "竖屏",
          label: 0,
        },
        {
          value: "横屏",
          label: 1,
        },
      ],
      type: [
        // {
        //     value:'推流',
        //     label:1
        // },
        {
          value: "手机直播",
          label: 0,
        },
      ],
      close_like: [
        {
          value: "开启",
          label: 1,
        },
        {
          value: "关闭",
          label: 0,
        },
      ],
      close_goods: [
        {
          value: "开启",
          label: 1,
        },
        {
          value: "关闭",
          label: 0,
        },
      ],
      close_comment: [
        {
          value: "开启",
          label: 1,
        },
        {
          value: "关闭",
          label: 0,
        },
      ],
      timeVal: "",
      modalPic: false,
      isChoice: "单选",
      activeIndex: 0,
      liveList: [],
      modal3: false,
    };
  },
  mounted() {
    this.getLive();
  },
  methods: {
    cancel() {
      this.modal3 = false;
    },
    codeImg() {
      this.modal3 = true;
    },
    anchorName(e) {
      this.liveList.filter((el, index) => {
        if (el.wechat === e) {
          this.formValidate.anchor_name = el.name;
        }
      });
    },
    //主播列表；
    getLive() {
      let formValidate = {
        kerword: "",
        page: "",
        limit: "",
      };
      liveAuchorList(formValidate)
        .then((res) => {
          this.liveList = res.data.list;
        })
        .catch((error) => {
          this.$Message.error(error.msg);
        });
    },
    // 点击图文封面
    modalPicTap(type) {
      this.activeIndex = type;
      this.modalPic = true;
    },
    // 选择日期
    selectDate(e) {
      this.formValidate.start_time = e;
    },
    // 获取图片信息
    getPic(pc) {
      this.$nextTick(() => {
        if (this.activeIndex == 0) {
          this.formValidate.cover_img = pc.att_dir;
        } else {
          this.formValidate.share_img = pc.att_dir;
        }
        this.modalPic = false;
      });
    },
    // 保存
    handleSubmit(name) {
      liveAdd(this.formValidate)
        .then((res) => {})
        .then((res) => {
          this.$Message.success("添加成功");
          setTimeout(() => {
            this.$router.push({ path: "/admin/content/live/live_room" });
          }, 500);
        })
        .catch((error) => {
          this.$Message.error(error.msg);
        });
    },
  },
};
</script>

<style scoped lang="stylus">
   .QRpic {
       width: 180px;
       height: 180px;

       img {
           width: 100%;
           height: 100%;
       }
   }
   .upload-box {
       display: flex;
       align-items: center;
       justify-content: center;
       width: 60px;
       height: 60px;
       background: #ccc;
   }
   .box{
       width: 60px;
       height: 60px;
       margin-bottom: 10px;

       img {
           width: 100%;
           height: 100%;
       }
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
