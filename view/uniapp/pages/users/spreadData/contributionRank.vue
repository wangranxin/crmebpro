<template>
	<view>
		<view class="top-card w-full relative" :style="[topCardStyle]">
			<NavBar 
				class="TopBar"
				titleText="用户贡献榜"
				textSize="34rpx" 
				:isScrolling="isScrolling"
				iconColor="#333333" 
				textColor="#333333" 
				showBack></NavBar>
			<view class="h-100 flex-around-center fs-28 text--w111-666 relative z-20">
				<text class="w-112 text-center" :class="{'tab-active': contributeType == 'order_count'}" @tap="toggleContribute('order_count')">订单数</text>
				<text class="w-112" :class="{'tab-active': contributeType == 'order_price'}" @tap="toggleContribute('order_price')">订单金额</text>
				<text class="w-112 text-center" :class="{'tab-active': contributeType == 'brokerage'}" @tap="toggleContribute('brokerage')">收益</text>
			</view>
			<view class="abs-lb w-full h-192 gradient-white"></view>
		</view>
		<view class="px-20 w-full relative z-10 empty-card" :style="[cardTop]">
			<view class="w-full bg--w111-fff rd-24rpx">
				<view class="py-32" v-if="contributeData.length">
					<view class="flex-between-center fs-22 lh-30rpx text--w111-666 pl-38 mb-28">
						<text class="w-44">排名</text>
						<text class="w-168">用户</text>
						<text class="w-72">订单数</text>
						<text class="w-108">订单金额</text>
						<text class="w-108">收益</text>
					</view>
					<view class="flex-between-center rank-cell fs-22 lh-30rpx text--w111-666 pl-38" 
						v-for="(item,index) in contributeData" :key="index">
						<view class="w-44">
							<text class="SemiBold rank-1" v-show="index == 0">1</text>
							<text class="SemiBold rank-2" v-show="index == 1">2</text>
							<text class="SemiBold rank-3" v-show="index == 2">3</text>
							<text class="SemiBold text--w111-ccc" v-show="index > 2">{{ index + 1 }}</text>
						</view>
						<view class="w-168 flex-y-center">
							<image class="w-44 h-44 rd-50-p111- block mr-8" src="https://test-pro30.crmeb.net/uploads/attach/2024/09/11/f9cda09d2cae3a4bd6a3db8e0c36e246.jpg"></image>
							<view class="w-116 line1">{{ item.nickname || '微信用户' }}</view>
						</view>
						<text class="w-72">{{ item.total_order_count }}</text>
						<text class="w-108">¥{{ item.total_order_price }}</text>
						<text class="w-108">¥{{ item.total_brokerage }}</text>
					</view>
				</view>
				<view class="py-32 empty-card" v-else>
					<emptyPage title="暂无贡献榜单数据～" src="/statics/images/noOrder.gif"></emptyPage>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import colors from '@/mixins/color.js';
	import { spreadContributeApi } from "@/api/user.js"
	import { HTTP_REQUEST_URL } from '@/config/app.js';
	import { toLogin } from '@/libs/login.js';
	import { mapGetters } from 'vuex';
	import home from '@/components/home/index.vue';
	import NavBar from "@/components/NavBar.vue";
	import emptyPage from '@/components/emptyPage.vue'
	let sysHeight = uni.getWindowInfo().statusBarHeight;
	export default {
		data() {
			return {
				sysHeight,
				isScrolling: false,
				berHeight: 40,
				active: 0,
				params:{
					page:1,
					limit:10
				},
				loading: false,
				contributeType: 'order_count',
				contributeData:[],
			}
		},
		mixins: [colors],
		components: { home, NavBar, emptyPage },
		computed: {
			...mapGetters(['isLogin']),
			topCardStyle(){
				return {
					height: 145 + this.berHeight + 'px'
				}
			},
			cardTop(){
				return {
					'top': `-${this.sysHeight + 96}px`
				}
			},
		},
		onPageScroll(option) {
			uni.$emit('scroll');
			if (option.scrollTop > 50) {
				this.isScrolling = true;
			} else if (option.scrollTop < 50) {
				this.isScrolling = false;
			}
		},
		onLoad() {
			setTimeout(()=>{
				this.getHeight();
			},100)
			if(this.isLogin){
				this.getContribute();
			}else{
				toLogin();
			}
		},
		methods: {
			changeTab(type){
				this.active = type;
			},
			getHeight() {
				let that = this;
			    uni.createSelectorQuery().select('.TopBar').boundingClientRect(function(rect) {
			      this.berHeight = rect.height;
			    }).exec();
			},
			toggleContribute(val){
				this.contributeType = val;
				this.params.page = 1;
				this.contributeData = [];
				this.loading = false;
				this.getContribute();
			},
			getContribute(){
				if (this.loading) return;
				this.loading = true;
				spreadContributeApi(this.contributeType, this.params).then(res=>{
					let list = res.data;
					let loading = list.length < this.params.limit;
					this.contributeData = this.contributeData.concat(list);
					this.params.page++;
					this.loading = loading;
				}).catch((err) => {
					this.$util.Tips({
						title: err
					});
				});
			},
		},
		onReachBottom() {
			this.getContribute();
		},
	}
</script>

<style lang="scss">
.top-card{
	background: #FFEEC7;
}
.tab-active{
	font-weight: bold;
	color: #333333;
	position: relative;
	&:after{
		content: "";
		position: absolute;
		left: 50%;
		bottom: -12rpx;
		transform: translateX(-50%);
		width: 40rpx;
		height: 10rpx;
		background-image: url("../static/rank_tab_fix.png");
		background-size: cover;
	}
	
}
.gradient-white{
	background: linear-gradient( 180deg, rgba(245,245,245,0) 0%, #F5F5F5 100%);
}
.content-card{
	top: -192rpx;
}
.rank-1{
	color: #E73E05;
}
.rank-2{
	color: #E26F04;
}
.rank-3{
	color: #F4BB00;
}
.rank-cell ~ .rank-cell{
	margin-top: 40rpx;
}
.empty-card{
	/* #ifdef MP */
	margin-top: 192rpx;
	/* #endif */
}
.w-44{
	width: 44rpx;
}
</style>
