<template>
  <div>
    <div class="main" v-show="showType == 0">
      <div class="main-header bg-fff">
        <div class="main-title">
          <span>商品信息</span>
        </div>
      </div>
      <div class="main-content bg-fff">
        <div class="title">顶部导航</div>
        <div class="form">
          <div class="form-item">
            <div class="form-label">菜单内容</div>
            <div class="form-value">
              <CheckboxGroup v-model="productCardInfo.navList" @on-change="navListChange">
                <Checkbox
                    :label="item.value"
                    v-for="(item, index) in navListBox" :key="index"
                >{{ item.label }}
                </Checkbox>
              </CheckboxGroup>
            </div>
          </div>
          <div class="form-item">
            <div class="form-label">开启分享</div>
            <div class="form-value">
              <RadioGroup v-model="productCardInfo.openShare" :true-value="1" :false-value="0">
                <Radio :label="1">显示</Radio>
                <Radio :label="0">隐藏</Radio>
              </RadioGroup>
            </div>
          </div>
        </div>
      </div>
      <div class="main-content bg-fff">
        <div class="title">商品主图</div>
        <div class="form">
          <div class="form-item">
            <div class="form-label">商品主图</div>
            <div class="form-value">
              <RadioGroup
                  v-model="productCardInfo.pictureConfig"
                  :true-value="1"
                  :false-value="0"
              >
                <Radio :label="0">固定方图</Radio>
                <Radio :label="1">高度自适应</Radio>
              </RadioGroup>
            </div>
          </div>
          <div class="form-item mt-20">
            <div class="form-label">轮播点</div>
            <div class="form-value ml-14">
              <RadioGroup
                  v-model="productCardInfo.swiperDot"
                  :true-value="1"
                  :false-value="0"
              >
                <Radio :label="1">显示</Radio>
                <Radio :label="0">隐藏</Radio>
              </RadioGroup>
            </div>
          </div>
        </div>
      </div>
      <div class="main-content bg-fff">
        <div class="title">商品信息</div>
        <div class="form">
          <div class="form-item">
            <div class="form-label">是否开启</div>
            <div class="form-value">
              <CheckboxGroup v-model="productCardInfo.isOpen">
                <Checkbox
                    :label="item.value"
                    v-for="(item, index) in openShowList" :key="index"
                >{{ item.label }}
                </Checkbox>
              </CheckboxGroup>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="main" v-show="showType == 1">
      <div class="main-header bg-fff">
        <div class="main-title">
          <span>会员</span>
        </div>
      </div>
      <div class="main-content bg-fff">
        <div class="title">是否显示</div>
        <div class="form">
          <div class="form-item mt-20">
            <div class="form-label">是否显示</div>
            <div class="form-value ml-14">
              <RadioGroup
                  v-model="productCardInfo.showSvip"
                  :true-value="1"
                  :false-value="0"
              >
                <Radio :label="1">显示</Radio>
                <Radio :label="0">隐藏</Radio>
              </RadioGroup>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="main" v-show="showType == 2">
      <div class="main-header bg-fff">
        <div class="main-title">
          <span>排行榜</span>
        </div>
      </div>
      <div class="main-content bg-fff">
        <div class="title">是否显示</div>
        <div class="form">
          <div class="form-item mt-20">
            <div class="form-label">是否显示</div>
            <div class="form-value ml-14">
              <RadioGroup
                  v-model="productCardInfo.showRank"
                  :true-value="1"
                  :false-value="0"
              >
                <Radio :label="1">显示</Radio>
                <Radio :label="0">隐藏</Radio>
              </RadioGroup>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="main" v-show="showType == 3">
      <div class="main-header bg-fff">
        <div class="main-title">
          <span>商品参数</span>
        </div>
      </div>
      <div class="main-content bg-fff">
        <div class="title">是否显示</div>
        <div class="form">
          <div class="form-item mt-20">
            <div class="form-label">是否显示</div>
            <div class="form-value">
              <CheckboxGroup v-model="productCardInfo.showService">
                <Checkbox :label="0">活动</Checkbox>
                <Checkbox :label="1">规格选择</Checkbox>
                <Checkbox :label="2">服务保障</Checkbox>
                <Checkbox :label="3">参数</Checkbox>
              </CheckboxGroup>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="main" v-if="[4,5,6,8,9].includes(showType)">
      <div class="main-header bg-fff">
        <div class="main-title">
          <span v-if="showType == 4">商品评价</span>
          <span v-else-if="showType == 5">搭配购</span>
          <span v-else-if="showType == 6">优品推荐</span>
          <span v-else-if="showType == 8">种草秀</span>
          <span v-else-if="showType == 9">分销信息</span>
        </div>
      </div>
      <div class="main-content bg-fff">
        <div class="form">
          <div class="form-item mt-20">
            <div class="form-label">是否显示</div>
            <div class="form-value" v-if="showType == 4">
              <RadioGroup v-model="productCardInfo.showReply" :true-value="1" :false-value="0">
                <Radio :label="1">显示</Radio>
                <Radio :label="0">隐藏</Radio>
              </RadioGroup>
            </div>
            <div class="form-value" v-else-if="showType == 5">
              <RadioGroup v-model="productCardInfo.showMatch" :true-value="1" :false-value="0">
                <Radio :label="1">显示</Radio>
                <Radio :label="0">隐藏</Radio>
              </RadioGroup>
            </div>
            <div class="form-value" v-else-if="showType == 6">
              <RadioGroup v-model="productCardInfo.showRecommend" :true-value="1" :false-value="0">
                <Radio :label="1">显示</Radio>
                <Radio :label="0">隐藏</Radio>
              </RadioGroup>
            </div>
            <div class="form-value" v-else-if="showType == 8">
              <RadioGroup v-model="productCardInfo.showCommunity" :true-value="1" :false-value="0">
                <Radio :label="1">显示</Radio>
                <Radio :label="0">隐藏</Radio>
              </RadioGroup>
            </div>
            <div class="form-value" v-else-if="showType == 9">
              <RadioGroup v-model="productCardInfo.showPromoter">
                <Radio :label="1">显示</Radio>
                <Radio :label="0">隐藏</Radio>
              </RadioGroup>
            </div>
          </div>
          <div class="form-item mt-20" v-if="showType == 9 && productCardInfo.showPromoter">
            <div class="form-label w-48">展示</div>
            <div class="form-value">
              <RadioGroup v-model="productCardInfo.showPromoterType">
                <Radio :label="0">全部用户</Radio>
                <Radio :label="1">仅分销员</Radio>
                <Radio :label="2">普通用户</Radio>
              </RadioGroup>
            </div>
          </div>
          <!--  -->
          <div class="form-item flex-y-center mt-20">
            <div class="form-label" v-if="showType == 4">评价数量</div>
            <div class="form-label" v-else-if="showType == 5">套餐数量</div>
            <div class="form-label" v-else-if="showType == 6">商品数量</div>
            <div class="form-label" v-else-if="showType == 8">内容数量</div>
            <div class="form-value flex-1" v-if="showType == 4">
              <Slider v-model="productCardInfo.replyNum" :min="1" :max="10" show-input input-size="small"></Slider>
            </div>
            <div class="form-value flex-1" v-else-if="showType == 5">
              <Slider v-model="productCardInfo.matchNum" :min="1" :max="10" show-input input-size="small"></Slider>
            </div>
            <div class="form-value flex-1" v-else-if="showType == 6">
              <Slider v-model="productCardInfo.recommendNum" :min="1" :max="24" show-input input-size="small"></Slider>
            </div>
            <div class="form-value flex-1" v-else-if="showType == 8">
              <Slider v-model="productCardInfo.communityNum" :min="1" :max="10" show-input input-size="small"></Slider>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="main" v-show="showType == 7">
      <div class="main-header bg-fff">
        <div class="main-title">
          <span>底部菜单</span>
        </div>
      </div>
      <div class="main-content bg-fff">
        <div class="form">
          <div class="form-item mt-20">
            <div class="form-label">是否显示</div>
            <div class="form-value">
              <CheckboxGroup v-model="productCardInfo.menuList" >
