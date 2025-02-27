<template>
	<view>
		<!-- 分类购物车下拉列表 -->
		<view class="cartList" :class="{'on':cartData.iScart,'show-pd': hideEmpty}" @touchmove.stop.prevent="moveHandle">
			<view class="title acea-row row-between-wrapper">
				<view class="name">购物车 <text class="fs-24 text--w111-999 pl-8">(共{{cartData.cartList.length}}件商品)</text> </view>
				<view class="del acea-row row-middle" @click="subDel">
					<view class="iconfont icon-ic_delete"></view>清空
				</view>
			</view>
			<view class="list pb-safe">
				<scroll-view class="scroll-list" scroll-y="true" :style="{
					'max-height': '800rpx',
					'margin-bottom': '126rpx'
				}">
					<view class="item flex" v-for="(item,index) in cartData.cartList" :key="index">
						<view class="pictrue">
							<image v-if="item.productInfo.attrInfo" :src='item.productInfo.attrInfo.image'></image>
							<image v-else :src='item.productInfo.image'></image>
							<view class="mantle" v-if="!item.status || !item.attrStatus"></view>
						</view>
						<view class="flex-1 flex-col justify-between ml-20">
							<view class="w-full">
								<view class="lh-40rpx fs-28 text--w111-333 line2"
									:class="(item.attrStatus && item.status)?'':'on'">{{item.productInfo.store_name}}</view>
								<view class="inline-block max-w-460 h-38 lh-38rpx mt-12  bg--w111-f5f5f5  text--w111-999 rd-20rpx px-12 text-center fs-22"
									v-if="item.productInfo.spec_type && item.attrStatus">
									<view class="flex">
										<text class="line1">属性: {{item.productInfo.attrInfo.suk}}</text>
										<text class="iconfont icon-ic_downarrow fs-24 ml-12"></text>
									</view>
								</view>
								<view class="inline-block max-w-460 h-38 lh-38rpx mt-12  bg--w111-f5f5f5  text--w111-999 rd-20rpx px-12 text-center fs-22"
									v-else>
									<view class="flex">
										<text class="line1">属性: {{item.productInfo.attrInfo.suk}}</text>
										<text class="iconfont icon-ic_downarrow fs-24 ml-12"></text>
									</view>
								</view>
							</view>
							<view class="flex-between-center mt-20">
								<baseMoney :money="item.truePrice" symbolSize="24" integerSize="40" decimalSize="24" weight></baseMoney>
								<view class="flex-y-center" v-if="item.attrStatus && item.status">
									<view class="flex-center w-48 h-48 rd-30rpx bg--w111-f5f5f5 text--w111-333" 
										:class="{'disabled-btn': item.productInfo.min_qty && item.cart_num == item.productInfo.min_qty}"
										@click="leaveCart(index, item)">
										<text class="iconfont icon-ic_Reduce fs-32"></text>
									</view>
									<view class="fs-30 text--w111-333 px-20">{{item.cart_num}}</view>
									<view class="flex-center w-48 h-48 rd-30rpx bg-color text--w111-fff" @click="joinCart(index)">
										<text class="iconfont icon-ic_increase fs-32"></text>
									</view>
								</view>
								<view class="noBnt" v-else-if="!item.attrStatus">已售罄</view>
								<view class="noBnt" v-else-if="!item.status">已下架</view>
							</view>
						</view>
					</view>
				</scroll-view>
				<view :class="isFooter ? 'show-footer' : ''"></view>
			</view>
		</view>
		<view class="mask" v-if="cartData.iScart" @click="closeList" @touchmove.stop.prevent="moveHandle"></view>
	</view>
</template>

<script>
	export default {
		props:{
			cartData: {
				type: Object,
				default: () => {}
			},
			isFooter: {
			  type: Boolean,
			  default: false
			},
			hideEmpty:{
				type: Boolean,
				default: true
			}
		},
		data() {
			return {};
		},
		methods: {
			moveHandle(){
				return false
			},
			closeList(){
				this.$emit('closeList', false);
			},
			leaveCart(index,item){
				if(item.productInfo.min_qty && item.cart_num == item.productInfo.min_qty) return
				this.$emit('ChangeCartNumDan', false,index);
			},
			joinCart(index){
				this.$emit('ChangeCartNumDan', true,index);
			},
			subDel(){
				this.$emit('ChangeSubDel');
			},
			oneDel(id,index){
				this.$emit('ChangeOneDel',id,index);
			}
		}
	}
</script>

<style lang="scss">
	.mask{
		z-index: 99;
	}
	.cartList{
		position: fixed;
		left:0;
		bottom: 0;
		width: 100%;
		background-color: #fff;
		z-index:100;
		padding: 40rpx 32rpx 0;
		box-sizing: border-box;
		border-radius:40rpx 40rpx 0 0;
		transform: translate3d(0, 100%, 0);
		transition: all .3s cubic-bezier(.25, .5, .5, .9);
		&.on{
			transform: translate3d(0, 0, 0);
		}
		.title{
			margin-bottom: 32rpx;
			.name{
				font-size:32rpx;
				color: #333;
				font-weight:500;
			}
			.del{
				font-size: 24rpx;
				color: #666;
				.iconfont{
					margin-right: 8rpx;
					font-size: 28rpx;
				}
			}
		}
		.list{
			max-height: 1000rpx;
			.item{
				margin-bottom: 32rpx;
				.pictrue{
					width: 200rpx;
					height: 200rpx;
					border-radius: 16rpx;
					position: relative;
					image{
						width: 100%;
						height: 100%;
						border-radius: 16rpx;
					}
					.mantle{
						position: absolute;
						top:0;
						left:0;
						width: 100%;
						height: 100%;
						background:rgba(255,255,255,0.65);
						border-radius:16rpx;
					}
				}
			}
		}
	}
	.show-pd{
		/* #ifdef H5 */
		padding-bottom: calc(100rpx + constant(safe-area-inset-bottom)); ///兼容 IOS<11.2/
		padding-bottom: calc(100rpx + env(safe-area-inset-bottom)); ///兼容 IOS>11.2/
		/* #endif */
	}
	.noBnt{
		width:126rpx;
		height:44rpx;
		background:#f5f5f5;
		border-radius:22rpx;
		text-align: center;
		line-height: 44rpx;
		font-size: 24rpx;
		color: #333;
	}
	.max-w-460{
		max-width: 460rpx;;
	}
	.show-footer{
		/* #ifdef MP || APP-PLUS */
		height: calc(104rpx + env(safe-area-inset-bottom));
		/* #endif */
		
	}
	.disabled-btn{
		color: #DEDEDE;
	}
</style>
