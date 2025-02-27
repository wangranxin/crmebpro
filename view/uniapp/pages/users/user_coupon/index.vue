<template>
	<!-- 我的有优惠券模块 -->
	<view :style="colorStyle">
		<view class="header">
			<view class="navbar acea-row">
				<view class="item acea-row row-center-wrapper" :class="{ on: navOn === 0 }" @click="onNav(0)">
					<view>待使用({{ not_used }}张)</view>
				</view>
				<view class="item acea-row row-center-wrapper" :class="{ on: navOn === 1 }" @click="onNav(1)">
					<view>已使用({{ used }}张)</view>
				</view>
				<view class="item acea-row row-center-wrapper" :class="{ on: navOn === 2 }" @click="onNav(2)">
					<view>已失效({{ expired }}张)</view>
				</view>
			</view>
			<view class="subnavbar px-32 flex-y-center">
				<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-full" show-scrollbar="false">
					<view class="inline-block h-52 rd-28rpx px-28 lh-52rpx text-center fs-24 bg--w111-f5f5f5 mr-24"
						v-for="(item, index) in navList" :key="index"
						:class="type === item.type ? 'on' : ''"
						@tap="onSubNav(item.type)">{{item.name}}</view>
				</scroll-view>
				
			</view>
		</view>
		<view class="header-shadow"></view>
		<view class='coupon-list'>
			<view class="item" :class="{ gray: navOn }" v-for='(item,index) in couponsList' :key="index">
				<view class="item-box acea-row" :class="{svip: item.receive_type === 4}">
					<view class="moneyCon acea-row row-center-wrapper">
						<view class='money'>
							<BaseMoney 
							v-if="item.coupon_type==1" 
							:money="item.coupon_price" 
							symbolSize="28" 
							integerSize="52" 
							isCoupon 
							:color="item.is_use ? '#cccccc' : item.receive_type === 4 ? '#FACC7D' : '#ffffff'"></BaseMoney>
							<view v-else-if="item.coupon_type==2" class="SemiBold">{{ parseFloat(item.coupon_price)/10 }}<text class="fs-28 pingfang pl-4">折</text></view>
							<view class="pic-num" v-if="item.use_min_price > 0">满{{item.use_min_price}}可用</view>
							<view class="pic-num" v-else>无门槛券</view>
						</view>
					</view>
					<view class="text acea-row row-column row-center">
						<view class='condition'>
							<view class="name line2">
								{{ item.coupon_title }}
							</view>
						</view>
						<view class='data acea-row row-between-wrapper'>
							<view>{{item.add_time}}-{{item.end_time}}</view>
						</view>
						<view class="text-bottom acea-row row-middle">
							<view v-if="item.applicable_type === 0">通用优惠券</view>
							<view v-else-if="item.applicable_type === 1">品类优惠券</view>
							<view v-else-if="item.applicable_type === 3">品牌优惠券</view>
							<view v-else>商品优惠券</view>
							<view v-show="item.rule">丨</view>
							<view class="" v-show="item.rule" @click="openRule(index)">查看用券规则<text class="iconfont icon-ic_downarrow"></text></view>
						</view>
					</view>
					<view class="btn-box acea-row row-middle">
						<view class="btn disabled" v-if="navOn == 1">已使用</view>
						<view class="btn disabled" v-else-if="navOn == 2">已失效</view>
						<view class="btn" v-else @click="useCoupon(item)">去使用</view>
					</view>
				</view>
				<view class="rule-desc" v-if="item.ruleShow">
					<view v-for="(ruleItem, idx) in item.rules" :key="idx">{{ ruleItem }}</view>
				</view>
			</view>
		</view>
		<view class='px-20' v-if="!couponsList.length && !loading">
			<emptyPage title="暂无优惠券，去看点别的吧～" src="/statics/images/noCoupon.gif"></emptyPage>
		</view>
	</view>
</template>

