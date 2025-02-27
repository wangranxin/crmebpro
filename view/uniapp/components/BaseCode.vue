<template>
	<view class="codePopup tui-modal__container" :class="[isShowCode ? 'tui-modal-show' : '']" @touchmove.stop.prevent="moveHandle">
		<view class="tui-modal-box" :class="[isShowCode ? 'tui-modal-normal' : 'tui-modal-scale', isShowCode ? 'tui-modal-show' : '']">
			<view class="header acea-row row-between-wrapper">
				<view class="title on">员工推广码</view>
			</view>
			<view>
				<view class="acea-row row-center-wrapper mb-20 mt-28">
					<!-- #ifndef MP -->
					<w-qrcode :options="config.qrc"></w-qrcode>
					<!-- #endif -->
					<!-- #ifdef MP -->
					<image class="code-img" :src="codeImg" show-menu-by-longpress></image>
					<!-- #endif -->
				</view>
				<view class="tip">如遇到扫码失败</view>
				<view class="tip">请将屏幕调至最亮重新扫码</view>
			</view>
			<view class="close">
				<view class="iconfont icon-ic_close1" @click="close"></view>
			</view>
		</view>
		<view class="mark" v-if="isShowCode" @touchmove.stop.prevent="moveHandle"></view>
	</view>
</template>

<script>
export default {
	name: 'codeModal',
	props: {
		isShowCode: {
			type: Boolean,
			default: false
		},
		code: {
			type: String,
			default: ''
		},
		codeImg: {
			type: String,
			default: ''
		}
	},
	data() {
		return {
			config: {
				bar: {
					code: '',
					color: ['#000'],
					bgColor: '#FFFFFF', // 背景色
					width: 480, // 宽度
					height: 110 // 高度
				},
				qrc: {
					code: '123123',
					size: 380, // 二维码大小
					level: 3, //等级 0～4
					bgColor: '#FFFFFF', //二维码背景色 默认白色
					color: ['#333', '#333'] //边框颜色支持渐变色
				}
			}
		};
	},
	watch: {
		code(newVal) {
			// #ifdef MP
			this.config.qrc.code = newVal;
			// #endif
		}
	},
	mounted() {
		console.log(this.code);
		// #ifndef MP
		this.config.qrc.code = this.code;
		// #endif
	},
	methods: {
		moveHandle() {},
		close() {
			this.$emit('update:isShowCode', false);
		}
	}
};
</script>

<style lang="scss">
.mark {
	position: fixed;
	top: 0;
	left: 0;
	bottom: 0;
	right: 0;
	background: rgba(0, 0, 0, 0.5);
	z-index: 50;
}
.tui-modal__container {
	width: 100%;
	height: 100%;
	position: fixed;
	left: 0;
	top: 0;
	display: flex;
	align-items: center;
	justify-content: center;
	visibility: hidden;
	z-index: 51;
}

.tui-modal-box {
	position: relative;
	opacity: 0;
	visibility: hidden;
	box-sizing: border-box;
	transition: all 0.3s ease-in-out;
	width: 600rpx;
	padding: 40rpx;
	border-radius: 32rpx;
	background-color: #fff;
	z-index: 9999;
	.tip {
		color: #999999;
		font-size: 26rpx;
		text-align: center;
	}
	.close {
		width: 520rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		position: fixed;
		bottom: -80rpx;
		.icon-ic_close1 {
			font-size: 52rpx;
			color: #cccccc;
		}
	}
}

.tui-modal-scale {
	transform: scale(0);
}

.tui-modal-normal {
	transform: scale(1);
}

.tui-modal-show {
	opacity: 1;
	visibility: visible;
}

.codePopup .header .title {
	width: 100%;
	text-align: center;
	font-weight: 500;
	font-size: 32rpx;
	color: #333333;
}
.code-img{
	width: 380rpx;
	height: 380rpx;
}
</style>
