<template>
	<scroll-view class="scroll-view" scroll-y="true" @scroll="scrollView">
		<!-- #ifdef MP -->
		<NavBar titleText="会员码" :iconColor="iconColor" :textColor="iconColor" showBack :isScrolling="isScrolling"></NavBar>
		<!-- #endif -->
		<view class="wrapper">
			<view class="wrapper-content">
				<view class="header" :style="[headerStyle]" v-if="currentLevel">
					<view class="title" :style="{ color: currentLevel.color }">尊敬的{{nowLevel.name ? nowLevel.name : ''}}</view>
					<view class="progress-wrap acea-row row-center-wrapper" :style="{
						color: colorToRgba(currentLevel.color||'', 0.8)
					}">
						<view v-show="currentLevel.grade">V{{currentLevel.grade}}</view>
						<view class="progress">
							<view class="inner" :style="{
								width: `${nextLevel ? (nowLevel.exp / nextLevel.exp_num * 100) : 100}%`,
								background: colorToRgba(currentLevel.color||'', 0.8)
							}"></view>
						</view>
						<view v-if="nextLevel">V{{nextLevel.grade}}</view>
					</view>
					<view class="info acea-row row-center-wrapper" :style="{
						color: currentLevel.color
					}">
						<template v-if="nextLevel">
							还需
							<text class="number" :style="{color: colorToRgba(currentLevel.color||'', 0.8)}">
								{{$util.$h.Sub(nextLevel.exp_num, nowLevel.exp)}}
							</text>
							经验到下一级
						</template>
						<template v-else>您已经是最高等级</template>
					</view>
				</view>
				<view class="content">
					<!-- <view class="acea-row row-center-wrapper">
						<w-barcode :options="config.bar"></w-barcode>
					</view> -->
					<view class="acea-row row-center-wrapper" style="margin-top: 56rpx;">
						<!-- #ifdef MP -->
						<image :src="qrc" class="qrcode"></image>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<image v-if="$wechat.isWeixin()" :src="qrc" class="qrcode"></image>
						<w-qrcode v-else :options="config.qrc"></w-qrcode>
						<!-- #endif -->
						<!-- #ifdef APP-PLUS -->
						<w-qrcode :options="config.qrc"></w-qrcode>
						<!-- #endif -->
					</view>
					<view class="codeNum acea-row row-center-wrapper">{{config.bar.code}}</view>
					<view class="balance-wrapper">
						<view class="balance acea-row row-column row-center-wrapper">
							<view class="">当前余额<text class="iconfont" :class="[isEye?'icon-ic_Eyes':'icon-ic_eye']" @click="toggleEye"></text></view>
							<view class="number" v-if="isEye">{{userInfo.now_money}}</view>
							<view class="number" v-else style="font-weight: 500;font-size: 48rpx;">******</view>
						</view>
						<view class="attribute acea-row row-center-wrapper">
							<view class="item" @click="goDetail(1)">
								<view class="iconfont icon-ic_coupon"></view>
								<view class="">{{userInfo.couponCount}}张券</view>
							</view>
							<view class="item" @click="goDetail(2)">
								<view class="iconfont icon-ic_gold1"></view>
								<view class="">{{userInfo.integral}}积分</view>
							</view>
							<view class="item" @click="goDetail(3)" v-if="VipList.length && levelInfo.exp">
								<view class="iconfont icon-ic_sale"></view>
								<view class="">{{userInfo.vip_discount}}折</view>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
	</scroll-view>
</template>

