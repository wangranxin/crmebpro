<template>
	<!-- 头部占位 -->
	<view>
		<!-- #ifdef APP-PLUS || MP -->
		<view class="sys-head" :style="{height:sysHeight}"></view>
		<!-- #endif -->
		<!-- #ifdef MP || APP-PLUS -->
		<view class="sys-title">{{title}}</view>
		<!-- #endif -->
	</view>
</template>

<script>
	let sysHeight = uni.getWindowInfo().statusBarHeight + 'px';
	export default {
		props: {
			title:{
				type:String,
				default:''
			}
		},
		data() {
			return {
				sysHeight: sysHeight
			}
		},
	}
</script>

<style scoped>
	.sys-title {
		z-index: 10;
		position: relative;
		height: 40px;
		line-height: 40px;
		font-size: 30rpx;
		color: #fff;
		background-color: var(--view-theme);
		// #ifdef MP || APP-PLUS
		text-align: center;
		// #endif
	}

	.sys-head {
		background-color: #fff;
	}
</style>
