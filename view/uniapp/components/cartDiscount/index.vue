<template>
	<view>
		<!-- 购物车优惠明细 -->
		<base-drawer mode="bottom" :visible="discountInfo.discount" background-color="transparent" mask maskClosable @close="closeDiscount">
		  <view class="w-full bg--w111-fff rd-t-40rpx py-32">
		    <view class="text-center fs-32 text--w111-333 fw-500">金额明细</view>
			<scroll-view  scroll-y="true" class="mt-32 scroll-content">
				<view class="p-32">
					<view class="flex-between-center">
						<view class="fs-26">商品总价：</view>
						<view class="fs-28 SemiBold">￥{{discountInfo.deduction.sum_price}}</view>
					</view>
					<view class="flex-between-center mt-38">
						<view class="fs-26">优惠抵扣：</view>
						<view class="fs-28 SemiBold">-￥{{$util.$h.Sub(discountInfo.deduction.sum_price,discountInfo.deduction.pay_price)}}</view>
					</view>
					<view class="flex-between-center mt-38" v-if="discountInfo.deduction.coupon_price">
						<view class="fs-26">{{discountInfo.coupon.coupon_title}}：</view>
						<view class="fs-28 SemiBold">-￥{{discountInfo.deduction.coupon_price}}</view>
					</view>
					<view class="flex-between-center mt-38" v-if="discountInfo.deduction.first_order_price">
						<view class="fs-26">新人首单优惠：</view>
						<view class="fs-28 SemiBold">-￥{{discountInfo.deduction.first_order_price}}</view>
					</view>
					<view class="flex-between-center mt-38" v-if="discountInfo.deduction.promotions_price">
						<view class="fs-26">优惠活动：</view>
						<view class="fs-28 SemiBold">-￥{{discountInfo.deduction.promotions_price}}</view>
					</view>
					<view class="flex-between-center mt-38" v-if="discountInfo.deduction.vip_price">
						<view class="fs-26">会员优惠：</view>
						<view class="fs-28 SemiBold">-￥{{discountInfo.deduction.vip_price}}</view>
					</view>
					<view class="flex-between-center mt-44" v-if="discountInfo.deduction.vip_price">
						<view class="fs-28 fw-500">优惠合计</view>
						<view class="fs-32 SemiBold font-num">-￥{{$util.$h.Sub(discountInfo.deduction.sum_price,discountInfo.deduction.pay_price)}}</view>
					</view>
				</view>
			</scroll-view>
			<view :class="showFooter ? 'show-footer' : 'hide-footer'"></view>
		  </view>
		</base-drawer>
	</view>
</template>

<script>
	import baseDrawer from '@/components/tui-drawer/tui-drawer.vue';
	export default {
		props: {
			discountInfo: {
				type: Object,
				default: () => {}
			},
			showFooter:{
				type:Boolean,
				default: false
			}
		},
		components:{
			baseDrawer
		},
		data() {
			return {};
		},
		mounted() {},
		methods: {
			closeDiscount(){
				this.$emit('myevent');
			}
		}
	}
</script>

<style scoped lang="scss">
	.scroll-content {
		max-height: 800rpx;
	}
	.show-footer{
		height: calc(194rpx + env(safe-area-inset-bottom));
	}
	.hide-footer{
		/* #ifdef H5 */
		height: calc(194rpx + env(safe-area-inset-bottom));
		/* #endif */
		/* #ifdef MP || APP-PLUS */
		height: calc(100rpx + env(safe-area-inset-bottom));
		/* #endif */
	}
</style>
