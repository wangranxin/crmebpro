<template>
	<view :style="colorStyle">
		<view class="OrderCancellation">
			<view class="header"></view>
			<view class="whiteBg" v-if="tabOn == 2">
				<view class="input">
					<input type="number" placeholder="请输入" v-model="verify_code" />
				</view>
				<view class="bnt" @click="codeChange">
					<text class="iconfont icon-ic_search"></text>
				</view>
			</view>
			<view v-if="tabOn == 1" class="scan" @click="scanCode">
				<text class="iconfont icon-ic_Scan"></text>
			</view>
			<view class="tabbar-box acea-row">
				<view class="item" :class="{ on: tabOn == 1 }" @click="onTab(1)">
					<view class="bg"></view>
					<view class="inner"><text class="iconfont icon-ic_Scan"></text>扫码核销</view>
				</view>
				<view class="item" :class="{ on: tabOn == 2 }" @click="onTab(2)">
					<view class="bg"></view>
					<view class="inner"><text class="iconfont icon-ic_edit"></text>手动输入</view>
				</view>
			</view>
		</view>
		<view v-if="iShidden">
			<view class="WriteOff">
				<view class="pictrue">
					<image :src="orderInfo.image" />
				</view>
				<view class="num acea-row row-center-wrapper">
					<text>{{ orderInfo.order_id }}</text>
					<view class="views" @click='goOrderDetails(orderInfo.order_id,orderInfo.order_type)'>查看<text class='iconfont icon-jiantou views-jian'></text></view>
				</view>
				<view class="tip">确定要核销此订单吗？</view>
				<view class="sure" @click="confirm">确定核销</view>
				<view class=" cancel" @click="cancel">取消</view>
			</view>
			<view class="mask"></view>
		</view>
	</view>
</template>

<script>
	import {
		orderVerific
	} from "@/api/admin";
	import colors from '@/mixins/color.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import {
		mapGetters
	} from 'vuex';
	export default {
		mixins: [colors],
		data() {
			return {
				iShidden: false,
				verify_code: '',
				isWeixin: '',
				orderInfo: {},
				tabOn: 1,
				auth: 1,
			}
		},
		computed: mapGetters(['isLogin']),
		onLoad(options) {
			// #ifdef H5 || APP-PLUS
			this.isWeixin = this.$wechat.isWeixin();
			// #endif
			// #ifdef MP
			if (options.scene) {
				options = this.$util.getUrlParams(decodeURIComponent(options.scene));
			}
			// #endif
			const {
				auth,
				code
			} = options;
			this.auth = auth || 1;
			this.verify_code = code || '';
		},
		onShow() {
			if (this.isLogin) {
				if (this.auth == 3) {
					this.codeChange();
				}
			} else {
				toLogin();
			}
		},
		methods: {
			/**
			 * 去订单详情
			 */
			goOrderDetails: function(id, type) {
				if (type == 'integral') {
					uni.navigateTo({
						url: '/pages/points_mall/integral_order_details?order_id=' + id
					});
				} else {
					uni.navigateTo({
						url: '/pages/admin/orderDetail/index?id=' + id + '&goname=look'
					});
				}
			},
			// 立即核销
			codeChange: function() {
				let self = this
				let ref = /^[0-9]*$/;
				if (!this.verify_code) return self.$util.Tips({
					title: '请输入核销码'
				});
				if (!ref.test(this.verify_code)) return self.$util.Tips({
					title: '请输入正确的核销码'
				});
				self.$util.Tips({
					title: '查询中'
				});
				setTimeout(() => {
					orderVerific(this.verify_code, this.auth)
						.then(res => {
							let auth = res.data.auth;
							self.orderInfo = res.data
							// self.iShidden = true
							if (res.data.is_order_code) {
								if (res.data.product_type) {
									uni.navigateTo({
										url: '/pages/admin/writeOffCard/index?id=' + res.data.data[0].id
									})
								} else {
									if (res.data.data[0].status == 2) {
										uni.navigateTo({
											url: '/pages/admin/offOrderResult/index?id=' + res.data.data[0].order_id
										})
									} else {
										uni.navigateTo({
											url: '/pages/admin/distribution/scanning/detail/index?auth=' + auth + '&id=' + res.data.data[0].id + '&let=' + res.data.data.length +
												'&code=' +
												this
												.verify_code
										})
									}
								}
							} else {
								if (res.data.product_type) {

								} else {
									if (res.data.data.length) {
										// 跳转多订单核销页
										uni.navigateTo({
											url: '/pages/admin/distribution/scanning/index?auth=' + auth + '&code=' + this.verify_code
										})
									} else {
										// 跳转结果页
										uni.navigateTo({
											url: '/pages/admin/offOrderResult/index'
										})
									}
								}
							}
						})
						.catch(res => {
							self.verify_code = ''
							return self.$util.Tips({
								title: res
							});
						});
				}, 800);
			},
			// 扫码核
			scanCode() {
				var self = this;
				// #ifdef MP || APP
				uni.scanCode({
					success(res) {
						let path = decodeURIComponent(res.path);
						self.verify_code = path.split('code=')[1];
						self.codeChange();
					},
				})
				// #endif
				//#ifdef H5
				this.$wechat.wechatEvevt('scanQRCode', {
					needResult: 1,
					scanType: ["qrCode", "barCode"]
				}).then(res => {
					let result = res.resultStr;
					if (result.includes(',')) {
						result = result.split(",")[1]
					}
					this.verify_code = result
					this.codeChange();
				});
				//#endif

			},

			/**
			 * 确定销码
			 */
			confirm: function() {
				let self = this
				orderVerific(this.verify_code, 1)
					.then(res => {
						self.verify_code = ''
						self.iShidden = false
						self.$util.Tips({
							title: res.msg
						});
					})
					.catch(res => {
						self.$util.Tips({
							title: res
						});
					});
			},
			/**
			 * 取消
			 */
			cancel: function() {
				this.iShidden = false
			},
			onTab(value) {
				this.tabOn = value;
			}
		}
	}
