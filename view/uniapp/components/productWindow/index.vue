<template>
	<view>
		<view class="product-window"
			:class="(attr.cartAttr === true ? 'on' : '') + ' ' + (iSbnt?'join':'') + ' ' + (iScart?'joinCart':'')"
			>
			<view class="textpic acea-row">
				<view class="pictrue" @click="showImg()">
					<image :src="attr.productSelect.image"></image>
					<view class="icon flex-center" v-if="fangda">
						<view class="iconfont icon-ic_enlarge"></view>
					</view>
				</view>
				<view class="pl-24 pt-24 flex-col">
					<view class="flex-y-center" v-if="attr.productSelect.integral">
						<image src="../../static/img/mall05.png" class="w-32 h-32"></image>
						<text class="lh-40rpx font-num fs-40 SemiBold pl-8">{{attr.productSelect.integral}}</text>
						<text class="fs-28 lh-40rpx px-8 text--w111-666">+</text>
						<baseMoney :money="attr.productSelect.price" symbolSize="28" integerSize="40" decimalSize="28" color="var(--view-theme)" weight></baseMoney>
					</view>
					<baseMoney
					:money="attr.productSelect.price"
					symbolSize="32"
					integerSize="48"
					decimalSize="32"
					incolor="var(--primary-theme-con)"
					weight v-else></baseMoney>
					<view class="inline-block h-48 lh-48rpx text-center rd-24rpx bg-color fs-24 text--w111-fff px-20 mt-16"
						v-if="type == 0">
						预估到手 <text class="fs-28 fw-600 pl-8">¥{{attr.productSelect.pay_price}}</text>
					</view>
					<view class="mt-12 fs-24 text--w111-999" v-if="type == 1 || type == 3">限量:{{ attr.productSelect.quota_show || 0 }}</view>
					<view class="mt-12 fs-24 text--w111-999" v-else>库存:{{ attr.productSelect.stock || attr.productSelect.product_stock || 0 }}</view>
				</view>
				<view class="iconfont icon-ic_close1" @click="closeAttr"></view>
			</view>
			<view class="mt-36">
				<scroll-view scroll-y="true" :style="'max-height: '+windowHeight+'rpx'" >
					<view class="productWinList">
						<view class="item" v-for="(item, indexw) in attr.productAttr" :key="indexw">
							<view v-show="item.is_pic == 0">
								<view class="fs-28 fw-500 px-32">{{ item.attr_name }}</view>
								<view class="listn acea-row row-middle">
									<view class="itemn" :class="item.index === itemn.attr ? 'active' : ''"
										v-for="(itemn, indexn) in item.attr_value" @click="tapAttr(indexw, indexn)"
										:key="indexn">
										{{ itemn.attr }}
									</view>
								</view>
							</view>
							<view v-show="item.is_pic == 1">
								<view class="flex-between-center">
									<view class="fs-28 fw-500 px-32">{{ item.attr_name }}</view>
									<view class="pr-32 fs-24 text--w111-666 flex-y-center" v-show="gridShow == 1" @tap="toggleGridAttr(0)"> 
										<text class="iconfont icon-a-ic_Imageandtextsorting fs-28"></text> 
										<text class="pl-6">列表</text>
									</view>
									<view class="pr-32 fs-24 text--w111-666 flex-y-center" v-show="gridShow == 0" @tap="toggleGridAttr(1)">
										<text class="iconfont icon-a-ic_Picturearrangement fs-28"></text> 
										<text class="pl-6">宫格</text>
									</view>
								</view>
								<view class="pl-32 mt-32" v-show="gridShow == 1">
									<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-686"
										show-scrollbar="false">
										<view class="inline-block mr-12" 
											v-for="(itemn, indexn) in item.attr_value" 
											:key="indexn"
											@click="tapAttr(indexw, indexn)">
											<view class="grid-item-box" :class="item.index === itemn.attr ? 'grid-active' : ''">
												<view class="w-full h-192 relative">
													<image class="w-full h-192 block" :src="itemn.pic" mode="aspectFill"></image>
													<view class="proview-icon flex-center" @tap.stop="proviewImg(itemn.pic)">
														<text class="iconfont icon-ic_enlarge fs-24 text--w111-fff"></text>
													</view>
												</view>
												<view class="flex-1 bg--w111-f5f5f5 break_word tname line1 flex-center fs-24">{{itemn.attr}}</view>
											</view>
										</view>
									</scroll-view>
								</view>
								<view class="listn acea-row row-middle" v-show="gridShow == 0">
									<view class="cell-itemn flex-between-center" 
										:class="item.index === itemn.attr ? 'active' : ''"
										v-for="(itemn, indexn) in item.attr_value" :key="indexn"
										@click="tapAttr(indexw, indexn)">
										<image :src="itemn.pic" class="w-48 h-48 rd-50-p111-"></image>
										<text class="pl-8">{{ itemn.attr }}</text>
									</view>
								</view>
							</view>
						</view>
					</view>
				</scroll-view>
				<view class="cart px-30 flex-between-center mt-24" v-if="type != 'setMeal' && type !='points'">
					<view class="fs-28">数量</view>
					<view class="carnum flex-y-center">
						<template v-if="type == 0">
							<text class="font-num fs-22" v-if="storeInfo.min_qty && !storeInfo.is_limit">（商品{{storeInfo.min_qty}}{{storeInfo.unit_name}}起购）</text>
							<text class="font-num fs-22" v-else-if="!storeInfo.min_qty && storeInfo.is_limit && storeInfo.limit_num > 0">(商品限购{{storeInfo.limit_num}}{{storeInfo.unit_name}})</text>
							<text class="font-num fs-22" v-else-if="storeInfo.min_qty && storeInfo.is_limit && storeInfo.limit_num > 0">(商品起购{{storeInfo.min_qty}}{{storeInfo.unit_name}} 限购{{storeInfo.limit_num}}{{storeInfo.unit_name}})</text>
						</template>
						<view class="item reduce flex-center"
							:class="{'on': reduceDisabled}" @click="CartNumDes">
							<text class="iconfont icon-ic_Reduce fs-24"></text>
						</view>
						<view class='item num flex-y-center'>
							<input type="number" :disabled="isDisabled" v-model="attr.productSelect.cart_num"
								@input="bindCode(attr.productSelect.cart_num)"></input>
						</view>
						<view class="item plus flex-center"
							:class="{'on': addDisabled }" 
							@click="CartNumAdd">
							<text class="iconfont icon-ic_increase fs-24"></text>
						</view>
					</view>
				</view>
			</view>
			<view v-if="isExtends">
				<view class="joinBnt bg-gray" v-if="attr.productSelect.stock <= 0">已售罄</view>
				<view class="joinBnt bg-color" v-else @click="cartConfirm">确定</view>
			</view>
			<view class="joinBnt bg-color"
				v-if="iSbnt && attr.productSelect.product_stock>0 &&attr.productSelect.quota>0" @click="goCat">我要参团
			</view>
			<view class="joinBnt on"
				v-else-if="(iSbnt && attr.productSelect.quota<=0)||(iSbnt &&attr.productSelect.product_stock<=0)">已售罄
			</view>
			<view class="joinBnt bg-color" v-if="iScart && attr.productSelect.stock" @click="goCat">确定</view>
			<view class="joinBnt on" v-else-if="iScart && !attr.productSelect.stock">已售罄</view>
		</view>
		<view class="mask z-25" @touchmove.stop.prevent="moveHandle" :hidden="attr.cartAttr === false" @click="closeAttr"></view>
	</view>
