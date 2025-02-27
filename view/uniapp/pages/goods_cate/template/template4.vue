<template>
	<view>
		<view class="fixed-lt w-full bg_mask z-900" :style="{ paddingTop: statusBarHeight + 'px',height: 80 + statusBarHeight + 'px'}">
			<view class="px-20 h-80 flex mt-12 text--w111-999">
				<view class="w-162 h-58 rd-30rpx bg--w111-f5f5f5 flex-center fs-32">
					<image class="w-32 h-32" src="@/static/img/drop_more.png" @tap="openDrower()"></image>
					<text class="text--w111-b3b3b3 px-16">|</text>
					<image class="w-32 h-32" src="@/static/img/cart_icon.png" @tap="goCart()"></image>
				</view>
				<!-- #ifdef MP -->
				<view class="w-344 h-58 rd-30rpx bg--w111-f5f5f5 ml-14 px-32 flex-y-center" @tap="goSearch">
				<!-- #endif -->
				<!-- #ifndef MP -->
				<view class="flex-1 h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32 ml-14" @tap="goSearch">
				<!-- #endif -->
						<text class="iconfont icon-ic_search fs-28"></text>
						<text class="fs-24 pl-18">请输入商品名称</text>
					</view>
				</view>
			</view>
			<!-- #ifdef MP || APP-PLUS -->
			<view :style="{'height': statusBarHeight + 'px'}"></view>
			<view class="px-20 p-b-20">
			<!-- #endif -->
				<!-- #ifndef MP -->
				<view class="p-20">
				<!-- #endif -->
					<view class="rd-24rpx pro_card" v-for="(item,index) in tempArr" :key="index"
						@click="goDetail(item)">
						<easy-loadimage
						mode="widthFix"
						:image-src="item.image"
						:borderSrc="item.activity_frame.image"
						width="100%"
						borderRadius="24rpx 24rpx 0 0"></easy-loadimage>
						<view class="rd-b-24rpx bg--w111-fff p-24">
							<view class="flex-between-center">
								<view class="flex-y-center">
									<baseMoney :money="item.price" symbolSize="36" integerSize="52" decimalSize="36"
										weight></baseMoney>
									<view class="svip-label ml-8" v-if="item.vip_price && item.vip_price > 0">
										<text class="text">SVIP</text>
										<text class="px-8 Regular">¥{{item.vip_price}}</text>
									</view>
								</view>
								<view class="fs-24 lh-34rpx text--w111-999">{{item.sales}}人都在买</view>
							</view>
							<view class="flex-between-center mt-24">
								<view class="w-422 lh-40rpx fs-28 text--w111-333 fw-500 line2">
									<text v-if="item.brand_name" class="brand-tag">{{ item.brand_name }}</text>{{item.store_name}}
								</view>
								<view class="flex" v-if="item.cart_button">
									<view class="w-88 h-64 rd-l-32rpx bg-primary-light flex-center"
										@click.stop="goCartDuo(item)" v-if="item.spec_type">
										<text class="iconfont icon-ic_ShoppingCart1 fs-40 text-primary-con"></text>
									</view>
									<view class="w-88 h-64 rd-l-32rpx bg-primary-light flex-center"
										@click.stop="goCartDan(item,index)" v-else>
										<text class="iconfont icon-ic_ShoppingCart1 fs-40 text-primary-con"></text>
									</view>
									<view class="w-112 h-64 rd-r-32rpx bg-color flex-center text--w111-fff fs-24">购买</view>
								</view>
								<view class="w-112 h-64 rd-32rpx bg-color flex-center text--w111-fff fs-24" v-else>购买</view>
							</view>
						</view>
					</view>
					<view v-if="!tempArr.length && !loading" :style="{'margin-top': 40 + statusBarHeight + 'px'}">
						<emptyPage title="暂无商品，去看点别的吧～" ></emptyPage>
					</view>
					<view class="h-100"></view>
				</view>
				<base-drawer mode="right"
					:visible="showFilterDrawer"
					background-color="transparent"
					mask maskClosable
					@close="closeDrawer">
					<view class="drawer_box bg--w111-fff h-full">
						<view :style="{height:fixedTop + 'px'}"></view>
						<view class="h-80 flex-center fs-34 fw-500 text--w111-333">筛选</view>
						<view v-if="level == 3">
							<view class="flex">
								<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-full pl-20 pt-24 pb-24"
									show-scrollbar="false">
									<view class="inline-block mr-8" v-for="(item,index) in categoryList" :key="index"
										@click="tapNav(index,item)">
										<view class="flex-col flex-center">
											<view :class="index == navActive ? 'active_pic' : 'scroll_pic'">
												<image :src="item.pic" mode="aspectFill"></image>
											</view>
											<view class="w-120 h-40 flex-center fs-24 text--w111-333"
												:class="index == navActive ? 'active_cate_text' : ''">{{item.cate_name}}
											</view>
										</view>
									</view>
								</scroll-view>
							</view>
							<view v-for="(item,index) in categoryErList" :key="index">
								<view class="activity_box pt-24 pb-24 pl-32 pr-32"
									v-if="item.children && item.children.length">
									<view class="flex-between-center">
										<view class="fs-28 text--w111-333 fw-500">{{item.cate_name}}</view>
										<view class="fs-20 text--w111-999" v-if="item.children && item.children.length > 3"
											@tap="openShowMore(item)">
											<text>{{item.showMore ? '收起' : '展开'}}</text>
											<text class="iconfont fs-20" :class="item.showMore ? 'icon-ic_uparrow' : 'icon-ic_downarrow'"></text>
										</view>
									</view>
									<view class="grid-column-3 box_gap mt-24" v-show="item.showMore">
										<view class="h-56 rd-28rpx bg--w111-f5f5f5 flex-center fs-24 text--w111-333"
											v-for="cate in item.children" :key="cate.id"
											:class="tid == cate.id ? 'cate_active' : ''" @tap="threeCateClick(cate)">
											{{cate.cate_name}}</view>
									</view>
									<view class="grid-column-3 box_gap mt-24" v-show="!item.showMore">
										<view class="h-56 rd-28rpx bg--w111-f5f5f5 flex-center fs-24 text--w111-333"
											v-for="cate in sliceArr(item.children)" :key="cate.id"
											:class="tid == cate.id ? 'cate_active' : ''" @tap="threeCateClick(cate)">
											{{cate.cate_name}}</view>
									</view>
								</view>
							</view>
						</view>
						<view v-else>
							<scroll-view :scroll-top="0" scroll-y="true"
								:style="{'height':scrollHeight + 'px'}">
								<view v-for="(item,index) in categoryList" :key="index">
									<view class="activity_box pt-24 pb-24 pl-32 pr-32">
										<view class="flex-between-center">
											<view class="fs-28 text--w111-333 fw-500">{{item.cate_name}}</view>
											<view class="fs-20 text--w111-999" v-if="item.children && item.children.length > 3" 
												@tap="openShowMore(item)">
												<text>{{item.showMore ? '收起' : '展开'}}</text>
												<text class="iconfont fs-20" :class="item.showMore ? 'icon-ic_uparrow' : 'icon-ic_downarrow'"></text>
											</view>
										</view>
										<view v-if="item.children && item.children.length">
											<view class="grid-column-3 box_gap mt-24" v-show="item.showMore">
												<view class="h-56 rd-28rpx bg--w111-f5f5f5 flex-center fs-24 text--w111-333"
													v-for="cate in item.children" :key="cate.id"
													:class="sid == cate.id ? 'cate_active' : ''" @tap="twoCateClick(cate)">
													{{cate.cate_name}}</view>
											</view>
											<view class="grid-column-3 box_gap mt-24" v-show="!item.showMore">
												<view class="h-56 rd-28rpx bg--w111-f5f5f5 flex-center fs-24 text--w111-333"
													v-for="cate in sliceArr(item.children)" :key="cate.id"
													:class="sid == cate.id ? 'cate_active' : ''" @tap="twoCateClick(cate)">
													{{cate.cate_name}}</view>
											</view>
										</view>
									</view>
								</view>
							</scroll-view>
						</view>
						<view class="h-200"></view>
						<view class="fixed-lb pb-safe bg--w111-fff w-full rd-b-40">
							<view class="px-32 flex-between-center h-112">
								<view class="w-296 h-72 rd-40rpx flex-center text-primary-con con_border bg--w111-fff"
									@click="reset">重置</view>
								<view class="w-296 h-72 rd-40rpx flex-center text--w111-fff bg-color" @tap="getList">
									确定</view>
							</view>
						</view>
					</view>
				</base-drawer>
				<productWindow
					:attr="attr" 
					:isShow='1' 
					:iSplus='1' 
					:iScart='1' 
					:is_vip="is_vip"
					:type="2"
					:storeInfo="storeInfo"
					@myevent="onMyEvent"
					@ChangeAttr="ChangeAttr" 
					@ChangeCartNum="ChangeCartNumDuo" 
					@attrVal="attrVal"
					@iptCartNum="iptCartNum" 
					@goCat="goCatNum" 
					@getImg="showImg"
					id='product-window' ></productWindow>
				<cusPreviewImg ref="cusPreviewImg" :list="skuArr" @changeSwitch="changeSwitch"></cusPreviewImg>
			</view>
		</view>
