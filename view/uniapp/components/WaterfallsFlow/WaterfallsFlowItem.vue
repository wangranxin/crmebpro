<template>
	<view class="wf-item-page wf-page0">
		<view class='pictrue'>
			<easy-loadimage
			mode="widthFix"
			:image-src="item.image"
			:borderSrc="item.activity_frame.image"
			width="100%"
			borderRadius="16rpx 16rpx 0 0"></easy-loadimage>
		</view>
		<view class="info_box">
			<view class="w-full line2 fs-28 text--w111-333 lh-40rpx">
				<text v-if="item.brand_name" class="brand-tag">{{ item.brand_name }}</text>{{item.store_name}}
			</view>
			<view class="flex items-end flex-wrap mt-12 w-full" v-if="item.store_label.length">
				<BaseTag
					:text="label.label_name"
					:color="label.color"
					:background="label.bg_color"
					:borderColor="label.border_color"
					:circle="label.border_color ? true : false"
					:imgSrc="label.icon"
					v-for="(label, idx) in item.store_label" :key="idx"></BaseTag>
			</view>
			<view class="flex-between-center mt-7" v-if="recommend">
				<view class="flex-y-center flex-wrap">
					<baseMoney :money="isVip ? item.vip_price : item.price" symbolSize="24" integerSize="40" decimalSize="24" weight></baseMoney>
					<view class="svip-label ml-8" 
						v-if="Number(item.vip_price) > 0 && item.is_vip && !isVip">
						<text class="text">SVIP</text>
						<text class="px-8 SemiBold">¥{{item.vip_price}}</text>
					</view>
				</view>
				<view class="w-44 h-44 rd-24 bg-gradient flex-center" v-show="showCart">
					<text class="iconfont icon-ic_ShoppingCart1 text--w111-fff fs-26"></text>
				</view>
			</view>
			<view class="mt-8" v-else>
				<view class="flex-y-center flex-wrap mt-8">
					<baseMoney :money="item.price" symbolSize="24" integerSize="40" decimalSize="24" weight></baseMoney>
					<view class="svip-label" v-if="Number(item.vip_price) > 0 && item.is_vip">
						<text class="text">SVIP</text>
						<text class="px-8 SemiBold">¥{{item.vip_price}}</text>
					</view>
				</view>
				<view class="flex-between-center mt-12">
					<text class="fs-22 text--w111-999">已售{{item.sales}}{{item.unit_name}}</text>
					<view class="w-44 h-44 rd-24 bg-gradient flex-center" @tap.stop="addCartChange" v-show="showCart">
						<text class="iconfont icon-ic_ShoppingCart1 text--w111-fff fs-26"></text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>
<script>
	import easyLoadimage from '@/components/easy-loadimage/easy-loadimage.vue'
	import {mapGetters} from "vuex";
	import {HTTP_REQUEST_URL} from '@/config/app';
	export default {
		components: {
			easyLoadimage
		},
		props: {
			item: {
				type: Object,
				require: true
			},
			type: {
				type: Number,
				default: 0
			},
			recommend:{
				type: Boolean,
				default: false
			},
			showCart:{
				type: Boolean,
				default: true
			}
		},
		data() {
			return {
				domain: HTTP_REQUEST_URL,
			}
		},
		inject: {
			isVip:{
				from: 'isVip',
				default: false // 这里设置默认值为 false
			},
		},
		methods: {
			addCartChange(){
				this.$eventHub.$emit('onCartAddChange',this.item);
			},
		}
	}
</script>
<style lang="scss" scoped>
	.wf-item-page {
		background: #fff;
		overflow: hidden;
		border-radius: 20rpx;
	}
	.pictrue{
		max-height: 560rpx;
		overflow-y: hidden;
	}
	.info_box{
		padding: 16rpx 20rpx;
		border-radius: 0 0 20rpx 20rpx;
		background-color: #fff;
	}
</style>