<!--                @on-change="changeProperty"-->
                <Checkbox
                    v-for="(item, index) in footerMenu" :key="index"
                    :label="item.value"
                    :disabled="!productCardInfo.menuList.includes(item.value) && productCardInfo.menuList.length >=3"
                >{{ item.label }}</Checkbox>
              </CheckboxGroup>
            </div>
          </div>
          <div class="form-item mt-20">
            <div class="form-label">菜单状态</div>
            <div class="form-value">
              <RadioGroup v-model="productCardInfo.menuStatus">
                <Radio :label="0">常规版</Radio>
                <Radio :label="1">分销版</Radio>
              </RadioGroup>
            </div>
          </div>
          <div class="form-item mt-20">
            <div class="form-label" style="margin-right: 30px;">购物车按钮</div>
            <div class="form-value">
              <RadioGroup v-model="productCardInfo.showCart">
                <Radio :label="1">显示</Radio>
                <Radio :label="0">隐藏</Radio>
              </RadioGroup>
            </div>
          </div>
          <div class="form-item mt-20" v-if="productCardInfo.menuStatus">
            <div class="form-label w-48">分享赚展示</div>
            <div class="form-value">
              <RadioGroup v-model="productCardInfo.showMenuPromoterShare">
                <Radio :label="0">全部用户</Radio>
                <Radio :label="1">仅分销员</Radio>
                <Radio :label="2">普通用户</Radio>
              </RadioGroup>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props:{
    showType:{
      type: Number,
      default: 0
    },
    productCardInfo:{
      type: Object,
      default: ()=>{}
    }
  },
  data() {
    return {
      navListBox: [
        {label: '首页', value: 0},
        {label: '搜索', value: 1},
        {label: '购物车', value: 2},
        {label: '我的收藏', value: 3},
        {label: '个人中心', value: 4},
      ],
      openShowList:[
        {label: '划线价', value: 0},
        {label: '累计销量', value: 1},
        {label: '库存', value: 2},
      ],
      footerMenu:[
        {label: '首页', value: 0},
        {label: '分享', value: 1},
        {label: '客服', value: 2},
        {label: '收藏', value: 3},
        {label: '购物车', value: 4},
      ]
    }
  },
  methods: {
    navListChange(e){
      if(e.length == 0) {
        return this.$Message.warning("该导航请至少选择一个");
      }
    }
  }
}
</script>

<style scoped lang="less">
.bg-fff {
  background-color: #fff;
}

.main {
  .main-header {
    font-size: 16px;
    padding: 20px 15px;
    margin: 6px 0 0;
    font-weight: 500;
    color: #333333;
    line-height: 16px;
  }

  .main-content {
    padding: 20px 15px;
    margin-top: 6px;
    border-top: 6px solid #f0f2f5;

    .title {
      font-size: 14px;
      font-weight: 400;
      color: #333333;
      line-height: 14px;
      margin-bottom: 20px;
    }

    .form {
      .form-item:last-child {
        margin-bottom: 0;
      }

      .form-item {
        display: flex;
        font-size: 12px;
        font-weight: 400;
        line-height: 12px;

        .form-label {
          color: #999999;
          line-height: 17px;
          margin-right: 40px;
          white-space: nowrap;
        }

        .form-value {
          color: #666666;

          /deep/ .ivu-radio-wrapper {
            margin-right: 23px;
          }

          /deep/ .ivu-checkbox-wrapper {
            margin-bottom: 22px;
            width: 84px;
            font-size: 12px;
          }
        }
      }
    }
  }
}
</style>
