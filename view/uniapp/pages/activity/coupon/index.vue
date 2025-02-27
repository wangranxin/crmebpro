<template>
	<view :style="colorStyle">
		<view class="w-full h-400 relative z-10" :style="{'height': (186 + sysHeight) * 2 + 'rpx', 'background' : headerBg}">
			<NavBar titleText="领券中心"
				textSize="34rpx" 
				:bagColor="pageScrollStatus ? 'var(--view-theme)' : 'transparent'"
				iconColor="#ffffff" 
				textColor="#ffffff" 
				showBack></NavBar>
			<view class="abs-box flex-col">
				<image src="../static/get_coupon_text.png" class="w-184 h-46"></image>
				<text class="fs-24 lh-34rpx text--w111-fff pt-20">天天来领券，优惠看的见</text>
			</view>
		</view>
		<view class="relative rd-t-40rpx bg--w111-f5f5f5 w-full content">
			<view class="_box flex-between-center text--w111-666 fs-30 lh-42rpx"
				:class="pageScrollStatus ? '' : 'rd-t-40rpx'"
				:style="{'top': (38 + sysHeight) * 2 + 'rpx'}">
				<text :class="current == 0 ? 'font-num fw-500' : ''" @tap="changeTab(0)">默认排序</text>
				<view class="flex-y-center" @tap="changeTab(1)">
					<text :class="current == 1 ? 'font-num fw-500' : ''">最新</text>
					<text v-show="params.timeOrder == 'desc'" class="iconfont icon-ic_down2 fs-14 ml-6 font-num"></text>
					<text v-show="params.timeOrder == 'asc'" class="iconfont icon-ic_up2 fs-14 ml-6 font-num"></text>
					<text v-show="params.timeOrder == ''" class="iconfont icon-ic_down2 fs-14 ml-6"></text>
					<!-- timeOrder -->
				</view>
				<view class="flex-y-center" @tap="changeTab(2)">
					<text :class="current == 2 ? 'font-num fw-500' : ''">价值</text>
					<text v-show="params.priceOrder == 'desc'" class="iconfont icon-ic_down2 fs-14 ml-6 font-num"></text>
					<text v-show="params.priceOrder == 'asc'" class="iconfont icon-ic_up2 fs-14 ml-6 font-num"></text>
					<text v-show="params.priceOrder == ''" class="iconfont icon-ic_down2 fs-14 ml-6"></text>
				</view>
				<view class="flex-y-center" @tap="showDrop = !showDrop">
					<text class="menu_line"></text>
					<text>筛选</text>
					<text class="iconfont icon-ic_sort pl-8"></text>
				</view>
				<view class="drop-box bg--w111-f5f5f5" v-if="showDrop">
					<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-full" show-scrollbar="false">
						<view class="inline-block h-52 rd-28rpx px-28 lh-52rpx text-center fs-24 bg--w111-fff mr-24"
							v-for="(item, index) in navList" :key="index"
							:class="params.type === item.type ? 'active-tab' : ''"
							@tap="setType(item.type)">{{item.name}}</view>
					</scroll-view>
					
				</view>
			</view>
			<view class="px-20" v-if="couponsList.length">
				<view class="relative card-item" v-for="(item,index) in couponsList" :key="index">
					<view class="card w-full h-200 bg--w111-fff rd-24rpx p-20 flex justify-between"
						:class="{svip: item.receive_type === 4}">
						<view class="flex">
							<easy-loadimage
							mode="aspectFit"
							:image-src="item.products[0].image"
							width="160rpx"
							height="160rpx"
							borderRadius="16rpx" v-if="item.products[0] && item.products[0].image"></easy-loadimage>
							<view class="w-160 h-160 rd-16rpx flex-center fs-22 text--w111-999" v-else>暂无商品</view>
							<view class="w-338 pl-24">
								<view class="w-286 h-72 lh-36rpx line2 fw-500" :class="item.is_use ? 'text--w111-ccc' : ''">{{item.title}}</view>
								<view class="fs-20 lh-28rpx mt-8" :class="item.is_use ? 'text--w111-ccc' : 'text--w111-666'"
									v-if="item.coupon_time">领取后{{item.coupon_time}}天内可用</view>
								<view class="fs-20 lh-28rpx mt-8" :class="item.is_use ? 'text--w111-ccc' : 'text--w111-666'"
									v-else>{{ item.start_time ? item.start_time + '-' : '' }}{{ item.end_time }}</view>
									<view class="flex-y-center fs-20 text--w111-999 lh-28rpx mt-20">
										<text>{{item.type | typeFilter}}</text>
										<view  v-show="item.rule" @tap="toggleRule(item)">
											<text class="pl-8"> | 查看用券规则</text>
											<text class="iconfont icon-ic_downarrow fs-20 ml-4"></text>
										</view>
									</view>
							</view>
						</view>
						<view class="flex-1 flex-col flex-center">
							<baseMoney
							:money="item.coupon_price"
							symbolSize="28"
							integerSize="44"
							decimalSize="28"
							:color="item.is_use ? '#cccccc' : item.receive_type === 4 ? '#333' : 'var(--view-theme)'"
							isCoupon
							v-if="item.coupon_type==1"></baseMoney>
							<view v-else class="fs-44 SemiBold" 
								:style="{color: item.is_use ? '#cccccc' : item.receive_type === 4 ? '#333' : 'var(--view-theme)'}"
								>{{ parseFloat(item.coupon_price) / 10 }} <text class="pingfang fs-28 pl-4">折</text></view>
							<text class="fs-20 lh-28rpx mt-8"
								:class="item.is_use ? 'text--w111-ccc' : 'font-num'" v-show="item.use_min_price == 0">无门槛券</text>
								<text class="fs-20 lh-28rpx mt-8"
									:class="item.is_use ? 'text--w111-ccc' : 'font-num'" v-show="item.use_min_price != 0">满{{item.use_min_price}}可用</text>
							<view class="w-128 h-48 rd-30rpx flex-center fs-20 bg-hui text--w111-fff mt-16" v-if="item.is_use == true">已领取</view>
							<view class="w-128 h-48 rd-30rpx flex-center fs-20 bg-hui text--w111-fff mt-16" v-else-if="item.is_use == 2">已领完</view>
							<view class="w-128 h-48 rd-30rpx flex-center fs-20 bg-gradient text--w111-fff mt-16" v-else
								@tap="getCoupon(item.id, index)">立即领取</view>
						</view>
					</view>
					<view class="rule-desc" v-html="item.rule" v-show="item.ruleShow"></view>
				</view>
			</view>
			<view class='loadingicon flex-center' v-if="couponsList.length">
				<text class='loading iconfont icon-jiazai' :hidden='loading==false'></text>{{loadTitle}}
			</view>
			<view class="px-20" v-if="!couponsList.length">
				<emptyPage title="暂无优惠券，去看点别的吧～" src="/statics/images/noCoupon.gif"></emptyPage>
			</view>
		</view>
		<view class="mask" v-if="showDrop" @tap="showDrop = false"></view>
		<home></home>
	</view>