<script>
	import {
		getCouponsNum,
		getUserCoupons
	} from '@/api/api.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import {
		mapGetters
	} from "vuex";
	import colors from '@/mixins/color.js';
	import emptyPage from '@/components/emptyPage.vue';
	import {
		HTTP_REQUEST_URL
	} from '@/config/app';
	export default {
		components: {
			emptyPage
		},
		mixins: [colors],
		data() {
			return {
				couponsList: [],
				loading: false,
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				navOn: 0,
				type: '',
				page: 1,
				limit: 15,
				finished: false,
				imgHost: HTTP_REQUEST_URL,
				expired: 0,
				not_used: 0,
				used: 0,
				navList: [
					{name: '全部',type: ''},
					{name: '快过期',type: -1},
					{name: '通用券',type: 0},
					{name: '品类券',type: 1},
					{name: '商品券',type: 2},
					{name: '品牌券',type: 3},
				],
			};
		},
		computed: mapGetters(['isLogin']),
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV) {
						// #ifdef H5 || APP-PLUS
						this.getUseCoupons();
						// #endif
					}
				},
				deep: true
			}
		},
		onLoad() {
			if (this.isLogin) {
				this.getCouponsNum();
				this.getUseCoupons();
			} else {
				toLogin()
			}
		},
		onShow() {
			uni.removeStorageSync('form_type_cart');
		},
		onReachBottom() {
			this.getUseCoupons();
		},
		methods: {
			getCouponsNum() {
				getCouponsNum().then(res => {
					this.used = res.data.used;
					this.not_used = res.data.not_used;
					this.expired = res.data.expired;
				});
			},
			onNav: function(type) {
				this.navOn = type;
				if (this.navOn) {
					this.type = 0;
				} else {
					this.type = -1;
				}
				this.couponsList = [];
				this.page = 1;
				this.finished = false;
				this.getUseCoupons();
			},
			onSubNav(type) {
				this.type = type;
				this.couponsList = [];
				this.page = 1;
				this.finished = false;
				this.getUseCoupons();
			},
			useCoupon(item) {
				let url = '';
				// 通用券
				if (item.category_id == 0 && item.product_id == '' && item.brand_id == 0) {
					url = '/pages/goods/goods_list/index?title=默认'
				}
				// 品类券
				if (item.category_id != 0) {
					if (item.category_type == 1) {
						url = '/pages/goods/goods_list/index?cid=' + item.category_id + '&title=' + item.category_name
					} else {
						url = '/pages/goods/goods_list/index?sid=' + item.category_id + '&title=' + item.category_name
					}
				}
				//商品券
				if (item.product_id != '') {
					let arr = item.product_id.split(',');
					let num = arr.length;
					if (num == 1) {
						url = '/pages/goods_details/index?id=' + item.product_id
					} else {
						url = '/pages/goods/goods_list/index?productId=' + item.product_id + '&title=默认'
					}
				}
				//品牌券
				if (item.brand_id != 0) {
					url = '/pages/goods/goods_list/index?brandId=' + item.brand_id + '&title=默认'
				}
				uni.navigateTo({
					url: url
				});
			},
			/**
			 * 授权回调
			 */
			onLoadFun: function() {
				this.getUseCoupons();
				this.isShowAuth = false;
			},
			// 授权关闭
			authColse: function(e) {
				this.isShowAuth = e
			},
			/**
			 * 获取领取优惠券列表
			 */
			getUseCoupons: function() {
				let that = this;
				if (that.loading || that.finished) {
					return;
				}
				that.loading = true;
				uni.showLoading({
					title: '正在加载…'
				});
				getUserCoupons(this.navOn, {
					type: this.type,
					page: this.page,
					limit: this.limit
				}).then(res => {
					that.loading = false;
					uni.hideLoading();
					res.data.forEach(item => {
						item.ruleShow = false;
						item.rules = item.rule ? item.rule.split('\n') : [];
					});

					that.couponsList = that.couponsList.concat(res.data);
					that.finished = res.data.length < that.limit;
					that.page += 1;
				}).catch(err => {
					that.loading = false;
					uni.showToast({
						title: err,
						icon: 'none'
					});
				});
			},
			openRule(index) {
				this.couponsList[index].ruleShow = !this.couponsList[index].ruleShow
			},
		}
	}
</script>


