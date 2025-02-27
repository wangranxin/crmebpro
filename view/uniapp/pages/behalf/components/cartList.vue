<template>
	<base-drawer mode="bottom" :visible="cartData.iScart" background-color="transparent" mask maskClosable @close="closeList">
		<view class="bg--w111-fff rd-t-40rpx p-32">
			<view class="text-center fs-32 text--w111-333 fw-500">待购清单</view>
			<view class="mt-36">
				<scroll-view scroll-y="true" style="max-height: 1000rpx;">
					<view class="w-full flex-between-center item"
						v-for="(item,index) in cartData.cartList" :key="index">
						<text class="iconfont fs-36"
							:class="item.select ? 'icon-a-ic_CompleteSelect' : 'icon-ic_unselect'"
							@tap="selectItem(item,index)"></text>
						<view class="flex-1 flex pl-18">
							<image v-if="item.productInfo.attrInfo" :src="item.productInfo.attrInfo.image" class="w-136 h-136 rd-16rpx block"></image>
							<image v-else :src="item.productInfo.image" class="w-136 h-136 rd-16rpx block"></image>
							<view class='flex-1 h-136 pl-20'>
								<view class="w-340 line1 fs-28 lh-40rpx fw-500">{{item.productInfo.store_name}}</view>
								<view class="w-324 line1 fs-22 lh-30rpx text--w111-999 mt-12">{{item.productInfo.attrInfo.suk}}</view>
								<view class="flex-1 flex-between-center mt-18">
									<view class="fs-36 Regular">¥{{item.truePrice}}</view>
									<view class="flex-y-center">
										<text class="iconfont icon-ic_Reduce fs-24"
											:class="item.cart_num <= 1 ? 'text--w111-f5f5f5' : ''"
											@tap="cartNumAdd(false,item,index)"></text>
										<input type="number" v-model="item.cart_num"
											 :always-embed="true" :adjust-position="true" cursor-spacing="30"
											 class="w-88 h-44 rd-4rpx bg--w111-f5f5f5 fs-24 text-center mx-10"></input>
										<text class="iconfont icon-ic_increase fs-24" @tap="cartNumAdd(true,item,index)"></text>
									</view>
								</view>
							</view>
						</view>
					</view>
				</scroll-view>
			</view>
			<view class="bt-box"></view>
			<view class="w-full fixed-lb pb-safe bg--w111-fff">
				<view class="w-full h-96 pl-32 pr-20 flex-between-center">
					<view class="flex-y-center" @tap="selectAll">
						<text class="iconfont fs-36" :class="allSelect ? 'icon-a-ic_CompleteSelect' : 'icon-ic_unselect'"></text>
						<text class="fs-26 pl-12">全选</text>
					</view>

					<view class="flex-y-center">
						<view class="w-160 h-64 rd-40rpx flex-center fs-24 con_border text-primary" @tap="cartDel">删除</view>
						<view class="w-160 h-64 rd-40rpx flex-center fs-24 bg-primary text--w111-fff ml-16" @tap="cartConfirm">下单</view>
					</view>
				</view>
			</view>
		</view>
	</base-drawer>
</template>
<script>
import baseDrawer from '@/components/tui-drawer/tui-drawer.vue'
export default {
		name:'cartList',
		props:{
			cartData: {
				type: Object,
				default: () => {}
			},
		},
		data(){
			return {
				allSelect: false
			}
		},
		components:{
			baseDrawer
		},
		methods:{
			closeList(){
				this.$emit('closeList', false);
			},
			selectItem(item,index){
				this.$emit('onSelect',index);
			},
			selectAll(){
				this.$emit('onSelectAll',this.allSelect);
				this.allSelect = !this.allSelect;
			},
			cartDel(){
				this.$emit('onDelCart');
			},
			cartConfirm(){
				this.$emit('onCartConfirm');
			},
			cartNumAdd(type,item,index){
				this.$emit('onCartNum',{type,item,index});
			},
		}
	}
</script>
<style lang="scss" scoped>
	.icon-ic_unselect{
		color: #ccc;
	}
	.icon-a-ic_CompleteSelect{
		color: $primary-admin;
	}
	.text-primary {
		color: $primary-admin;
	}
	.bg-primary{
		background: $primary-admin;
	}
	.con_border {
		border: 1rpx solid $primary-admin;
	}
	.bt-box{
		height: calc(100rpx + env(safe-area-inset-bottom));
	}
	.item ~ .item{
		margin-top: 48rpx;
	}
</style>