</template>

<script>
	let sysHeight = uni.getWindowInfo().statusBarHeight;
	import { getCoupons, setCouponReceive } from '@/api/api.js';
	import emptyPage from '@/components/emptyPage.vue';
	import NavBar from "@/components/NavBar.vue"
	import { toLogin } from '@/libs/login.js';
	import { mapGetters } from "vuex";
	import colors from '@/mixins/color.js';
	import {HTTP_REQUEST_URL} from '@/config/app';
	export default {
		mixins:[colors],
		components:{ emptyPage, NavBar },
		data() {
			return {
				sysHeight:sysHeight,
				couponsList: [],
				loading: false,
				loadend: false,
				loadTitle: '加载更多', //提示语
				params:{
					page: 1,
					limit: 20,
					type: '',
					defaultOrder: 1,
					timeOrder: '',
					priceOrder: ''
				},
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				navList: [
					{name: '全部',type: ''},
					{name: '快过期',type: -1},
					{name: '通用券',type: 0},
					{name: '品类券',type: 1},
					{name: '商品券',type: 2},
					{name: '品牌券',type: 3},
				],
				count: 0,
				imgHost:HTTP_REQUEST_URL,
				pageScrollStatus:false,
				showDrop: false,
				current:0,
				price: 0,
			};
		},
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV) {
						this.getUseCoupons();
					}
				},
				deep: true
			},
		},
		computed:{
			...mapGetters(['isLogin']),
			headerBg(){
				return 'url('+this.imgHost+'/statics/images/get_coupon_bg.png'+')' + 'no-repeat center center/100% 100%, linear-gradient(90deg, var(--view-gradient) 0%, var(--view-theme) 100%)'
			}
		},
		filters:{
			typeFilter(val){
				let obj = {
					0: '通用券',
					1: '品类券',
					2: '商品券',
					3: '品牌券',
				};
				return obj[val]
			}
		},
		onPageScroll(object) {
			if (object.scrollTop > 130) {
				this.pageScrollStatus = true;
			} else if (object.scrollTop < 130) {
				this.pageScrollStatus = false;
			}
			uni.$emit('scroll');
		},
		onLoad() {
			if (this.isLogin) {
				this.getUseCoupons();
			} else {
				toLogin()
			}
		},
		/**
		 * 页面上拉触底事件的处理函数
		 */
		onReachBottom() {
			this.getUseCoupons();
		},
		methods: {
			changeTab(val){
				this.current = val;
				if(val == 0){
					this.params.defaultOrder = 1;
					this.params.timeOrder = '';
					this.params.priceOrder = '';
				}else if(val == 1){
					if(this.params.timeOrder == '') {
						this.params.timeOrder = 'asc'
					}else if (this.params.timeOrder == 'asc'){
						this.params.timeOrder = 'desc'
					}else if(this.params.timeOrder == 'desc'){
						this.params.timeOrder = ''
					}
					this.params.defaultOrder = '';
					this.params.priceOrder = '';
				} else if(val == 2){
					if(this.params.priceOrder == '') {
						this.params.priceOrder = 'asc'
					}else if (this.params.priceOrder == 'asc'){
						this.params.priceOrder = 'desc'
					}else if(this.params.priceOrder == 'desc'){
						this.params.priceOrder = ''
					}
					this.params.defaultOrder = '';
					this.params.timeOrder = '';
				}
				this.couponsList = [];
				this.loadend = false;
				this.loading = false;
				this.params.page = 1;
				this.getUseCoupons();
			},
			toggleRule(item){
				item.ruleShow = !item.ruleShow
			},
			backPage(){
				uni.navigateBack()
			},
			goDetails(item){
				uni.navigateTo({
					url: '/pages/goods_details/index?id=' + item.id
				})
			},
			getCoupon: function(id, index) {
				let that = this;
				let list = that.couponsList;
				//领取优惠券
				setCouponReceive(id).then(function(res) {
					list[index].is_use = true;
					that.$set(that, 'couponsList', list);
					that.$util.Tips({
						title: '领取成功'
					});
				}).catch(error => {
					return that.$util.Tips({
						title: error
					});
				})
			},
			/**
			 * 获取领取优惠券列表
			 */
			getUseCoupons: function() {
				let that = this
				if (this.loadend) return false;
				if (this.loading) return false;
				that.loading = true;
				that.loadTitle = '加载更多';
				getCoupons(this.params).then(res => {
					let list = res.data.list,
						loadend = list.length < that.params.limit,
						countIndex = [];
					list.map(item=>{
						this.$set(item,'ruleShow',false);
					})
					let couponsList = that.$util.SplitArray(list, that.couponsList);
					that.$set(that, 'couponsList', couponsList);
					that.loadend = loadend;
					that.loading = false;
					that.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
					that.params.page++;
				}).catch(err => {
					that.loading = false;
					that.loadTitle = '加载更多';
				});
			},
			setType(type) {
				if (this.params.type !== type) {
					this.params.type = type;
				}else{
					this.params.type = '';
				}
				this.params.page = 1;
				this.couponsList = [];
				this.loadend = false;
				this.showDrop = false;
				this.getUseCoupons();
			}
		}
	};
