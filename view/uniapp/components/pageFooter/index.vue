<template>
	<!-- 底部导航 -->
	<view v-if="showTabBar">
		<view class="fixed-lb w-full pb-safe z-3000" :style="[bgColor]">
			<view class="page-footer-wrapper">
				<view class="page-footer" :class="{
					'page-footer2': newData.navStyleConfig.tabVal == 1,
					'page-footer3': newData.navStyleConfig.tabVal == 2,
				}" id="target" :style="[componentStyle]">
					<view class="foot-item flex-1 flex-col flex-center h-96 relative" v-for="(item,index) in newData.menuList" :key="index" @click="goRouter(item)">
						<template v-if="item.link.split('?')[0] == activeRouter">
							<image v-if="newData.navStyleConfig.tabVal != 1" :src="item.imgList[0]"></image>
							<view v-if="newData.navStyleConfig.tabVal != 2" class="txt active" :style="[txtActiveColor]">{{item.name}}</view>
						</template>
						<template v-else>
							<image v-if="newData.navStyleConfig.tabVal != 1" :src="item.imgList[1]"></image>
							<view v-if="newData.navStyleConfig.tabVal != 2" class="txt" :style="[txtColor]">{{item.name}}</view>
						</template>
						<uni-badge v-if="item.link === '/pages/order_addcart/order_addcart' && cartNum>0" class="uni-badge-left-margin" :text="cartNum" absolute="rightTop">
						</uni-badge>
					</view>
				</view>
			</view>
		</view>
		<view :style="{ height: `${footerHeight}px` }"></view>
		<view class="pb-safe"></view>
	</view>
</template>

