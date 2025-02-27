<template>
  <!-- 砍价列表模块 -->
	<view class="box">
		<view class='bargain-list'>
			<view class='header' :style="'background-image: url('+imgHost+'/statics/images/bargain-list-bg.png);'">
				<NavBar titleText=" "
					textSize="34rpx" 
					bagColor="transparent"
					iconColor="#ffffff" 
					textColor="#ffffff" 
					showBack></NavBar>
			</view>
			<view class='list' v-if="bargainList.length">
				<block v-for="(item,index) in bargainList" :key="index">
					<view class='item flex justify-between relative'
						@tap="openSubscribe('/pages/activity/goods_bargain_details/index?id='+ item.id +'&spid='+ userInfo.uid)">
						<easy-loadimage
						mode="aspectFit"
						:image-src="item.image"
						width="240rpx"
						height="240rpx"
						borderRadius="20rpx"></easy-loadimage>
						<view class='w-410 fs-28 flex-col justify-between'>
							<view>
								<view class='w-410 lh-40rpx line2'>
									<text v-if="item.brand_name" class="brand-tag">{{ item.brand_name }}</text>
								{{item.title}}</view>
								<view class='fs-22 fw-500 mt-8 red'>
									<text class='iconfont icon-ic_fire fs-22 mr-8'></text>
									{{item.people}}人正在参与
								</view>
							</view>
							<view class="flex-between-center">
								<view>
									<view class='flex items-baseline red'>
										<text class="fs-22 lh-30rpx fw-500">最低: </text>
										<baseMoney :money="item.min_price" symbolSize="24" integerSize="40" decimalSize="24" incolor="#E93323" weight></baseMoney>
									</view>
									<view class="otPrice">￥{{item.price}}</view>
								</view>
								<view class='cutBnt w-144 h-56 rd-30rpx flex-center fs-24 text--w111-fff'>参与砍价</view>
							</view>
						</view>
					</view>
				</block>
			</view>
			<view class="list no-goods" v-if="!bargainList.length && !loading">
				<image :src="imgHost + '/statics/images/noActivity.gif'" mode=""></image>
				<text class="fs-26 text--w111-999 pt-16">暂无砍价商品，去看看其他商品吧～</text>
			</view>
		</view>
	</view>
</template>
<script>
	let app = getApp();
	import { HTTP_REQUEST_URL } from '@/config/app';
	import { getBargainList } from '@/api/activity.js';
	import { openBargainSubscribe } from '@/utils/SubscribeMessage.js';
	import { getUserInfo } from '@/api/user.js';
	import { toLogin } from '@/libs/login.js';
	import { mapGetters } from "vuex";
	import emptyPage from '@/components/emptyPage.vue';
	import NavBar from "@/components/NavBar.vue"
	export default {
		data() {
			return {
				bargainList: [],
				page: 1,
				limit: 20,
				loading: false,
				loadend: false,
				userInfo: {},
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				imgHost:HTTP_REQUEST_URL,
				// #ifdef MP
				getHeight: this.$util.getWXStatusHeight()
				// #endif
			};
		},
		components:{ emptyPage, NavBar },
		computed: mapGetters(['isLogin']),
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV) {
						// #ifndef MP
						this.getUserInfo();
						// #endif
					}
				},
				deep: true
			}
		},
		onLoad: function(options) {
			this.getBargainList();
			if (this.isLogin) {
				this.getUserInfo();
			}
		},
		onShow(){
			uni.removeStorageSync('form_type_cart');
		},
		onPageScroll(object) {
			uni.$emit('scroll');
		},
		methods: {
			// 授权关闭
			authColse: function(e) {
				this.isShowAuth = e
			},
			goarrow(){
				uni.navigateBack()
			},
			/*
			 * 获取用户信息
			 */
			getUserInfo: function() {
				let that = this;
				getUserInfo().then(res => {
					that.$set(that, 'userInfo', res.data);
				});
			},
			openSubscribe: function(e) {
				if(this.isLogin){
					let page = e;
					// #ifndef MP
					uni.navigateTo({
						url: page
					});
					// #endif
					// #ifdef MP
					uni.showLoading({
						title: '正在加载',
					})
					openBargainSubscribe().then(res => {
						uni.hideLoading();
						uni.navigateTo({
							url: page,
						});
					}).catch((err) => {
						uni.hideLoading();
					});
					// #endif
				}else{
					toLogin()
				}
			},
			getBargainList: function() {
				let that = this;
				if (that.loadend) return;
				if (that.loading) return;
				that.loading = true;
				getBargainList({
					page: that.page,
					limit: that.limit
				}).then(function(res) {
					that.$set(that, 'bargainList', that.bargainList.concat(res.data));
					that.$set(that, 'page', that.page + 1);
					that.$set(that, 'loadend', that.limit > res.data.length);
					that.$set(that, 'loading', false);
				}).catch(res => {
					that.loading = false;
				});
			},
		},
		onReachBottom: function() {
			this.getBargainList();
		},
	}
</script>

<style lang="scss">
	.no-goods{
		text-align: center;
		padding-bottom: 60rpx!important;
		color: #999;
		image{
			width: 440rpx;
			height: 360rpx;
			margin: 40rpx auto 0 auto;
			display: block;
		}
	}
	
	.bargain-list .header {
		background-repeat: no-repeat;
		background-size: 100% 100%;
		width: 750rpx;
		height: 926rpx;
		padding-top: 55rpx;
		box-sizing: border-box;
		
		.iconfont{
			color: #fff;
			font-size: 40rpx;
			margin-left: 15rpx;
		}
	}

	.bargain-list .list {
		background-color: #fff;
		border-radius: 24rpx;
		margin: -510rpx 20rpx 66rpx 20rpx;
		padding: 32rpx 20rpx;
	}
	.brand-tag{
		background-color: #e93323 !important;
	}
	.item~.item{
		margin-top: 40rpx;
	}
	
	.red{
		color: #E93323;
	}
	
	.otPrice{
		font-size: 26rpx;
		font-family: Regular;
		color: #999;
		text-decoration: line-through;
		margin-top: 4rpx;
	}

	.cutBnt {
		background: linear-gradient(90deg, #FF7931 0%, #E93323 100%);
	}

	.bargain-list .list .load {
		font-size: 24rpx;
		height: 85rpx;
		text-align: center;
		line-height: 85rpx;
	}
</style>