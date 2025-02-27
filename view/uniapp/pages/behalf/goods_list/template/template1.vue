<template>
	<view>
		<view class="flex-col bg--w111-fff" :style="{ height: windowHeight + 'px' }">
			<view :style="{paddingTop: sysHeight + 'px'}" class="text--w111-999">
				<view class="h-80 px-32 flex-y-center">
					<text class="iconfont icon-ic_leftarrow fs-40 mr-16" @tap="goBack"></text>
					<!-- #ifdef MP -->
					<view class="w-468 h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32">
					<!-- #endif -->
					<!-- #ifndef MP -->
					<view class="flex-1 h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32">
					<!-- #endif -->
						<text class="iconfont icon-ic_search fs-28"></text>
						<input class="flex-1 h-full fs-28 pl-18" v-model="keyword" placeholder="请输入商品名称" placeholder-class="text--w111-999" @confirm="searchSubmit" />
						<!-- <text class="fs-24 pl-18">请输入商品名称</text> -->
					</view>
				</view>
			</view>
				<view class="flex border_e">
					<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-678 pl-20 pt-24 pb-24"
						show-scrollbar="false">
						<view class="inline-block mr-8" v-for="(item,index) in categoryList" :key="index"
							@tap="tapNav(index,item)">
							<view class="flex-col flex-center">
								<view :class="index == navActive ? 'active_pic' : 'scroll_pic'">
									<image :src="item.pic" mode="aspectFill"></image>
								</view>
								<view class="w-120 h-40 flex-center fs-24 text--w111-333"
									:class="index == navActive ? 'active_cate_text' : ''">{{item.cate_name}}</view>
							</view>
						</view>
					</scroll-view>
					<view class="flex-1 flex-col flex-center fs-24" @tap="openCateDrawer(true)">
						<text>全</text>
						<text>部</text>
						<image class="w-28 h-28 block mt-8" src="@/static/img/all_pic.png"></image>
					</view>
				</view>
				<view class="scroll_box flex flex-1">
					<view class="w-168 h-full bg--w111-f5f5f5">
						<scroll-view :scroll-top="0" scroll-y="true" class="h-full">
							<view class="w-168 h-96 flex-center fs-26 text--w111-666"
								v-for="(item,index) in categoryErList"
								:key="index"
								:class="[{'aside_active': index===tabClick},'aside_' + index]"
								@tap="longClick(index,item)">{{item.cate_name}}
							</view>
						</scroll-view>
					</view>
					<view class="relative w-full h-full">
						<view class="flex-1 pl-24 pr-20 abs-lt" v-if="threeCateList.length">
							<view class="flex mt-24 mb-16">
								<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-464"
									show-scrollbar="false">
									<view class="inline-block mr-16" v-for="(item,index) in threeCateList" :key="index"
										@tap="threeCateClick(index,item)">
										<view
											class="w-144 h-56 rd-30rpx bg--w111-f5f5f5 flex-center fs-24 text--w111-333"
											:class="index===threeClick?'cate_active':''">{{item.cate_name}}</view>
									</view>
								</scroll-view>
								<view class="w-56 h-56 rd-30rpx bg--w111-f5f5f5 flex-center ml-16"
									v-if="threeCateList.length"
									@tap="openCateDrawer(false)">
									<text class="iconfont icon-ic_downarrow fs-32 text--w111-333"></text>
								</view>
							</view>
						</view>
						<view class="h-96" v-if="threeCateList.length"></view>
						<view v-else class="mt-24"></view>
						<view class="pl-24 pr-20" :class="!threeCateList.length ? 'mt-24' : ''">
							<scroll-view :scroll-top="0" scroll-y="true" @scrolltolower="lower" @scroll="scroll"
								:style="{'height':scrollHeight + 'px'}">
								<!-- 小图模板 -->
								<view>
									<view class="mb-24 flex justify-between" v-for="(item,index) in tempArr" :key="index"
										@tap="goDetail(item)">
										<easy-loadimage
											:image-src="item.image"
											:borderSrc="item.activity_frame.image"
											width="176rpx"
											height="176rpx"
											borderRadius="16rpx"></easy-loadimage>
										<view class="flex-1 flex-col justify-between pl-20">
											<view class="w-full">
												<view class="line1 w-346 fs-28 text-#333 lh-40rpx">{{item.store_name}}</view>
												<view class="flex items-end flex-wrap mt-12 w-full">
													<BaseTag
														:text="label.label_name"
														:color="label.color"
														:background="label.bg_color"
														:borderColor="label.border_color"
														:circle="label.border_color ? true : false"
														:imgSrc="label.icon"
														v-for="(label, idx) in item.store_label" :key="idx"></BaseTag>
												</view>
											</view>
											<view class="flex-between-center">
												<view class="flex items-baseline">
													<baseMoney :money="item.price" symbolSize="24" integerSize="40" color="#FF7E00"
														decimalSize="24" weight>
													</baseMoney>
												</view>
												<view class="flex-center w-48 h-48 rd-30rpx bg-primary text--w111-fff"
													@tap.stop="goCartDuo(item)" v-if="item.spec_type">
													<text class="iconfont icon-ic_ShoppingCart1 fs-30"></text>
												</view>
												<view v-if="!item.spec_type">
													<view
														class="flex-center w-48 h-48 rd-30rpx bg-primary text--w111-fff "
														@tap.stop="goCartDan(item,index)">
														<text class="iconfont icon-ic_ShoppingCart1 fs-30"></text>
													</view>
												</view>
											</view>
										</view>
									</view>
								</view>
								<view v-if="!tempArr.length && !loading">
									<emptyPage :title="keyword ? '无搜索结果,换个词试试吧' : '暂无商品，看点别的吧～'"
										:src="keyword ? '/statics/images/noSearch.gif' : '/statics/images/empty-box.gif'"></emptyPage>
								</view>
								<view class="white-box"></view>
							</scroll-view>
						</view>
					</view>
				</view>
			</view>
			<view class="w-full fixed-lb pb-safe bg--w111-fff">
				<view class="w-full h-96 flex-between-center pl-28 pr-12">
					<view class="flex-y-center" @tap="getCartList(0)">
						<view class="w-96 h-96 rd-50rpx bg-primary flex-center relative cart-icon">
							<image src="@/static/img/cart_pic.png" class="w-54 h-54"></image>
							<uni-badge class="badge-style" v-if="cartData.cartList.length" :text="cartData.cartList.length"></uni-badge>
						</view>
						<view class="flex items-baseline ml-24">
							<baseMoney :money="totalPrice" symbolSize="32" integerSize="48" decimalSize="32"
								color="#333" weight></baseMoney>
							<view class="fs-24 text--w111-999 lh-34rpx pl-16" v-show="totalPrice > 0">
								查看明细<text class="iconfont icon-ic_uparrow fs-24"></text>
							</view>
						</view>
					</view>
					<view class="w-186 h-72 rd-40rpx flex-center text--w111-fff fs-26 fw-500 bg-primary mr-12"
						@tap="subOrder">去结算({{cartData.cartList.length}})</view>
				</view>
			</view>
			<view class="more_box abs-lt w-full bg--w111-fff rd-b-32rpx z-20" v-show="showCateDrawer">
				<view :style="{paddingTop: sysHeight + 'px'}">
					<view class="h-80 px-32 flex-y-center">
						<!-- #ifdef MP -->
						<view class="w-508 h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32">
						<!-- #endif -->
						<!-- #ifndef MP -->
						<view class="w-full h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32">
						<!-- #endif -->
							<text class="iconfont icon-ic_search fs-28"></text>
							<text class="fs-24 text--w111-999 pl-18">请输入商品名称</text>
						</view>
					</view>
				</view>
				<view class="pt-32 pl-30 pr-30">
					<view v-if="!topNavShow">
						<view class="fs-32 text--w111-333" v-if="categoryList.length">
							{{categoryList[navActive].cate_name}}
						</view>
						<view class="grid-column-4 grid-gap-24rpx mt-24">
							<view class="w-154 h-56 rd-30rpx flex-center fs-24 text--w111-333 bg--w111-f5f5f5"
								v-for="(item,index) in threeCateList" :key="index"
								@tap="threeCateClick(index,item)" :class="index===threeClick?'cate_active':''">
								{{item.cate_name}}
							</view>
						</view>
					</view>
					<view class="grid-column-5 grid-gap-x-22rpx grid-gap-y-32rpx" v-if="topNavShow">
						<view class="flex-col flex-center" v-for="(item,index) in categoryList" :key="index"
							@tap="tapNav(index,item)">
							<view :class="index == navActive ? 'active_pic' : 'scroll_pic'">
								<image :src="item.pic" mode="aspectFill"></image>
							</view>
							<view class="w-120 h-40 flex-center fs-24 text--w111-333"
								:class="index == navActive ? 'active_cate_text' : ''">{{item.cate_name}}</view>
						</view>
					</view>
					<view class="flex-center fs-24 text--w111-999 h-80 mt-32" @tap="closeCateDrawer">
						<text>点击收起 <text class="iconfont icon-ic_uparrow pl-4 fs-24"></text> </text>
					</view>
				</view>
			</view>
			<view class="mask" v-show="showCateDrawer" @tap="closeCateDrawer"></view>
			<cartList
				ref="cartPopup"
				:cartData="cartData"
				@closeList="closeList"
				@onSelect="selectitem"
				@onSelectAll="selectAll"
				@onDelCart="cartDelChange"
				@onCartConfirm="subOrder"
				@onCartNum="cartNumChange">
			</cartList>
			<productWindow
				:attr="attr"
				:cartButton="storeInfo.cart_button"
				@myevent="onMyEvent"
				@ChangeAttr="ChangeAttr"
				@ChangeCartNum="ChangeCartNumDuo"
				@attrVal="attrVal"
				@iptCartNum="iptCartNum"
				@goCat="goCatNum"></productWindow>
		</view>
