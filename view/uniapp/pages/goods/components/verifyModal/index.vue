<template>
	<view class="modal_container" v-if="visible" @touchmove.stop.prevent>
		<view class="modal_box relative">
			<view class="h-172 flex-col flex-center">
				<view class="fs-32 lh-44rpx fw-500">核销信息</view>
				<view class="fs-26 lh-36rpx mt-28" v-if="productType == 4">已核销 {{writeOff}} / 需要核销 {{writeTimes}}</view>
			</view>
			<view class="mt-52 flex-col flex-center">
				<view class="qrcode flex-center">
					<view class="qrcode_content">
						<!-- #ifdef MP -->
						<image :src="qrc" class="image"></image>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<image v-if="$wechat.isWeixin()" :src="qrc" class="image"></image>
						<w-qrcode v-else :options="qrcode"></w-qrcode>
						<!-- #endif -->
						<!-- #ifdef APP-PLUS -->
						<w-qrcode :options="qrcode"></w-qrcode>
						<!-- #endif -->
					</view>
				</view>
				<view class="bg-primary-light qrocode-num rd-16rpx flex-center fs-32 fw-500 font-color mt-48">{{verifyCode}}</view>
				<view class="mt-24 text--w111-999 fs-24" v-if="writeDay">核销时间：{{writeDay}}</view>
			</view>
			<text class="iconfont icon-ic_close1 close fs-48 text--w111-fff"></text>
		</view>
		<view class="mask z-90" @click="closeModal"></view>
	</view>
</template>

<script>
	export default {
		props:{
			visible: {
			    type: Boolean,
			    default: false,
			},
			qrcode:{
				type:Object,
				default: ()=>{}
			},
			verifyCode:{
				type: String,
				default:""
			},
			writeDay:{
				type: String,
				default:""
			},
			writeOff:{
				type: [String, Number],
				default: 0
			},
			writeTimes:{
				type: [String, Number],
				default: 0
			},
			productType:{
				type: Number,
				default: 0
			},
			qrc:{
				type: String,
				default:""
			},
		},
		methods:{
			moveHandle(){
				return false
			},
			closeModal(){
				this.$emit('closeModal');
			}
		}
	}
</script>

<style>
	.modal_box{
		position: fixed;
		left: 50%;
		top: 50%;
		transform: translate(-50%,-50%);
		z-index: 3000;
		width:600rpx;
		height:906rpx;
		background:#fff;
		border-radius:32rpx;
		-webkit-mask: radial-gradient(circle at 14rpx 172rpx, transparent 14rpx, red 0) -14rpx;
	}
	.h-172{
		height:172rpx;
		border-bottom: 1px dashed #ccc;
	}
	.z-90{
		z-index:2999;
	}
	.qrcode{
		width:440rpx;
		height:440rpx;
		background-image: url('../../static/qrcode_bg.png');
		background-size:100%;
		background-repeat: no-repeat;
	}
	.qrcode_content{
		width:360rpx;
		height:360rpx;
	}
	.bg-primary-light{
		background: var(--view-minorColorT);
	}
	.qrocode-num{
		width:440rpx;
		height:80rpx;
	}
	.close{
		position: absolute;
		left:50%;
		bottom: -96rpx;
		transform: translateX(-50%);
	}
	.qrcode_content .image {
		width: 100%;
		height: 100%;
	}
</style>