</script>

<style scoped lang="scss">
	.abs-box{
		width: 264rpx;
		position: absolute;
		left: 48rpx;
		bottom: 80rpx;
	}
	.content{
		top: -44rpx;
	}
	.menu_line {
		width: 1rpx;
		height: 26rpx;
		background: #ccc;
		margin: 0 32rpx 0 20rpx;
	}
	._box{
		padding: 28rpx 32rpx;
		background: #f5f5f5;
		position: sticky;
		z-index: 99;
	}
	.drop-box{
		width: 100%;
		// height: 102rpx;
		position: absolute;
		left: 0;
		top: 94rpx;
		border-radius: 0 0 40rpx 40rpx;
		z-index: 199;
		padding: 10rpx 32rpx 24rpx;
	}
	.active-tab{
		background: var(--view-minorColorT);
		color: var(--view-theme);
		border: 1px solid var(--view-theme);
	}
	.svip .font-num{
		color: #333333 !important;
	}
	.svip .bg-gradient{
		background: linear-gradient(90deg, #584834 0%, #32302D 100%);
		color: #FACC7D;
	}
	.bg-hui{
		color: #ffffff !important;
		background: #cccccc !important;
	}
	.indent{
		text-indent: 60rpx;
	}
	.card{
		background-image:
		radial-gradient(circle at 500rpx top, #f5f5f5, #f5f5f5 10rpx, transparent 11rpx),
		radial-gradient(circle at 500rpx bottom, #f5f5f5, #f5f5f5 10rpx, transparent 11rpx);
		position: relative;
		&:before{
			content: '';
			position: absolute;
			left: 500rpx;
			top: 16rpx;
			height: 168rpx;
			border-left: 2px dotted #ddd;
		}
		.name {
			width: 286rpx;
			height: 80rpx;
			line-height: 40rpx;
			color: #333;
			font-weight: 500;
			font-size: 28rpx;
		}
		.type-tag{
			display: inline-block;
			background: var(--view-minorColorT);
			color: var(--view-theme);
			border-radius: 14rpx;
			padding: 2rpx 10rpx;
			font-size: 20rpx;
			margin-right: 8rpx;
			vertical-align: middle;
		}
	}
	.card-item ~ .card-item{
		margin-top: 20rpx;
	}
	.rule-desc{
		margin-top: -16rpx;
		padding: 40rpx 24rpx 24rpx;
		white-space: pre-wrap;
		font-size: 20rpx;
		line-height: 28rpx;
		background: linear-gradient(180deg, #F7F7F7 0%, #FFFFFF 100%);
		border-radius: 0 0 24rpx 24rpx;
		color: #999;
	}
	.SemiBold{
		font-family: 'SemiBold'
	}
	.pingfang{
		font-family: PingFang SC, PingFang SC;
	}
</style>