<script>
	import {mapState,mapGetters} from "vuex"
	import {getCartCounts} from '@/api/order.js';
	export default {
		name: 'pageFooter',
		props: {
			isTabBar:{
				type: Boolean,
				default: true
			},
			configData:{
				type: Object,
				default: ()=>{}
			}
		},
		computed: {
			...mapGetters(['isLogin', 'cartNum','tabBarData']),
			txtActiveColor() {
				let styleObject = {};
				if (this.newData.toneConfig && this.newData.toneConfig.tabVal) {
					styleObject['color'] = this.newData.activeTxtColor.color[0].item;
				}
				return styleObject;
			},
			txtColor() {
				let styleObject = {};
				if (this.newData.toneConfig && this.newData.toneConfig.tabVal) {
					styleObject['color'] = this.newData.txtColor.color[0].item;
				}
				return styleObject;
			},
			bgColor() {
				let styleObject = {};
				if (!this.newData.name) {
					return styleObject;
				}
				if (!this.newData.navConfig.tabVal) {
					styleObject['background'] = this.newData.bgColor.color[0].item;
				}
				return styleObject;
			},
			componentStyle() {
				let styleObject = {};
				let borderRadius = ``;
				if (!this.newData.name) {
					return styleObject;
				}
				if (this.newData.navConfig.tabVal) {
					borderRadius = `${this.newData.fillet.val * 2}rpx`;
					if (this.newData.fillet.type) {
						borderRadius =
							`${this.newData.fillet.valList[0].val * 2}rpx ${this.newData.fillet.valList[1].val * 2}rpx ${this.newData.fillet.valList[3].val * 2}rpx ${this.newData.fillet.valList[2].val * 2}rpx`;
					}
					styleObject['right'] = `${this.newData.prConfig.val * 2}rpx`;
					styleObject['bottom'] = `${this.newData.mbConfig.val * 2}rpx`;
					styleObject['left'] = `${this.newData.prConfig.val * 2}rpx`;
					styleObject['padding-top'] = `${this.newData.topConfig.val * 2}rpx`;
					styleObject['padding-bottom'] = `${this.newData.bottomConfig.val * 2}rpx`;
					styleObject['border-radius'] = borderRadius;
					styleObject['background'] = this.newData.bgColor2.color[0].item;
				} else {
					styleObject['padding-top'] = `${this.newData.topConfig.val * 2}rpx`;
					styleObject['padding-bottom'] = `${this.newData.bottomConfig.val * 2}rpx`;
					styleObject['background'] = this.newData.bgColor.color[0].item;
				}
				return styleObject;
			},
			// validConfigData() {
			//     return this.configData && Object.keys(this.configData).length > 0;
			// },
		},
		watch: {
			configData(newVal){
				if(!this.showTabBar && newVal){
					let configData = newVal;
					this.newData = configData;
					this.showTabBar = configData.effectConfig.tabVal;
				}
			},
			tabBarData(newVal){
				if(newVal){
					let configData = newVal;
					this.newData = configData;
					this.showTabBar = configData.effectConfig.tabVal;
					if (configData.effectConfig.tabVal) {
						uni.hideTabBar()
					} else {
						uni.showTabBar()
					}
					this.$emit('newDataStatus', configData.effectConfig.tabVal)
				}
			}
		// 	'newData.menuList'(newValue, oldValue) {
		// 		this.$nextTick(() => {
		// 			const query = uni.createSelectorQuery().in(this);
		// 			query.select("#target").boundingClientRect(({
		// 				height
		// 			}) => {
		// 				this.footerHeight = height;
		// 			}).exec();
		// 		});
		// 	}
		},
		created() {
			let routes = getCurrentPages(); //获取当前打开过的页面路由数组
			let curRoute = routes[routes.length - 1].route //获取当前页面路由
			this.activeRouter = '/' + curRoute
		},
		mounted() {
			this.navigationInfo();
			if (this.isLogin) {
				this.getCartNum()
			}
		},
		data() {
			return {
				newData: {},
				activeRouter: '',
				showTabBar: false,
				footerHeight: 0,
			}
		},
		methods: {
			navigationInfo() {
				//判断渲染来源是一级菜单还是微页面
				if(this.isTabBar && this.tabBarData.effectConfig){
					let configData = this.tabBarData;
					this.newData = configData;
					this.showTabBar = configData.effectConfig.tabVal;
					if (configData.effectConfig.tabVal) {
						uni.hideTabBar()
					} else {
						uni.showTabBar()
					}
					this.$emit('newDataStatus', configData.effectConfig.tabVal)
				}
				
			},
			goRouter(item) {
				var pages = getCurrentPages();
				var page = (pages[pages.length - 1]).$page.fullPath;
				if (item.link == page) return
				if (item.link == '/pages/short_video/appSwiper/index' || item.link == '/pages/short_video/nvueSwiper/index') {
					//#ifdef APP
					item.link = '/pages/short_video/appSwiper/index'
					//#endif
					//#ifndef APP
					item.link = '/pages/short_video/nvueSwiper/index'
					//#endif
				}
				uni.switchTab({
					url: item.link,
					fail(err) {
						uni.redirectTo({
							url: item.link
						})
					}
				})
			},
			getCartNum: function() {
				getCartCounts().then(res => {
					this.$store.commit('indexData/setCartNum', res.data.count + '')
				}).catch(err=>{
					console.log(err.msg);
				})
			},
		}
	}
</script>

<style scoped lang="scss">
	.page-footer-wrapper {
		position: relative;
	}
	.z-3000{
		z-index: 3000;
	}
	.page-footer {
		position: absolute;
		right: 0;
		bottom: 0;
		left: 0;
		display: flex;

		.foot-item image {
			display: block;
			height: 40rpx;
			width: 40rpx;
			margin: 0 auto;
		}

		.foot-item .txt {
			margin-top: 4rpx;
			font-size: 20rpx;
			line-height: 28rpx;
			color: #333333;

			&.active {
				color: var(--view-theme);
			}
		}
	}

	.page-footer2 .foot-item .txt {
		margin-top: 0;
		font-size: 32rpx;
		line-height: 44rpx;
		color: #333333;

		&.active {
			color: var(--view-theme);
		}
	}

	.page-footer2.float .foot-item::before,
	.page-footer3.float .foot-item::before {
		content: "";
		position: absolute;
		top: 50%;
		left: 0;
		width: 2rpx;
		height: 32rpx;
		background: #CCCCCC;
		transform: translateY(-50%);
	}

	.page-footer2.float .foot-item:first-child::before,
	.page-footer3.float .foot-item:first-child::before {
		display: none;
	}
</style>