</script>

<style lang="scss">
	.OrderCancellation {
		position: absolute;
		width: 100%;
		overflow: hidden;
	}

	.OrderCancellation .header {
		position: absolute;
		top: 0;
		left: -25%;
		width: 150%;
		height: 660rpx;
		border-bottom-right-radius: 100%;
		border-bottom-left-radius: 100%;
		background: #FFFFFF;
	}

	.OrderCancellation {
		// width: 100%;
		// height: 100%;
		// background: #fff;
		overflow: hidden;
	}

	.OrderCancellation .whiteBg {
		position: relative;
		width: 540rpx;
		height: 120rpx;
		border-radius: 60rpx;
		margin: 250rpx auto 416rpx;
		background: #F5F5F5;
	}

	.OrderCancellation .whiteBg .input {
		height: 100%;
	}

	.OrderCancellation .whiteBg .input input {
		font-size: 32rpx;
		color: #333333;
		height: 100%;
		text-align: center;
		line-height: 120rpx;
		padding: 0 104rpx;
		box-sizing: border-box;
	}

	.OrderCancellation .whiteBg .input .input-placeholder {
		color: #CCCCCC;
	}

	.OrderCancellation .whiteBg .bnt {
		position: absolute;
		top: 20rpx;
		right: 24rpx;
		width: 80rpx;
		height: 80rpx;
		border-radius: 40rpx;
		background: $primary-admin;
		text-align: center;
		line-height: 80rpx;
	}

	.OrderCancellation .whiteBg .bnt .iconfont {
		font-size: 32rpx;
		color: #FFFFFF;
	}

	.OrderCancellation .scan {
		position: relative;
		width: 400rpx;
		height: 400rpx;
		border-radius: 200rpx;
		margin: 110rpx auto 0;
		background: $primary-admin;
		box-shadow: 0rpx 4rpx 32rpx 0rpx $light-primary-admin;
		text-align: center;
		line-height: 400rpx;
	}

	.OrderCancellation .scan .iconfont {
		font-size: 120rpx;
		color: #FFFFFF;
	}

	.OrderCancellation .scan image {
		width: 100%;
		height: 100%;
		display: block;
	}

	.WriteOff {
		width: 560rpx;
		height: 800rpx;
		background-color: #fff;
		border-radius: 20rpx;
		position: fixed;
		top: 50%;
		left: 50%;
		margin-top: -400rpx;
		margin-left: -280rpx;
		z-index: 99;
		padding-top: 55rpx;
	}

	.WriteOff .pictrue {
		width: 340rpx;
		height: 340rpx;
		margin: 0 auto;
	}

	.WriteOff .pictrue image {
		width: 100%;
		height: 100%;
		display: block;
		border-radius: 10rpx;
	}

	.WriteOff .num {
		font-size: 30rpx;
		color: #666;
		margin: 28rpx 0 30rpx 0;
	}

	.WriteOff .num .see {
		font-size: 16rpx;
		color: #fff;
		border-radius: 4rpx;
		background-color: #c68937;
		padding-left: 5rpx;
		margin-left: 12rpx;
	}

	.WriteOff .num .see .iconfont {
		font-size: 15rpx;
	}

	.WriteOff .tip {
		font-size: 36rpx;
		color: #282828;
		text-align: center;
		border-top: 1px dashed #ccc;
		padding-top: 40rpx;
		position: relative;
	}

	.WriteOff .tip:after {
		content: "";
		position: absolute;
		width: 25rpx;
		height: 25rpx;
		border-radius: 50%;
		background-color: #7f7f7f;
		right: -12.5rpx;
		top: -12.5rpx;
	}

	.WriteOff .tip:before {
		content: "";
		position: absolute;
		width: 25rpx;
		height: 25rpx;
		border-radius: 50%;
		background-color: #7f7f7f;
		left: -12.5rpx;
		top: -12.5rpx;
	}

	.WriteOff .sure {
		font-size: 32rpx;
		color: #fff;
		text-align: center;
		line-height: 82rpx;
		height: 82rpx;
		width: 460rpx;
		border-radius: 41rpx;
		margin: 40rpx auto 0 auto;
		background-image: linear-gradient(to right, #f67a38 0%, #f11b09 100%);
		background-color: $primary-admin;
	}

	.WriteOff .cancel {
		line-height: 82rpx;
		color: #999;
		height: 82rpx;
		width: 460rpx;
		border-radius: 41rpx;
		margin: 10rpx auto 0 auto;
		text-align: center;
	}

	.WriteOff .cancel {}

	.views {
		font-size: 18rpx;
		background: #C68937;
		border-radius: 4px;
		color: #fff;
		padding: 5rpx 2rpx 5rpx 8rpx;
		margin-left: 10rpx;
	}

	.views-jian {
		font-size: 10px;
	}

	.tabbar-box {
		width: 516rpx;
		margin: 276rpx auto 0;

		.item {
			position: relative;
			flex: 1;
			z-index: 5;
			min-width: 0;
			height: 80rpx;
			border-radius: 40rpx 0 0 40rpx;
			overflow: hidden;

			&:last-child {
				border-radius: 0 40rpx 40rpx 0;

				.bg {
					height: 68rpx;
					top: 0;
					border-radius: 12rpx 0 0 12rpx;
					transform-origin: center top;
					transform: perspective(80rpx) rotateX(10deg) translateX(20rpx);
				}


			}

			&.on {
				.bg {
					background: $primary-admin;
				}

				.inner {
					color: #FFFFFF;
				}
			}
		}

		.bg {
			position: absolute;
			bottom: 0;
			z-index: 2;
			width: 100%;
			height: 68rpx;
			border-radius: 0 12rpx 12rpx 0;
			background: #FFFFFF;
			transform-origin: center bottom;
			transform: perspective(80rpx) rotateX(-10deg) translateX(-20rpx);
		}

		.inner {
			position: absolute;
			z-index: 3;
			width: 260rpx;
			height: 80rpx;
			text-align: center;
			line-height: 80rpx;
			font-weight: 500;
			font-size: 28rpx;
			color: #333333;
		}

		.iconfont {
			vertical-align: middle;
			margin-right: 16rpx;
			font-size: 32rpx;
		}
	}
</style>