</template>

<script>
	let windowHeight = (uni.getWindowInfo().windowHeight*(3/4)-238)*2;
	export default {

		props: {
			attr: {
				type: Object,
				default: () => {}
			},
			storeInfo: {
				type: Object,
				default: () => {}
			},
			limitNum: {
				type: Number,
				value: 0
			},
			isShow: {
				type: Number,
				value: 0
			},
			iSbnt: {
				type: Number,
				value: 0
			},
			iSplus: {
				type: Number,
				value: 0
			},
			iScart: {
				type: Number,
				value: 0
			},
			is_vip: {
				type: Number,
				value: 0
			},
			type: {
				type: [Number, String],
				default: 0
			},
			fangda: {
				type: Boolean,
				default: true
			},
			isExtends:{
				type: Boolean,
				default: false
			},
			showFooter:{
				type:Boolean,
				default: false
			}
		},
		data() {
			return {
				windowHeight:windowHeight,
				gridShow: 1,
			};
		},
		computed:{
			reduceDisabled(){
				if(this.type == 0 && this.storeInfo && this.storeInfo.min_qty){
					return this.storeInfo.min_qty == this.attr.productSelect.cart_num
				}else{
					return this.attr.productSelect.cart_num <= 1
				}
			},
			addDisabled(){
				if(this.type == 0 && this.storeInfo && this.storeInfo.is_limit && this.storeInfo.limit_num){
					return this.storeInfo.limit_num == this.attr.productSelect.cart_num
				}else{
					return this.attr.productSelect.cart_num >= this.attr.productSelect.stock;
				}
			},
			isDisabled(){
				if(this.type ==0 && (this.storeInfo.min_qty || this.storeInfo.limit_num)){
					return true
				}else false
			}
		},
		methods: {
			toggleGridAttr(type){
				this.gridShow = type;
			},
			goCat() {
				this.$emit('goCat');
			},
			bindCode(e) {
				this.$emit('iptCartNum', this.attr.productSelect.cart_num);
			},
			closeAttr() {
				this.$emit('myevent');
			},
			CartNumDes() {
				if(this.reduceDisabled) return
				this.$emit('ChangeCartNum', false);
			},
			CartNumAdd() {
				if(this.addDisabled) return
				this.$emit('ChangeCartNum', true);
			},
			tapAttr: function(indexw, indexn) {
				let that = this;
				that.$emit("attrVal", {
					indexw: indexw,
					indexn: indexn
				});
				this.$set(this.attr.productAttr[indexw], 'index', this.attr.productAttr[indexw].attr_values[indexn]);
				let value = that
					.getCheckedValue()
					.join(",");
				that.$emit("ChangeAttr", value);

			},
			//获取被选中属性；
			getCheckedValue: function() {
				let productAttr = this.attr.productAttr;
				let value = [];
				for (let i = 0; i < productAttr.length; i++) {
					for (let j = 0; j < productAttr[i].attr_values.length; j++) {
						if (productAttr[i].index === productAttr[i].attr_values[j]) {
							value.push(productAttr[i].attr_values[j]);
						}
					}
				}
				return value;
			},
			showImg() {
				this.$emit('getImg');
			},
			cartConfirm(){
				this.$emit('onConfirm');
			},
			proviewImg(img){
				uni.previewImage({
					current: 0,
					urls: [img]
				});

			}
		}
	}