</template>

<script>
	let sysHeight = uni.getWindowInfo().statusBarHeight;
	import baseDrawer from '@/components/tui-drawer/tui-drawer.vue';
	import productWindow from '@/components/productWindow';
	import {
		getCategoryList,
		getProductslist,
		getAttr,
		postCartNum
	} from '@/api/store.js';
	import {goShopDetail} from '@/libs/order.js';
	import {toLogin} from '@/libs/login.js';
	import {mapState,mapGetters} from 'vuex';
	import skuSelect from '@/mixins/skuSelect.js';
	import emptyPage from '@/components/emptyPage.vue';
	import cusPreviewImg from '@/components/cusPreviewImg';
	export default {
		props: {
			level: {
				type: Number,
				default: 2
			},
			isFooter:{
				type: Boolean,
				default: false
			}
		},
		data() {
			return {
				statusBarHeight: sysHeight,
				showFilterDrawer: false,
				navActive: 0,
				categoryList: [],
				categoryErList: [],
				threeCateList: [],
				tempArr: [],
				loading: false,
				loadend: false,
				loadTitle: '加载更多',
				page: 1,
				limit: 10,
				cid: 0, //一级分类
				sid: 0, //二级分类
				tid: 0, //二级分类
				id: 0, //商品id
				storeName: '',
				tabClick: 0,
				attr: {
					cartAttr: false,
					productAttr: [],
					productSelect: {}
				},
				attrValue: '', //已选属性
				productValue: [],
				is_vip: 0, //是否是会员
				cart_num: 0,
				storeInfo: {},
				selectSku: {},
				skuArr: [],
				scrollHeight:0
			}
		},
		mixins: [skuSelect],
		components: {
			baseDrawer,
			productWindow,
			emptyPage,
			cusPreviewImg
		},
		computed: {
			fixedTop() {
				// #ifdef MP || APP-PLUS
				return uni.getWindowInfo().statusBarHeight
				// #endif
				// #ifdef H5
				return 20
				// #endif
			},
			...mapGetters(['isLogin', 'uid', 'cartNum'])
		},
		watch:{
			cartNum(){
				this.getCartList(1);
			}
		},
		mounted() {
			this.getAllCategory();
			setTimeout(() => {
				this.getScrollHeight();
			}, 500);
		},
		methods: {
			// 商品详情接口；
			getAttrs(id) {
				let that = this;
				getAttr(id, 0).then(res => {
					// uni.hideLoading();
					that.$set(that.attr, 'productAttr', res.data.productAttr);
					that.$set(that, 'productValue', res.data.productValue);
					that.$set(that, 'is_vip', res.data.storeInfo.is_vip);
					that.$set(that, 'storeInfo', res.data.storeInfo);
					that.skuArr = [];
					for (let key in res.data.productValue) {
						let obj = res.data.productValue[key];
						that.skuArr.push(obj)
					}
					if (!that.skuArr.length) {
						that.skuArr = [{
							image: this.storeInfo.image,
							suk: this.storeInfo.store_name,
							price: this.storeInfo.price
						}];
					}
					this.$set(this, "selectSku", that.skuArr[0]);
					that.DefaultSelect();
				})
			},
			//点击sku图片打开轮播图
			showImg(index) {
				this.$refs.cusPreviewImg.open(this.selectSku.suk)
			},
			//滑动轮播图选择商品
			changeSwitch(e) {
				let productSelect = this.skuArr[e];
				this.$set(this, 'selectSku', productSelect);
				var skuList = productSelect.suk.split(',');
				skuList.forEach((i, index) => {
					this.$set(this.attr.productAttr[index], 'index', skuList[index]);
				})
				if (productSelect) {
					this.$set(this.attr.productSelect, 'image', productSelect.image);
					this.$set(this.attr.productSelect, 'price', productSelect.price);
					this.$set(this.attr.productSelect, 'stock', productSelect.stock);
					this.$set(this.attr.productSelect, 'unique', productSelect.unique);
					this.$set(this.attr.productSelect, 'cart_num', 1);
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.$set(this, 'attrValue', productSelect.suk);
					this.$set(this, 'attrTxt', '已选择');
				}
			},
			getAllCategory() {
				let that = this;
				getCategoryList().then(res => {
					if (!res.data.length) return
					res.data.map(item=>{
						this.$set(item,'showMore',false);
						if(item.children && item.children.length){
							item.children.map(item1=>{
								this.$set(item1,'showMore',false);
							})
						}
					})
					let data = res.data;
					that.categoryTitle = data[0].cate_name;
					that.cid = data[0].id;
					that.sid = 0;
					that.tid = 0;
					that.navActive = 0;
					that.tabClick = 0;
					that.categoryList = data;
					that.categoryErList = res.data[0].children ? res.data[0].children : [];
					that.page = 1;
					that.loadend = false;
					that.tempArr = [];
					that.productslist();
				})
			},
			// 产品列表
			productslist() {
				let that = this;
				if (that.loadend) return;
				if (that.loading) return;
				that.loading = true;
				that.loadTitle = '';
				getProductslist({
					page: that.page,
					limit: that.limit,
					type: 1,
					cid: that.cid,
					sid: that.sid,
					tid: that.tid,
					is_big:1
				}).then(res => {
					let list = res.data,
						loadend = list.length < that.limit;
					that.tempArr = that.$util.SplitArray(list, that.tempArr);
					that.$set(that, 'tempArr', that.tempArr);
					that.loading = false;
					that.loadend = loadend;
					that.loadTitle = loadend ? "没有更多内容啦~" : "加载更多";
					that.page = that.page + 1;
				}).catch(err => {
					that.loading = false;
					that.loadTitle = '加载更多'
				});
			},
			tapNav(index, item) {
				let list = this.categoryList[index];
				this.navActive = index;
				this.categoryTitle = list.cate_name;
				this.categoryErList = item.children ? item.children : [];
				this.threeCateList = item.children[0].children ? item.children[0].children : [];
				this.tabClick = 0;
				this.tabLeft = 0;
				this.cid = list.id;
				this.sid = 0;
				this.tid = 0;
			},
			threeCateClick(item) {
				this.tid = item.id;
			},
			twoCateClick(item){
				this.sid = item.id;
			},
			openShowMore(item){
				item.showMore = !item.showMore;
			},
			getList() {
				this.page = 1;
				this.loadend = false;
				this.tempArr = [];
				this.productslist();
				this.showFilterDrawer = false;
				if(this.isFooter){
					this.$emit('toggleBar',true);
				}else{
					uni.showTabBar();
				}
			},
			reset() {
				this.cid = this.categoryList[0].id;
				this.sid = 0;
				this.tid = 0;
				this.navActive = 0;
				this.categoryErList = this.categoryList[0].children ? this.categoryList[0].children : [];
			},
			// 去详情页
			goDetail(item) {
				goShopDetail(item, this.uid).catch(res => {
					uni.navigateTo({
						url: `/pages/goods_details/index?id=${item.id}&fromType=1`
					});
				});
			},
			goSearch() {
				uni.navigateTo({
					url: '/pages/goods/goods_search/index'
				})
			},
			goCart(){
				uni.switchTab({
					url: '/pages/order_addcart/order_addcart'
				})
			},
			openDrower() {
				this.showFilterDrawer = true;
				if(this.isFooter){
					this.$emit('toggleBar',false);
				}else{
					uni.hideTabBar();
				}
			},
			closeDrawer() {
				this.showFilterDrawer = false;
				if(this.isFooter){
					this.$emit('toggleBar',true);
				}else{
					uni.showTabBar();
				}
			},
			sliceArr(arr){
				return arr.slice(0,3)
			},
			getScrollHeight() {
				let sysH = uni.getWindowInfo().statusBarHeight;
				// #ifdef H5
				this.scrollHeight = uni.getWindowInfo().windowHeight - 116 - sysH;
				// #endif
				// #ifdef MP-WEIXIN || APP-PLUS
				this.scrollHeight = uni.getWindowInfo().windowHeight - 60 - sysH;
				// #endif
			},
		},
	}