</template>

<script>
	let windowHeight = uni.getWindowInfo().windowHeight;
	let sysHeight = uni.getWindowInfo().statusBarHeight;
	import { adminCategory, adminProList, adminCartList } from "@/api/admin.js"
	import {getAttr,postCartNum} from '@/api/store.js';
	import {mapState,mapGetters} from 'vuex';
	import {toLogin} from '@/libs/login.js';
	import skuSelect from '../../components/skuSelect.js'
	import emptyPage from '@/components/emptyPage.vue';
	import productWindow from "../../components/productWindow.vue"
	import cartList from "../../components/cartList.vue"
	export default {
		props: {
			userId:{
				type: [Number, String],
				default: 0
			},
			touristId:{
				type: Number,
				default: 0
			}
		},
		data() {
			return {
				windowHeight: windowHeight,
				showCateDrawer: false,
				sysHeight: sysHeight,
				categoryList: [],
				navActive: 0,
				categoryTitle: '',
				categoryErList: [],
				tabLeft: 0,
				isWidth: 0, //每个导航栏占位
				tabClick: 0, //导航栏被点击
				iSlong: false,
				tempArr: [],
				loading: false,
				loadend: false,
				loadTitle: '加载更多',
				page: 1,
				limit: 10,
				cid: 0, //一级分类
				sid: 0, //二级分类
				tid: 0, //三级分类
				keyword:'',
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				attr: {
					cartAttr: false,
					productAttr: [],
					productSelect: {}
				},
				productValue: [],
				attrValue: '', //已选属性
				storeName: '', //多属性产品名称
				id: 0,
				cartData: {
					cartList: [],
					iScart: false
				},
				totalPrice: 0.00,
				lengthCart: 0,
				is_vip: 0, //是否是会员
				cart_num: 0,
				storeInfo: {},
				scrollHeight: 0,
				threeCateList: [],
				threeClick: 0,
				topNavShow: true,
				selectSku: {},
				skuArr: []
			}
		},
		components: {
			productWindow,
			emptyPage,
			cartList
		},
		mixins: [skuSelect],
		computed: {
			...mapState({
				cartNum: state => state.indexData.cartNum
			}),
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
			if(this.isLogin){
				this.getCartList(1);
			}
			uni.$on('newAttrNum', (data) => {
				if (data.length) {
					this.tempArr.forEach((item) => {
						data.forEach(j => {
							if (item.id == j.id) {
								item.cart_num = j.num
							}
						})
					})
				}
			})
			uni.$on('newCartNum', (data) => {
				if (data.length) {
					this.tempArr.forEach((item) => {
						data.forEach(j => {
							if (item.id == j.product_id) {
								item.cart_num = 0
							}
						})
					})
				}
			})
		},
		methods: {
			getScrollHeight() {
				let sysH = uni.getWindowInfo().statusBarHeight;
				this.scrollHeight = windowHeight - 138 - sysH;
			},
			getAllCategory() {
				let that = this;
				adminCategory().then(res => {
					if (!res.data.length) return
					let data = res.data;
					that.categoryTitle = data[0].cate_name;
					that.cid = data[0].id;
					that.sid = 0;
					that.tid = 0;
					that.navActive = 0;
					that.tabClick = 0;
					that.categoryList = data;
					that.categoryErList = res.data[0].children ? res.data[0].children : [];
					that.threeCateList = res.data[0].children[0].children ? res.data[0].children[0].children : [];
					if (this.threeCateList.length) {
						this.threeCateList.unshift({
							cate_name: '全部商品',
							id: 0
						})
					}
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
				adminProList({
					page: that.page,
					limit: that.limit,
					type: 1,
					cid: that.cid,
					sid: that.sid,
					tid: that.tid,
					keyword:that.keyword,
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
				uni.pageScrollTo({
					duration: 0,
					scrollTop: 0
				})
				let list = this.categoryList[index];
				this.navActive = index;
				this.categoryTitle = list.cate_name;
				this.categoryErList = item.children ? item.children : [];
				this.threeCateList = [];
				if (this.categoryErList.length) {
					this.threeCateList = item.children[0].children ? item.children[0].children : [];
					if (this.threeCateList && !this.haveAll(this.threeCateList)) {
						this.threeCateList.unshift({
							cate_name: '全部商品',
							id: 0
						})
					}
				}
				this.tabClick = 0;
				this.tabLeft = 0;
				this.cid = list.id;
				this.sid = 0;
				this.tid = 0;
				this.page = 1;
				this.loadend = false;
				this.tempArr = [];
				this.productslist();
			},
			// 导航栏点击
			longClick(index, item) {
				this.tabClick = index; //设置导航点击了哪一个
				this.threeClick = 0;
				this.sid = this.categoryErList[index].id;
				this.tid = 0;
				this.threeCateList = item.children && item.children.length ? item.children : [];

				if (this.threeCateList && this.threeCateList.length && !this.haveAll(this.threeCateList)) {
					this.threeCateList.unshift({
						cate_name: '全部商品',
						id: 0
					})
				}
				this.page = 1;
				this.loadend = false;
				this.tempArr = [];
				this.productslist();
			},
			threeCateClick(index, item) {
				this.threeClick = index;
				this.tid = item.id;
				this.page = 1;
				this.loadend = false;
				this.tempArr = [];
				this.productslist();
			},
			searchSubmit(){
				this.page = 1;
				this.loadend = false;
				this.tempArr = [];
				this.productslist();
			},
			deepClone(obj) {
				let newObj = Array.isArray(obj) ? [] : {}
				if (obj && typeof obj === "object") {
					for (let key in obj) {
						if (obj.hasOwnProperty(key)) {
							newObj[key] = (obj && typeof obj[key] === 'object') ? this.deepClone(obj[key]) : obj[key];
						}
					}
				}
				return newObj
			},
			haveAll(arr) {
				let cloneMap = this.deepClone(arr),
					list = [];
				cloneMap.forEach(item => {
					list.push(item.id);
				})
				return list.includes(0) ? true : false
			},
			// 生成订单；
			subOrder() {
				let that = this,
					list = that.cartData.cartList,
					ids = [];
				if (list.length) {
					list.forEach(item => {
						if (item.attrStatus && item.status) {
							ids.push(item.id)
						}
					});
					uni.navigateTo({
						url: `/pages/behalf/order_confirm/index?cartId=${ids.join(',')}&uid=${this.userId}&news=0`
					});
					that.cartData.iScart = false;
				} else {
					return that.$util.Tips({
						title: '请选择产品'
					});
				}
			},
			// 计算总价；
			getTotalPrice() {
				let that = this,
					list = that.cartData.cartList,
					totalPrice = 0.00;
				list.forEach(item => {
					if (item.attrStatus && item.status) {
						totalPrice = that.$util.$h.Add(totalPrice, that.$util.$h.Mul(item.cart_num, item.truePrice));
					}
				})
				that.$set(that, 'totalPrice', totalPrice);
			},
			getCartList(iSshow) {
				let that = this;
				adminCartList(this.userId).then(res => {
					res.data.map(item=>{
						this.$set(item,'select',false);
					})
					that.$set(that.cartData, 'cartList', res.data);
					if (res.data.length) {
						that.$set(that.cartData, 'iScart', iSshow ? false : !that.cartData.iScart);
					} else {
						that.$set(that.cartData, 'iScart', false);
					}
					that.getTotalPrice();
				})
			},
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
			// 去详情页
			goDetail(item) {
				if(item.spec_type){
					this.goCartDuo(item);
				}else{
					this.goCartDan(item);
				}
			},
			closeCateDrawer() {
				this.showCateDrawer = false;
			},
			openCateDrawer(type) {
				this.topNavShow = type;
				this.showCateDrawer = true;
			},
			lower(e) {
				this.productslist();
			},
			scroll(e) {
				uni.$emit('scroll');
			},
			goBack(){
				uni.navigateBack()
			},
		}
	}
</script>

<style lang="scss" scoped>
	.scroll_box {
		overflow: hidden;
	}

	.aside_active {
		background-color: #fff;
		width: 100%;
		text-align: center;
		color: $primary-admin;
		font-weight: 500;
		position: relative;

		&::after {
			content: '';
			width: 6rpx;
			height: 48rpx;
			background-color: $primary-admin;
			position: absolute;
			left: 0;
			top: 50%;
			transform: translateY(-50%);
		}
	}

	.cate_active {
		color: $primary-admin;
		background: $light-primary-admin;
		border: 1rpx solid $primary-admin;
	}

	.text-primary{
		color: $primary-admin;
	}
	.bg-primary{
		background: $primary-admin;
	}

	.bg-primary-light {
		background: $light-primary-admin;
	}

	.cart-icon{
		margin-left: -4rpx;
		top: -20rpx;
	}

	.border_e {
		border-bottom: 1px solid #eee;
	}

	.active_pic {
		width: 104rpx;
		height: 104rpx;
		background-color: #fff;
		padding: 3rpx;
		border-radius: 50%;
		border: 3rpx solid $primary-admin;

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
		background: $primary-admin;
		color: #fff;
		border-radius: 20rpx;
		margin-top: 8rpx;
	}
	.badge-style {
		position: absolute;
		top: -10rpx;
		right: -20rpx;
		/deep/ .uni-badge--error {
			background-color: $primary-admin !important;
		}
	}
	.white-box{
		height: calc(300rpx + env(safe-area-inset-bottom));
	}
</style>