<style lang="scss" scoped>
	.money {
		display: flex;
		flex-direction: column;
		justify-content: center;
	}

	.pic-num {
		color: #ffffff;
		font-size: 24rpx;
	}

	.coupon-list .item .text .condition {
		display: flex;
		align-items: center;
	}

	.coupon-list .item .text .condition .name {
		font-size: 28rpx;
		line-height: 40rpx;
		font-weight: 500;
	}

	.coupon-list .item .text .condition .pic {
		width: 30rpx;
		height: 30rpx;
		display: block;
		margin-right: 10rpx;
		display: inline-block;
		vertical-align: sub;
	}

	.condition .line-title {
		/* width: 70rpx; */
		padding: 0 12rpx;
		height: 32rpx !important;
		/* #ifndef APP */
		line-height: 28rpx;
		/* #endif */
		/* #ifdef APP */
		line-height: 30rpx;
		/* #endif */
		text-align: center;
		box-sizing: border-box;
		background: var(--view-minorColorT);
		border: 1px solid var(--view-theme);
		opacity: 1;
		border-radius: 20rpx;
		font-size: 18rpx !important;
		color: var(--view-theme);
		margin-right: 12rpx;
		text-align: center;
		display: inline-block;
		vertical-align: middle;
	}

	.condition .title {
		vertical-align: middle;
	}

	.condition .line-title.bg-color-huic {
		border-color: #BBB !important;
		color: #bbb !important;
		background-color: #F5F5F5 !important;
	}

	.header-shadow {
		height: 194rpx;
	}

	.header {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		background-color: #FFFFFF;
		z-index: 9;
	}

	.subnavbar {
		height: 92rpx;

		.on {
			border-color: var(--view-theme);
			background-color: var(--view-minorColorT);
			font-weight: 500;
			font-size: 24rpx;
			color: var(--view-theme);
		}
	}

	.navbar {
		height: 82rpx;

		.item {
			flex: 1;
			position: relative;
			font-size: 26rpx;
			color: #666666;

			.number {
				height: 34rpx;
				padding: 0 16rpx;
				border-radius: 17rpx;
				margin-top: 8rpx;
				background-color: transparent;
				font-size: 24rpx;
				line-height: 34rpx;
				color: #666666;
			}

			&.on {
				font-weight: 500;
				font-size: 28rpx;
				color: var(--view-theme);

				.number {
					background-color: var(--view-theme);
					color: #FFFFFF;
				}
			}
		}
	}

	.coupon-list {
		padding: 0 20rpx;
		margin-top: 0;

		.item {
			height: auto;
			background: none;
		}

		.item-box {
			height: 170rpx;
			border-radius: 24rpx;
			background: #FFFFFF;
		}

		.moneyCon {
			border-radius: 24rpx 0 0 24rpx;
			background: linear-gradient(270deg, var(--view-gradient) 0%, var(--view-theme) 100%);
			color: #FFFFFF;
		}

		.money {
			position: relative;
			background-image: radial-gradient(circle at left, #F5F5F5 16rpx, transparent 0);
			color: #FFFFFF;
			font-size: 52rpx;
			font-family: Regular;

			&::after {
				content: "";
				position: absolute;
				top: 0;
				bottom: 0;
				right: 0;
				width: 6rpx;
				background-image: radial-gradient(circle at 6rpx, #FFFFFF 6rpx, transparent 6rpx);
				background-size: 6rpx 18rpx;
			}
		}

		.pic-num {
			margin-top: 8rpx;
			font-weight: 400;
			font-size: 22rpx;
			line-height: 28rpx;
		}

		.text-bottom {
			margin-top: 20rpx;
			font-size: 20rpx;
			line-height: 28rpx;
			color: #999999;

			.iconfont {
				margin-left: 6rpx;
				font-size: 20rpx;
			}
		}

		.btn-box {
			padding: 0 20rpx;
		}

		.btn {
			height: 52rpx;
			padding: 0 24rpx;
			border-radius: 26rpx;
			background-color: var(--view-minorColorT);
			font-weight: 500;
			font-size: 22rpx;
			line-height: 52rpx;
			color: var(--view-theme);
		}

		.disabled {
			background-color: #ccc;
			color: #fff;
		}

		.rule-desc {
			padding: 42rpx 24rpx 24rpx;
			border-radius: 0 0 24rpx 24rpx;
			background: linear-gradient(180deg, #F7F7F7 0%, #FFFFFF 100%);
			font-size: 20rpx;
			line-height: 28rpx;
			color: #999999;
		}

		.gray {
			.moneyCon {
				background: #CCCCCC;
			}

			.item-right {
				background-color: #CCCCCC;
				color: #FFFFFF;
			}
		}
	}
	.svip .btn{
		background: linear-gradient(90deg, #584834 0%, #32302D 100%);
		color: #FACC7D;
	}
	.svip .moneyCon{
		background: linear-gradient(90deg, #584834 0%, #32302D 100%);
		color: #FACC7D;
	}
	.svip .pic-num{
		color: #FACC7D;
	}
</style>