<script>
	// #ifdef MP
	import NavBar from '@/components/NavBar.vue'
	// #endif
	import {
		getUserInfo,
		getlevelInfo,
		getRandCode,
	} from '@/api/user.js';
	import {
		activityCodeApi
	} from '@/api/activity.js';
	export default {
		components: {
			// #ifdef MP
			NavBar,
			// #endif
		},
		data() {
			return {
				iconColor: '#FFFFFF',
				isScrolling: false,
				userInfo: {},
				VipList: [],
				currentLevel: {},
				nextLevel: null,
				nowLevel: {
					name:'',
					exp:'',
					exp_num:0,
				},
				isEye: true,
				config: {
					bar: {
						code: '',
						color: ['#000'],
						bgColor: '#FFFFFF', // 背景色
						width: 480, // 宽度
						height: 110 // 高度
					},
					qrc: {
						code: '',
						size: 380, // 二维码大小
						level: 3, //等级 0～4
						bgColor: '#FFFFFF', //二维码背景色 默认白色
						border: {
							color: ['#eee', '#eee'], //边框颜色支持渐变色
							lineWidth: 3, //边框宽度
						},
						// img: '/static/logo.png', //图片
						// iconSize: 40, //二维码图标的大小
						color: ['#333', '#333'], //边框颜色支持渐变色
					}
				},
				qrc: ''
			}
		},
		computed: {
			headerStyle() {
				let styleObject = {};
				if (this.nextLevel) {
					styleObject['background'] =
						`linear-gradient(91deg, ${this.colorToRgba(this.currentLevel.color||'', 0.05)} 0%, ${this.colorToRgba(this.currentLevel.color||'', 0.4)} 100%)`;
				}
				return styleObject;
			}
		},
		onLoad() {
			this.getCode();
			this.getUserInfo();
			this.getlevelInfo();
			this.activityCodeApi();
		},
		methods: {
			activityCodeApi() {
				activityCodeApi(90, 0).then(res => {
					const {
						routineUrl,
						wechatUrl
					} = res.data;
					// #ifdef MP
					this.qrc = routineUrl;
					// #endif
					// #ifdef H5
					if (this.$wechat.isWeixin()) {
						this.qrc = wechatUrl;
					}
					// #endif
				});
			},
			goDetail(val) {
				if (val == 1) {
					uni.navigateTo({
						url: '/pages/users/user_coupon/index'
					})
				} else if (val == 2) {
					uni.navigateTo({
						url: '/pages/users/user_integral/index'
					})
				} else if (val == 3) {
					uni.navigateTo({
						url: '/pages/users/user_vip/index'
					})
				}
			},
			scrollView(e) {
				if (e.detail.scrollTop > 50) {
					this.isScrolling = true;
					this.iconColor = '#333333';
				} else if (e.detail.scrollTop < 50) {
					this.isScrolling = false;
					this.iconColor = '#FFFFFF';
				}
			},
			getCode() {
				getRandCode().then(res => {
					let code = res.data.code;
					this.config.bar.code = code;
					this.config.qrc.code = code;
				}).catch(err => {
					return this.$util.Tips(err);
				})
			},
			getUserInfo: function() {
				let that = this;
				getUserInfo().then(res => {
					that.userInfo = res.data
					that.config.bar.code = that.userInfo.bar_code;
					that.config.qrc.code = that.userInfo.bar_code;
				});
			},
			getlevelInfo: function() {
				getlevelInfo().then(res => {
					const {
						level_info,
						level_list,
						task,
						user
					} = res.data;
					this.levelInfo = level_info;
					level_list.forEach(item => {
						switch (item.grade) {
							case 1:
								item.background = ['#091D2B', '#000F18'];
								break;
							case 2:
								item.background = ['#131A1D', '#040809'];
								break;
							case 3:
								item.background = ['#4B351C', '#14100C'];
								break;
							case 4:
								item.background = ['#1D172B', '#17151E'];
								break;
							case 5:
								item.background = ['#0F0D07', '#0F0D07'];
								break;
							default:
								break;
						}
					})
					this.VipList = level_list;
					const index = level_list.findIndex(item => item.grade == level_info.grade);
					this.currentLevel = level_list[index];
					this.nextLevel = level_list[index + 1];
					this.nowLevel = level_info;
				});
			},
			toggleEye() {
				this.isEye = !this.isEye;
			},
			colorToRgba(str, n) {
				// 十六进制颜色值的正则表达式
				const reg = /^#([0-9a-fA-f]{3}|[0-9a-fA-f]{6})$/;
				let sColor = str.toLowerCase();
				// 十六进制颜色转换为RGB格式
				if (sColor && reg.test(sColor)) {
					if (sColor.length === 4) {
						let sColorNew = '#';
						for (let i = 1; i < 4; i += 1) {
							sColorNew += sColor.slice(i, i + 1).concat(sColor.slice(i, i + 1));
						}
						sColor = sColorNew;
					}
					// 处理六位颜色值
					const sColorChange = [];
					for (let k = 1; k < 7; k += 2) {
						sColorChange.push(parseInt(`0x${sColor.slice(k, k + 2)}`, 16));
					}
					return `rgba(${sColorChange.join(',')}, ${n})`;
				}
				return sColor;
			}
		},
	}
</script>

<style lang="scss" scoped>
	.scroll-view {
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		background: linear-gradient(180deg, #1B1A17 0%, #141414 100%);

		.wrapper {
			padding: 94rpx 32rpx;
		}

		.wrapper-content {
			border-radius: 32rpx;
			background: #FFFFFF;
			overflow: hidden;
		}

		.header {
			padding: 42rpx 0 40rpx;
			border-radius: 32rpx 32rpx 0 0;
			background: linear-gradient(91deg, rgba(26, 25, 23, 0.9) 0%, #1A1917 100%);

			.title {
				text-align: center;
				font-weight: 600;
				font-size: 44rpx;
				line-height: 62rpx;
				color: #804400;
			}

			.progress-wrap {
				margin-top: 14rpx;
				font-size: 22rpx;
				line-height: 30rpx;
				color: rgba(126, 75, 6, 0.8);
			}

			.progress {
				width: 360rpx;
				height: 8rpx;
				border-radius: 4rpx;
				margin: 0 16rpx;
				background: rgba(255, 255, 255, 0.4);
			}

			.inner {
				height: 8rpx;
				border-radius: 4rpx;
				background: rgba(126, 75, 6, 0.8);
			}

			.info {
				margin-top: 24rpx;
				font-size: 24rpx;
				line-height: 34rpx;
				color: #7E4B06;
			}

			.number {
				margin: 0 4rpx;
				font-family: SemiBold;
				font-size: 28rpx;
				color: rgba(126, 75, 6, 0.8);
			}
		}

		.content {
			padding: 28rpx 0 0;
		}

		.codeNum {
			margin-top: 12rpx;
			letter-spacing: 3px;
			font-weight: 500;
			font-size: 28rpx;
			line-height: 40rpx;
			color: #333333;
		}

		.balance-wrapper {
			margin-top: 40rpx;
			background: #F9F9F9;
		}

		.balance {
			padding: 54rpx 0 76rpx;
			font-size: 24rpx;
			line-height: 34rpx;
			color: #999999;

			.iconfont {
				margin-left: 8rpx;
				font-size: 28rpx;
			}

			.number {
				margin-top: 20rpx;
				font-family: SemiBold;
				font-size: 56rpx;
				line-height: 56rpx;
				color: #333333;
			}
		}

		.attribute {
			padding-bottom: 48rpx;
			text-align: center;
			font-size: 24rpx;
			line-height: 34rpx;
			color: #666666;

			.item {
				margin-right: 123rpx;

				&:last-child {
					margin-right: 0;
				}
			}

			.iconfont {
				margin-bottom: 16rpx;
				font-size: 48rpx;
				color: #444444;
			}
		}
	}

	.qrcode {
		width: 380rpx;
		height: 380rpx;
	}
</style>