</script>
<style lang="scss" scoped>
	.bg_mask {
		background: linear-gradient(180deg, rgba(0, 0, 0, 0.5) 0%, rgba(0, 0, 0, 0) 100%);
	}

	.z-900 {
		z-index: 900;
	}

	.pro_card~.pro_card {
		margin-top: 20rpx;
	}

	.drawer_box {
		width: 668rpx;
		border-radius: 40rpx 0 0 40rpx;
		overflow: auto;
	}

	.box_gap {
		grid-row-gap: 24rpx;
		grid-column-gap: 26rpx;
	}

	.con_border {
		border: 1px solid var(--view-theme);
	}

	.text-primary-con {
		color: var(--view-theme);
	}

	.bg-primary-light {
		background: var(--view-minorColorT);
	}

	.p-b-20 {
		padding-bottom: 20rpx !important;
	}

	.w-598 {
		width: 598rpx;
	}

	.active_pic {
		width: 104rpx;
		height: 104rpx;
		background-color: #fff;
		padding: 3rpx;
		border-radius: 50%;
		border: 3rpx solid var(--view-theme);

		image {
			width: 100%;
			height: 100%;
			border-radius: 50%;
		}
	}

	.scroll_pic {
		image {
			width: 92rpx;
			height: 92rpx;
			border-radius: 50%;
		}
	}

	.active_cate_text {
		background: linear-gradient(90deg, var(--view-gradient) 0, var(--view-theme) 100%);
		color: #fff;
		border-radius: 20rpx;
		margin-top: 8rpx;
	}

	.cate_active {
		color: var(--view-theme);
		background: var(--view-minorColorT);
		border: 1rpx solid var(--view-theme);
	}
	.rd-b-40{
		border-radius: 0 0 0 40rpx;
	}
</style>
