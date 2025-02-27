<template>
	<view>
		<view class="coupon-list" v-if="couponsList.length">
			<view class="item-group" v-for="(item,index) in couponsList" :key="item.id">
				<view class="item acea-row" :class="{ disabled: item.is_use && item.used && item.used.is_fail }">
					<view class='money' :class="{ moneyGray: item.used && item.used.is_fail }">
						<view>
							<text v-if="item.coupon_type == 1">￥</text>
							<text class='num'>{{item.coupon_type == 1?item.coupon_price:parseFloat(item.coupon_price)/10}}</text>
							<text v-if="item.coupon_type == 2">折</text>
						</view>
						<view class="pic-num" v-if="item.use_min_price > 0">满{{ item.use_min_price | money }}元可用</view>
						<view class="pic-num" v-else>无门槛券</view>
					</view>
					<view class="text-wrap acea-row row-middle">
						<view class="text">
							<view class="condition">
								<view class="name line2">
									<text>{{item.coupon_title}}</text>
								</view>
							</view>
							<view class='data acea-row row-between-wrapper'>
								<view v-if="item.coupon_time">领取后{{item.coupon_time}}天内有效</view>
								<view v-else>{{item.start_use_time}}-{{item.end_use_time}}</view>
							</view>
							<view class="look-wrap">
								<view class="look" @click="openRule(index)">
									{{ item.applicable_type ? (item.applicable_type == 1 ? '品类' : '商品') : '通用' }}优惠券<text v-if="item.rule">丨查看用券规则</text>
									<text v-if="item.rule" :class="['iconfont', item.ruleShow ? 'icon-ic_uparrow' : 'icon-ic_downarrow']"></text>
								</view>
							</view>
						</view>
						<!-- is_fail:1为失效；0为可用 -->
						<view class="bnt" v-if="!item.is_use" @click="setCouponReceive(item.id)">立即领取</view>
						<view class="bnt" v-else-if="item.used && item.used.is_fail">已失效</view>
						<view class="bnt" v-else @click="useCoupon(item)">去使用</view>
					</view>
				</view>
				<view v-if="item.ruleShow" class="rules">
					<view v-for="(ruleItem, idx) in item.rules" :key="idx">{{ ruleItem }}</view>
				</view>
			</view>
		</view>
		<view class='px-20 mt-20' v-if="!couponsList.length && !loading">
			<emptyPage title="暂无优惠券，去看点别的吧～" src="/statics/images/noCoupon.gif"></emptyPage>
		</view>
		<home></home>
	</view>
</template>

<script>
	import {
		memberCouponsList
	} from '@/api/user.js';
	import {
		setCouponReceive
	} from '@/api/api.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import dayjs from '@/plugin/dayjs/dayjs.min.js';
	import colors from '@/mixins/color.js';
	import {
		HTTP_REQUEST_URL
	} from '@/config/app';
	import {
		mapGetters
	} from "vuex";
	import emptyPage from '@/components/emptyPage.vue';
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
				imgHost: HTTP_REQUEST_URL,
				page: 1,
				limit: 15,
				loadend: false,
			};
		},
		filters: {
			format(value) {
				if (!value) return ''
				return dayjs(value * 1000).format('YYYY-MM-DD');
			},
			money(value) {
				if (!value) return '0'
				return parseFloat(value);
			}
		},
		computed: mapGetters(['isLogin']),
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV) {
						// #ifndef MP
						this.getUseCoupons();
						// #endif
					}
				},
				deep: true
			}
		},
		onLoad() {
			if (this.isLogin) {
				this.getUseCoupons();
			}
		},
		onShow() {
			uni.removeStorageSync('form_type_cart');
			if (!this.isLogin) {
				toLogin()
			}
		},
		methods: {
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
				if (this.loadend) {
					return;
				}
				memberCouponsList(this.page, this.limit).then(res => {
					that.loading = true;
					res.data.forEach(item => {
						item.ruleShow = false;
						// item.rules = item.rule.split('\n');
					});
					this.page += 1;
					this.loadend = res.data.length < this.limit;
					this.couponsList = [...this.couponsList, ...res.data];
				})
			},
			// 领取优惠券
			setCouponReceive(id) {
				setCouponReceive(id).then(res => {
					this.$util.Tips({
						title: '领取成功'
					});
					for (let i = 0; i < this.couponsList.length; i++) {
						if (this.couponsList[i].id == id) {
							this.couponsList[i].is_use = true;
						}
					}
				}).catch(err => {
					this.$util.Tips({
						title: err
					});
				});
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
			openRule(index) {
				this.couponsList[index].ruleShow = !this.couponsList[index].ruleShow
			},
		},
		onReachBottom() {
			this.getUseCoupons();
		},
		onPageScroll(object) {
			uni.$emit('scroll');
		},
	}