</script>

<style scoped lang="scss">
	.vip-money {
		color: #282828;
		font-size: 28rpx;
		font-weight: 700;
		margin-left: 6rpx;
	}

	.vipImg {
		width: 56rpx;
		height: 20rpx;
		margin-left: 6rpx;

		image {
			width: 100%;
			height: 100%;
			display: block;
		}
	}

	.product-window {
		position: fixed;
		bottom: 0;
		width: 100%;
		left: 0;
		background-color: #fff;
		z-index: 100;
		border-radius: 40rpx 40rpx 0 0;
		transform: translate3d(0, 100%, 0);
		transition: all 0.3s ease-in-out;
		padding-bottom: constant(safe-area-inset-bottom); ///兼容 IOS<11.2/
		padding-bottom: env(safe-area-inset-bottom);///兼容 IOS>11.2/
	}

	.product-window.on {
		transform: translate3d(0, 0, 0);
	}

	.product-window.join {
		padding-bottom: 30rpx;
	}

	.product-window.joinCart {
		padding-bottom: 30rpx;
		z-index: 4000;
	}

	.product-window .textpic {
		padding: 0 32rpx;
		margin-top: 48rpx;
		position: relative;
	}

	.product-window .textpic .pictrue {
		width: 180rpx;
		height: 180rpx;
		position: relative;
		.icon{
			width: 30rpx;
			height: 30rpx;
			background-color: rgba(0,0,0,0.4);
			border-radius: 4rpx;
			position: absolute;
			bottom: 8rpx;
			right: 8rpx;
			text-align: center;
			line-height: 23rpx;
			.iconfont{
				color: #fff;
				font-size: 20rpx;
			}
		}
	}

	.product-window .textpic .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 16rpx;
	}

	.product-window .textpic .text {
		width: 410rpx;
		font-size: 32rpx;
		color: #202020;
	}

	.product-window .textpic .text .money {
		font-size: 24rpx;
		margin-top: 40rpx;
		.icon{
			display: inline-block;
			font-size: 16rpx;
			font-weight: normal;
			background: #FF9500;
			color: #fff;
			border-radius: 18rpx;
			padding: 2rpx 6rpx;
			margin-left: 10rpx;
			.iconfont{
				font-size: 16rpx;
				margin-right: 4rpx;
				color: #fff;
			}
		}
	}

	.product-window .textpic .icon-ic_close1 {
		position: absolute;
		right: 30rpx;
		top: -5rpx;
		font-size: 35rpx;
		color: #8a8a8a;
	}
	.product-window .productWinList .item~.item {
		margin-top: 36rpx;
	}

	.product-window .productWinList .item .listn {
		padding: 0 32rpx 0 16rpx;
	}

	.product-window .productWinList .item .listn .itemn {
		// height: 56rpx;
		line-height: 56rpx;
		border: 1rpx solid #F2F2F2;
		font-size: 24rpx;
		color: #333;
		padding: 0 20rpx;
		border-radius: 28rpx;
		margin: 24rpx 0 0 16rpx;
		background-color: #F2F2F2;
		word-break: break-all;
	}

	.product-window .productWinList .item .active {
		color: var(--view-theme) !important;
		background: var(--view-minorColorT) !important;
		border-color: var(--view-theme) !important;
	}

	.product-window .productWinList .item .listn .itemn.limit {
		color: #999;
		text-decoration: line-through;
	}

	.product-window .cart .carnum view {
		width: 84rpx;
		text-align: center;
		height: 100%;
		line-height: 54rpx;
		color: #282828;
		font-size: 45rpx;
	}

	.product-window .cart .carnum .reduce {
		border-right: 0;
		border-radius: 6rpx 0 0 6rpx;
		line-height: 48rpx;
		font-size: 60rpx;
	}

	.product-window .cart .carnum .reduce.on {
		// border-color: #e3e3e3;
		color: #DEDEDE;
	}

	.product-window .cart .carnum .plus {
		border-left: 0;
		border-radius: 0 6rpx 6rpx 0;
		line-height: 46rpx;
	}

	.product-window .cart .carnum .plus.on {
		// border-color: #e3e3e3;
		color: #dedede;
	}

	.product-window .cart .carnum .num {
		background: rgba(242, 242, 242, 1);
		color: #282828;
		font-size: 28rpx;
		border-radius: 4rpx;
	}

	.product-window .joinBnt {
		font-size: 28rpx;
		width: 710rpx;
		height: 80rpx;
		border-radius: 40rpx;
		text-align: center;
		line-height: 80rpx;
		color: #fff;
		margin: 74rpx auto 12rpx;
	}

	.product-window .joinBnt.on {
		background-color: #bbb;
		color: #fff;
	}
	.mt-74{
		margin-top: 74rpx;
	}
	.join_cart{
		background-color: var(--view-bntColor);
	}
	.bg-gray{
		background-color: #CCCCCC;
	}
	.z-25{
		z-index: 25;
	}
	.w-686{
		width: 686rpx;
	}
	.grid-item-box{
		width: 196rpx;
		border: 2rpx solid #F5F5F5;
		border-radius: 8rpx;
		image{
			border-radius: 8rpx 8rpx 0 0;
		}
		.tname{
			width: 196rpx;
			height: 58rpx;
			border-radius: 0 0 8rpx 8rpx ;
			padding: 0 12rpx;
		}
	}
	.grid-active{
		border: 2rpx solid var(--view-theme);
		.tname{
			color: var(--view-theme);
			background: var(--view-minorColorT);
		}
	}
	.cell-itemn{
		line-height: 56rpx;
		border: 1rpx solid #F2F2F2;
		font-size: 24rpx;
		color: #333;
		padding: 0 20rpx 0 4rpx;
		border-radius: 30rpx;
		margin: 24rpx 0 0 16rpx;
		background-color: #F2F2F2;
		word-break: break-all;
	}
	.proview-icon{
		position: absolute;
		top: 8rpx;
		right: 8rpx;
		width: 36rpx;
		height: 36rpx;
		background: #DDDDDD;
		border-radius: 50%;
	}
</style>
