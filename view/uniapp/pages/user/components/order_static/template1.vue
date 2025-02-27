<script>
import {HTTP_REQUEST_URL} from '@/config/app';
export default {
	props: {
		staticData: {
			type: Object,
			default: () => {}
		}
	},
	inject: ['goMenuPage'],
	computed:{
		bgStyle1(){
			return {
				backgroundImage: 'url('+ HTTP_REQUEST_URL +'/statics/images/product/group1_pic.png'+')'
			}
		},
		bgStyle2(){
			return {
				backgroundImage: 'url('+ HTTP_REQUEST_URL +'/statics/images/product/group2_pic.png'+')'
			}
		},
		bgStyle3(){
			return {
				backgroundImage: 'url('+ HTTP_REQUEST_URL +'/statics/images/product/group3_pic.png'+')'
			}
		},
	}
};
</script>


<template>
	<view class="pt-32 pr-24 pb-32 pl-24 bg--w111-fff rd-16rpx mt-20 ml-20 mr-20">
		<view class="fs-30 fw-500 lh-42rpx text--w111-333">运营统计</view>
		<view class="flex-between-center mt-14">
			<view class="w-208 rd-16rpx pt-24 pl-24 pb-20 flex-col bg_1" 
				:style="[bgStyle1]"
				@click="goMenuPage('/pages/admin/order/index')">
				<text class="fs-24 text--w111-999 lh-34rpx">
					支付金额
					<text class="iconfont icon-ic_rightarrow fs-24"></text>
				</text>
				<text class="fs-32 text--w111-333 fw-bold lh-52rpx num-fa-semi">{{ staticData.price }}</text>
			</view>
			<view class="w-208 rd-16rpx pt-24 pl-24 pb-20 flex-col" 
				:style="[bgStyle2]"
				@click="goMenuPage('/pages/admin/order/index')">
				<text class="fs-24 text--w111-999 lh-34rpx">
					支付订单数
					<text class="iconfont icon-ic_rightarrow fs-24"></text>
				</text>
				<text class="fs-32 text--w111-333 fw-bold lh-52rpx num-fa-semi">{{ staticData.num }}</text>
			</view>
			<view class="w-208 rd-16rpx pt-24 pl-24 pb-20 flex-col" 
				:style="[bgStyle3]"
				@click="goMenuPage('/pages/admin/orderList/index?types=1')">
				<text class="fs-24 text--w111-999 lh-34rpx">
					待发货订单
					<text class="iconfont icon-ic_rightarrow fs-24"></text>
				</text>
				<text class="fs-32 text--w111-333 fw-bold lh-52rpx num-fa-semi">{{ staticData.consignment }}</text>
			</view>
		</view>
	</view>
</template>

<style lang="scss" scoped>
.bg-cover{
	background-size: 100%;
}
</style>
