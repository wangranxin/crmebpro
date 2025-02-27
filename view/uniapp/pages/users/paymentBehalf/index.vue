<template>
	<view :style="colorStyle">
		<view class="w-full relative  z-99 bg-gradient">
			<NavBar titleText="好友代付"
				textSize="34rpx" 
				:isScrolling="isScrolling"
				:iconColor="isScrolling ? '#333333' : '#ffffff'" 
				:textColor="isScrolling ? '#333333' : '#ffffff'" 
				showBack></NavBar>
			<view class="flex-col flex-center">
				<image src="../static/logo2.png" class="w-92 h-96 rd-50-p111-"></image>
				<view class="text--w111-fff fs-28 lh-40rpx pt-24">代付订单创建成功，发给好友帮你付款吧~</view>
				<view class="h-152"></view>
			</view>
		</view>
		<view class="flex-x-center px-20 content-box relative z-100">
			<view class="w-full bg--w111-fff rd-24rpx flex-col flex-center pt-52">
				<view class="fs-30 lh-42rpx pb-22">代付金额</view>
				<baseMoney money="199.00" symbolSize="40" integerSize="72" decimalSize="72" color="#333333" weight></baseMoney>
				<view class="w-full px-24 flex-between-center my-40">
					<view class="w-320 h-80 rd-40rpx flex-center view-border font-num fs-28">查看订单详情</view>
					<view class="w-320 h-80 rd-40rpx flex-center bg-color text--w111-fff fs-28">发给微信好友</view>
				</view>
			</view>
		</view>
		<view class="flex-x-center px-20 mt-20">
			<view class="w-full bg--w111-fff rd-24rpx pt-32 pr-24 pb-32 pl-24">
				<view class="fs-28 lh-40rpx mb-26">共4件商品</view>
				<view class="flex-between-center pro-card">
					<image src="https://test-pro30.crmeb.net/uploads/attach/2024/10/20241031/7b3ba8ff404a8ab53b3e1bcef12d774d.png" class="w-136 h-136 rd-16rpx" mode="aspectFill"></image>
					<view class="ml-20 flex-1">
						<view class="w-508 fs-28 lh-40rpx line1">兰蔻唇香礼盒是香水口红兰蔻唇香礼盒礼盒</view>
						<view class="fs-24 text--w111-999 lh-34rpx pt-8">兰蔻唇香礼盒是香水口红兰蔻唇香礼盒...</view>
						<view class="w-full flex-between-center mt-20">
							<baseMoney money="199.00" symbolSize="20" integerSize="32" decimalSize="20" color="#333333" weight></baseMoney>
							<text class="fs-24 text--w111-999">x1</text>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import colors from '@/mixins/color.js';
import { HTTP_REQUEST_URL } from '@/config/app.js';
import { toLogin } from '@/libs/login.js';
import { mapGetters } from 'vuex';
import home from '@/components/home/index.vue';
import NavBar from "@/components/NavBar.vue"
let sysHeight = uni.getWindowInfo().statusBarHeight;
export default {
	data() {
		return {
			sysHeight,
			isScrolling: false,
		}
	},
	mixins: [colors],
	components: { home, NavBar },
	computed: mapGetters(['isLogin']),
	onPageScroll(option) {
		uni.$emit('scroll');
		if (option.scrollTop > 50) {
			this.isScrolling = true;
		} else if (option.scrollTop < 50) {
			this.isScrolling = false;
		}
	},
	methods: {
		
	}
}
</script>

<style lang="scss" scoped>
.bg-gradient{
	background: linear-gradient(180deg, var(--view-theme) 0%, var(--view-theme) 52%, rgba(233,51,35,0) 100%);
}
.h-152{
	height: 152rpx;
}
.content-box{
	margin-top: -120rpx;
}
.pt-52{
	padding-top: 52rpx;
}
.view-border{
	border: 1px solid var(--view-theme);
}
.pro-card ~ .pro-card{
	margin-top: 26rpx;
}
</style>
