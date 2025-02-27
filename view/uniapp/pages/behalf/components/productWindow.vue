<template>
	<base-drawer mode="bottom" :visible="attr.cartAttr" background-color="transparent" mask maskClosable
		@close="closeAttr">
		<view class="scroll-content bg--w111-fff rd-t-40rpx">
			<view class="w-full pt-32">
				<view class="px-32 flex">
					<image class="w-180 h-180 rd-16rpx" :src="attr.productSelect.image"></image>
					<view class="pl-24">
						<baseMoney :money="attr.productSelect.price" symbolSize="32" integerSize="48"
							decimalSize="32" color="#FF7E00" weight></baseMoney>
						<view class="mt-20 fs-24 text--w111-999">库存:{{ attr.productSelect.stock }}</view>
					</view>
				</view>
			</view>
			<view class="px-32">
				<scroll-view scroll-y="true" style="max-height: 454rpx" >
					<view class="item mt-32" v-for="(item, indexw) in attr.productAttr" :key="indexw">
						<view class="fs-28 fw-500">{{ item.attr_name }}</view>
						<view class="flex-y-center flex-wrap">
							<view class="sku-item" :class="item.index === itemn.attr ? 'active' : ''"
								v-for="(itemn, indexn) in item.attr_value" @click="tapAttr(indexw, indexn)"
								:key="indexn">
								{{ itemn.attr }}
							</view>
						</view>
					</view>
				</scroll-view>
			</view>
			<view class="flex-between-center mt-40 px-32">
				<view class="fs-28 fw-500">数量</view>
				<view class="flex-1 flex justify-end items-center">
					<text class="fs-22 text-primary">（最多可购买{{attr.productSelect.stock}}件）</text>
					<view class="flex-y-center pl-24">
						<text class="iconfont icon-ic_Reduce fs-24"
							:class="attr.productSelect.cart_num <= 1 ? 'text--w111-f5f5f5' : ''"
							@tap="CartNumDes"></text>
						<input type="number" v-model="attr.productSelect.cart_num"
							data-name="productSelect.cart_num" :always-embed="true" :adjust-position="true" cursor-spacing="30"
							@input="bindCode(attr.productSelect.cart_num)" class="w-88 h-44 rd-4rpx bg--w111-f5f5f5 fs-24 text-center mx-10"></input>
						<text class="iconfont icon-ic_increase fs-24" @tap="CartNumAdd"></text>
					</view>
				</view>
			</view>
			<view class="btn-box" v-show="attr.productSelect.stock == 0">
				<view class="w-full h-72 rd-40rpx flex-center fs-26 bg--w111-ccc text--w111-fff">已售罄</view>
			</view>
			<view class="btn-box flex-between-center" v-show="attr.productSelect.stock > 0">
				<view class="w-346 h-72 rd-40rpx flex-center fs-26 con_border text-primary"
				:class="{'disabled': cartButton == 0}"
				 @tap="goCart(0)">加入购物车</view>
				<view class="w-346 h-72 rd-40rpx flex-center fs-26 bg-primary text--w111-fff" @tap="goCart(1)">立即下单</view>
			</view>
		</view>
	</base-drawer>
</template>
<script>
	import baseDrawer from '@/components/tui-drawer/tui-drawer.vue'
	export default {
		name:'skuSelect',
		props:{
			attr: {
				type: Object,
				default: () => {}
			},
			cartButton:{
				type: Number,
				default: 1
			}
		},
		data(){
			return {

			}
		},
		components:{
			baseDrawer
		},
		methods:{
			goCart(type) {
				if(type == 0 && this.cartButton == 0) return
				if(this.attr.productSelect.stock == 0) return
				this.$emit('goCat',type);
			},
			bindCode: function(e) {
				this.$emit('iptCartNum', this.attr.productSelect.cart_num);
			},
			closeAttr: function() {
				this.$emit('myevent');
			},
			CartNumDes: function() {
				this.$emit('ChangeCartNum', false);
			},
			CartNumAdd: function() {
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

		}
	}
</script>


<style lang="scss" scoped>
	.sku-item {
		height: 56rpx;
		line-height: 56rpx;
		border: 1rpx solid #F2F2F2;
		font-size: 24rpx;
		color: #333;
		padding: 0 44rpx;
		border-radius: 28rpx;
		margin: 24rpx 0 0 16rpx;
		background-color: #F2F2F2;
		word-break: break-all;
	}

	.active {
		color: $primary-admin;
		background: $light-primary-admin;
		border-color: $primary-admin;
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
	.btn-box{
		padding:0 20rpx calc(20rpx + env(safe-area-inset-bottom));
		margin: 78rpx auto 0;
	}
	.disabled{
		background-color: #ccc;
		color: #fff;
		border: 1rpx solid #ccc;
	}
</style>