</script>

<style scoped>
	.coupon-list .moneyGray.item .text .data .bnt {
		background: #B5B5B5;
	}

	.coupon-list .moneyGray.item .money {
		color: #7D7D7D;
	}

	.coupon-list .moneyGray.item .text {
		background: linear-gradient(-90deg, #DADADA 0%, #E9E9E9 100%);
	}

	.coupon-list .moneyGray.item .text .condition {
		border-color: #F0F0F0;
	}

	.coupon-list .moneyGray.item .text .condition {
		color: #7D7D7D;
	}

	.coupon-list .moneyGray.item .text .data {
		color: #999999;
	}

	.moneyGray .condition .line-title {
		border-color: #7D7D7D;
		background: #EFEFEF;
		color: #7D7D7D;
	}

	.coupon-list .item-group {
		margin-bottom: 20rpx;
	}

	.coupon-list .item-group .rules {
		padding: 42rpx 24rpx 24rpx;
		border-radius: 0 0 24rpx 24rpx;
		margin-top: -18rpx;
		background: linear-gradient(180deg, #F7F7F7 0%, #FFFFFF 100%);
		font-size: 20rpx;
		line-height: 28rpx;
		color: #999999;
	}

	.coupon-list .item {
		margin-bottom: 0;
	}

	.coupon-list .item .money {
		position: relative;
		border-radius: 24rpx 0 0 24rpx;
		background-image: radial-gradient(circle at left, #F5F5F5 16rpx, #32302D 0, #584834 100%);
		font-family: Regular;
	}

	.coupon-list .item .money::after {
		content: "";
		position: absolute;
		top: 0;
		bottom: 0;
		right: 0;
		width: 6rpx;
		background-image: radial-gradient(circle at 6rpx, #FFFFFF 6rpx, transparent 6rpx);
		background-size: 6rpx 18rpx;
	}

	.coupon-list .item.disabled .money {
		background-image: radial-gradient(circle at left, #F5F5F5 16rpx, #CCCCCC 0);
		color: #FFFFFF;
	}

	.coupon-list .item.disabled .bnt {
		color: #FFFFFF;
	}

	.money {
		display: flex;
		flex-direction: column;
		justify-content: center;
	}

	.pic-num {
		margin-top: 8rpx;
		font-size: 24rpx;
	}

	.coupon-list .item {
		background-color: transparent;
	}

	.coupon-list .item .text-wrap {
		flex: 1;
		padding-right: 20rpx;
		border-radius: 0 24rpx 24rpx 0;
		background-color: #FFFFFF;
	}

	.coupon-list .item .text .condition {
		display: flex;
		align-items: center;
	}

	.coupon-list .item .text .condition .name {
		font-size: 28rpx;
		font-weight: 500;
	}

	.coupon-list .item .text .condition .pic {
		width: 30rpx;
		height: 30rpx;
		display: block;
		margin-right: 10rpx;
		display: inline-block;
		vertical-align: middle;
	}

	.condition .line-title {
		width: 70rpx;
		height: 32rpx !important;
		line-height: 30rpx;
		text-align: center;
		box-sizing: border-box;
		background: #FEF7EC;
		border: 1px solid #EEC181;
		opacity: 1;
		border-radius: 20rpx;
		font-size: 18rpx !important;
		color: #EEC181;
		margin-right: 12rpx;
		text-align: center;
		display: inline-block;
		vertical-align: middle;
	}

	.condition .line-title.bg-color-huic {
		border-color: #BBB;
		color: #bbb;
		background-color: #F5F5F5;
	}

	.coupon-list .item .bnt {
		background: linear-gradient(90deg, #584834 0%, #32302D 100%);
	}

	.coupon-list .item.disabled .bnt {
		background: #CCCCCC;
	}

	.look-wrap {
		margin-top: 20rpx;
	}

	.look-wrap .look {
		font-size: 20rpx;
		line-height: 28rpx;
		color: #999999;
	}

	.look-wrap .look {
		font-size: 20rpx;
		line-height: 28rpx;
		color: #999999;
	}

	.look-wrap .look .iconfont {
		margin-left: 6rpx;
		font-size: 20rpx;
	}
</style>