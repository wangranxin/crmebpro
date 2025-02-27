<template>
  <div class="px-10">
    <div class="rd-12px bg--w111-fff mb-8 px-10">
      <div class="flex-between-center h-45 bb-f5">
        <div class="text--w111-333 fs-13">推荐好友下单可赚<span class="money">¥3.99</span>元</div>
        <div class="more-tag flex-center">
          <img src="@/assets/images/jifen_diy_icon.png" style="width: 12px;" />
          <span class="text--w111-fff fs-12 pl-4">去分享</span>
          <span class="mobiconfont icon-ic_rightarrow text--w111-fff fs-12"></span>
        </div>
      </div>
      <div class="pt-16 flex-between-center">
        <span class="fs-14 lh-20rpx text--w111-333 fw-bold">分享文案</span>
        <span class="money lh-15rpx" style="font-size: 11px;">复制</span>
      </div>
      <div class="fs-13 lh-18rpx pt-8 pb-12">
        <div>【商品】晨光 供港壹号纯牛奶 早餐奶200ml*12盒</div>
        <div>【优势】晨光  供港壹号纯牛奶 早餐奶200ml*12盒 供港壹纯牛奶【1891标准】纯牛奶*3箱</div>
      </div>
    </div>
    <div class="mask" v-if="!showPromoter">已隐藏</div>
  </div>
</template>
<script>
export default {
  props:{
    showPromoter:{
      type: Number,
      default: 1
    },
    showPromoterType:{
      type: Number,
      default: 0
    }
  },
  data(){
    return {
      pic: require('@/assets/images/product_diy.png')
    }
  }
}
</script>
<style scoped lang="less">
.more-tag{
  width: 77px;
  height: 25px;
  background: #423F3C;
  border-radius: 20px;
}
.money{
  color: #e93323;
}
.h-45{
  height: 45px;
}
.bb-f5{
  border-bottom: 1px solid #f5f5f5;
}
.pt-8{
  padding-top: 8px;
} 
.pb-12{
  padding-bottom: 12px;
}
.pt-16{
  padding-top: 16px;
}
.space-line{
  white-space: pre-line;
}
</style>
