<template>
	<view :style="colorStyle">
		<view class="w-full relative z-99">
			<NavBar
				titleText="订单详情"
				textSize="34rpx"
				:isScrolling="isScrolling"
				:iconColor="isScrolling ? '#333333' : '#ffffff'"
				:textColor="isScrolling ? '#333333' : '#ffffff'"
				showBack
			></NavBar>
			<view class="px-20 relative z-80">
				<view class="flex-between-center h-160 mt-12">
					<view class="flex-1 text--w111-fff pl-12">
						<!-- 交易取消 -->
						<view class="fs-36 fw-500 lh-50rpx" v-if="orderInfo.is_del == 1">交易取消</view>
 						<view class="fs-36 fw-500 lh-50rpx" v-else>{{ orderInfo._status._title }}</view>
						<view class="fs-26 lh-36rpx mt-8" v-if="orderInfo.is_del == 1">交易取消，感谢您的支持</view>
						<view class="fs-26 lh-36rpx mt-8" v-else>{{ orderInfo._status._msg }}
							<text v-if="orderInfo.delivery_type == 'send' && orderInfo.shipping_type == 2"
								>({{orderInfo.status_name}})</text>
						</view>
						
					</view>
					<image :src="imgHost + '/statics/images/order/order_cancel.png'" class="w-140 h-140" 
						v-if="orderInfo.is_del == 1"> </image>
					<image :src="orderInfo.status_pic" class="w-140 h-140" v-else></image>
				</view>
				<view class="bg--w111-fff rd-24rpx p-32 relative z-1" v-if="[1, 3].includes(orderInfo.shipping_type) && orderInfo.product_type == 0">
					<view class="flex-between-center">
						<view class="flex-y-center fw-500 fs-30 lh-42rpx">
							<text class="iconfont icon-ic_location4 fs-30"></text>
							<text class="pl-12">{{ orderInfo.real_name }}</text>
							<text class="pl-20">{{ orderInfo.user_phone }}</text>
						</view>
						<view class="w-154 h-56 rd-28rpx flex-center bg--w111-f5f5f5 fs-24 lh-34rpx" v-if="orderInfo.delivery_type == 'send' && orderInfo.verify_code" @tap="showQrcode = true">
							<text class="iconfont icon-ic_QRcode fs-24"></text>
							<text class="pl-10">自提码</text>
						</view>
					</view>
					<view class="fs-24 text--w111-999 lh-34rpx mt-12 w-full line2 pb-8">{{ orderInfo.user_address }}</view>
					<!-- 普通配送 -->
					<view class="bt pt-32 flex-between-center mt-32" 
						v-if="orderInfo.delivery_type == 'send' && orderInfo.verify_code">
						<view class="fs-26 text--w111-666 lh-34rpx flex-col">
							<text>配送员: {{ orderInfo.delivery_name }}</text>
							<text class="pt-14">联系电话: {{ orderInfo.delivery_id }}</text>
						</view>
						<view class="w-144 h-56 rd-30rpx flex-center fs-24 border bg--w111-fff" @tap="makePhone(orderInfo.delivery_id)">拨打电话</view>
					</view>
					<!-- 第三方配送 -->
					<view class="bt pt-32 flex-between-center mt-32"
						v-if="orderInfo.delivery_type == 'send' && orderInfo.shipping_type == 2">
						<view class="fs-26 text--w111-666 lh-34rpx flex-col">
							<text>配送员: {{ orderInfo.delivery_info.porter_name }}</text>
							<text class="pt-14">联系电话: {{ orderInfo.delivery_info.porter_phone }}</text>
						</view>
						<view class="w-144 h-56 rd-30rpx flex-center fs-24 border bg--w111-fff" 
							@tap="makePhone(orderInfo.delivery_info.porter_phone)">拨打电话</view>
					</view>
					<image src="/static/images/line.jpg" class="bt_line abs-lb"></image>
				</view>
				<view class="bg--w111-fff rd-24rpx p-32 relative z-1" 
					v-if="orderInfo.shipping_type === 2 && [0, 4].includes(orderInfo.product_type)">
					<view class="flex-between-center">
						<view class="h-128 flex-col justify-between">
							<view class="flex-y-center">
								<view class="w-48 h-48 rd-50-p111- flex-center bg-primary-light font-num">
									<text class="iconfont icon-a-ic_user1 fs-28"></text>
								</view>
								<view class="fs-28 fw-500 lh-40rpx pl-16">
									<text>{{ orderInfo.real_name }}</text>
									<text class="pl-20">{{ orderInfo.user_phone }}</text>
								</view>
							</view>
							<view class="flex-y-center">
								<view class="w-48 h-48 rd-50-p111- flex-center bg-primary-light font-num">
									<text class="iconfont icon-ic_mall fs-28"></text>
								</view>
								<view class="fs-28 fw-500 lh-40rpx pl-16">
									<text>{{ orderInfo.system_store.name }}</text>
								</view>
							</view>
						</view>
						<image :src="system_store.image" class="w-128 h-128 rd-16rpx"></image>
					</view>
					<view class="mt-24 pb-24 border_bb flex justify-between">
						<view class="w-578 fs-24 text--w111-999">
							<view class="lh-34rpx line2">地址: {{ orderInfo.system_store.address }}{{ orderInfo.system_store.detailed_address }}</view>
							<view class="mt-12">营业时间：每日{{ orderInfo.system_store.day_time }}</view>
						</view>
						<text class="inline-block copy_btn fs-22 ml-32" @tap="copy(orderInfo.system_store.detailed_address)">复制</text>
					</view>
					<view class="flex-between-center pt-24 fs-24">
						<view class="flex-y-center" @tap="showCodeChange">
							<text class="iconfont icon-ic_QRcode"></text>
							<text class="pl-8">出示自提码</text>
						</view>
						<view class="flex-y-center" @tap="makePhone(orderInfo.system_store.phone)">
							<text class="iconfont icon-ic_phone"></text>
							<text class="pl-8">联系自提点</text>
						</view>
						<view class="flex-y-center" @tap="showMaoLocation">
							<text class="iconfont icon-ic_location"></text>
							<text class="pl-8">导航自提点</text>
						</view>
					</view>
				</view>
			</view>
			<view class="w-full bg-gradient abs-lt" :style="{ height: 213 + sysHeight + 'px' }">
				<view class="w-full abs-lb white_jianbian z-20"></view>
			</view>
		</view>
		<view class="px-20 mt-20">
			<view class="bg--w111-fff rd-24rpx pt-32 pr-24 pl-24 pb-32 relative z-999">
				<view class="fs-28 lh-40rpx mb-26">{{ orderInfo.add_time_y }} {{ orderInfo.add_time_h }}</view>
				<view class="order_goods" v-for="(item, index) in cartInfo" :key="index">
					<view class="flex" @tap="goPage(1, `/pages/goods_details/index?id=${item.product_id}`)">
						<image class="w-136 h-136 rd-16rpx" :src="item.productInfo.attrInfo.image" v-if="item.productInfo.attrInfo" mode="aspectFit"></image>
						<image class="w-136 h-136 rd-16rpx" :src="item.productInfo.image" v-else mode="aspectFit"></image>
						<view class="flex-1 flex justify-between pl-20">
							<view class="w-360rpx">
								<view class="w-full line1 fs-28 lh-40rpx">{{ item.productInfo.store_name }}</view>
								<view class="w-full line1 fs-24 text--w111-999 lh-34rpx mt-8">{{ item.productInfo.attrInfo.suk }}</view>
								<view class="flex items-end flex-wrap mt-12 w-full">
									<BaseTag
										:text="label.label_name"
										:color="label.color"
										:background="label.bg_color"
										:borderColor="label.border_color"
										:circle="label.border_color ? true : false"
										:imgSrc="label.icon"
										v-for="(label, idx) in item.productInfo.store_label"
										:key="idx"
									></BaseTag>
								</view>
							</view>
							<view class="flex-1 flex-col items-end">
								<baseMoney
									:money="item.productInfo.attrInfo ? item.productInfo.attrInfo.price : item.productInfo.price"
									symbolSize="20"
									integerSize="36"
									decimalSize="20"
									color="#333"
									weight
								></baseMoney>
								<view class="fs-24 text--w111-999 lh-40rpx mt-10">共{{ item.cart_num }}件</view>
							</view>
						</view>
					</view>
					<!-- 订单商品操作按钮 核销状态文字 退款状态文字 -->
					<view class="flex-between-center mt-32">
						<view class="fs-24" v-if="[5, 1, 2, 3].includes(status.type) && (orderInfo.shipping_type == 2 || orderInfo.delivery_type == 'send' || orderInfo.product_type == 4)">
							<text v-if="item.is_writeoff">已核销</text>
							<text v-if="!item.is_writeoff && item.write_surplus_times < item.write_times">已核销{{ parseInt(item.write_times) - parseInt(item.write_surplus_times) }}件</text>
							<text v-if="!item.is_writeoff && item.write_surplus_times == item.write_times">未核销</text>
						</view>
						<text class="fs-24" v-if="item.refund_num && status.type != -2">{{ item.refund_num }}件退款中</text>
						<view class="flex-1 flex-y-center justify-end">
							<view
								class="btn w-144 h-56 rd-30rpx flex-center fs-24 border bg--w111-fff"
								v-if="
									orderInfo.is_apply_refund &&
									orderInfo.refund_status === 0 &&
									item.refund_num != item.cart_num &&
									Number(orderInfo.paid) &&
									item.is_support_refund &&
									orderInfo.type != 8
								"
								@tap.stop="openSubcribe(item, orderInfo.product_type)"
							>
								申请退款
							</view>
							<view
								class="btn w-144 h-56 rd-30rpx flex-center fs-24 text--w111-fff bg-color"
								v-if="evaluate == 3 && item.is_reply == 0 && pid != -1"
								@tap.stop="evaluateTap(item.unique, order_id)"
							>
								立即评价
							</view>
						</view>
					</view>
				</view>
				<view class="cell flex justify-between mt-32" v-if="giftCount > 0">
					<text class="fs-28">赠品</text>
					<view class="w-460 flex-y-center justify-end" @tap="showGiftDrawer = true">
						<view class="flex">
							<view class="w-64 h-64 mr-8" v-for="(item, index) in giveCartInfo" :key="item.id">
								<image class="h-full w-full rd-8rpx" :src="item.productInfo.attrInfo.image" v-if="item.productInfo.attrInfo"></image>
								<image class="h-full w-full rd-8rpx" :src="item.productInfo.image" v-else></image>
							</view>
							<view class="w-64 h-64 rd-8rpx bg--w111-f5f5f5 flex-center mr-8" v-if="giveData.give_coupon.length">
								<text class="gold iconfont icon-a-ic_discount1"></text>
							</view>
							<view class="w-64 h-64 rd-8rpx bg--w111-f5f5f5 flex-center mr-8" v-if="giveData.give_integral > 0">
								<text class="gold iconfont icon-ic_badge11"></text>
							</view>
						</view>
						<view class="fs-26 text--w111-666">
							共{{ giftCount }}件
							<text class="iconfont icon-ic_rightarrow"></text>
						</view>
					</view>
				</view>
				<view class="cell flex justify-between mt-32" v-if="orderInfo.mark || orderInfo.refund_explain">
					<text class="fs-28">{{ isGoodsReturn ? '退款留言' : '买家备注' }}</text>
					<text class="fs-28 w-544 text-right">{{ !isGoodsReturn ? orderInfo.mark : orderInfo.refund_explain }}</text>
				</view>
				<view class="cell flex justify-between mt-32" v-if="orderInfo.refund_goods_explain">
					<text class="fs-28">退货留言</text>
					<text class="fs-28 w-544 text-right">{{ orderInfo.refund_goods_explain }}</text>
				</view>
				<view class="pt-32 bt mt-32" v-if="orderInfo.product_type == 1 && Array.isArray(orderInfo.virtual_info)">
					<view class="flex-between-center">
						<text class="fs-28">卡密发货</text>
						<text class="fs-28 card-pasd" @tap="copyCard()">复制全部</text>
					</view>
					<view class="mt-16 bg--w111-f5f5f5 rd-16rpx p-24 lh-36rpx" v-for="(item, index) in orderInfo.virtual_info" :key="index">
						<view class="flex-between-center">
							<text class="fs-28">卡号:</text>
							<text class="fs-36 card-pasd iconfont icon-ic_copy" @tap="copy(item.card_no)"></text>
						</view>
						<view class="fs-26 text--w111-999 mt-8">{{ item.card_no }}</view>
						<view class="flex-between-center mt-24">
							<text class="fs-28">密码:</text>
							<text class="fs-36 card-pasd iconfont icon-ic_copy" @tap="copy(item.card_pwd)"></text>
						</view>
						<view class="fs-26 text--w111-999 mt-8">{{ item.card_pwd }}</view>
					</view>
				</view>
				<view class="mt-32" v-else-if="orderInfo.product_type == 1">
					<view class="flex-between-center mt-32">
						<text class="fs-28">虚拟发货</text>
						<text class="fs-28 card-pasd" @tap="copy(orderInfo.virtual_info)">复制文本</text>
					</view>
					<view class="mt-16 bg--w111-f5f5f5 text--w111-999 rd-16rpx p-24 lh-36rpx">{{ orderInfo.virtual_info }}</view>
				</view>
				<!-- #ifdef MP -->
				<button class="flex-center pt-24 bt-e fs-28 mt-32" hover-class="none" open-type="contact" v-if="orderInfo.routine_contact_type == 1">
					<text class="iconfont icon-ic_customerservice"></text>
					<text class="pl-16">客服</text>
				</button>
				<view class="flex-center pt-24 bt-e fs-28 mt-32" v-else @tap="goGoodCall">
					<text class="iconfont icon-ic_customerservice"></text>
					<text class="pl-16">联系客服</text>
				</view>
				<!-- #endif -->
				<!-- #ifdef H5 || APP-PLUS -->
				<view class="flex-center pt-24 bt-e fs-28 mt-32" @tap="goGoodCall">
					<text class="iconfont icon-ic_customerservice"></text>
					<text class="pl-16">联系客服</text>
				</view>
				<!-- #endif -->
			</view>
			<view class="mt-20 bg--w111-fff rd-16rpx pt-32 pr-24 pl-24 pb-32">
				<view class="cell flex-between-center">
					<text class="fs-28">商品总价</text>
					<text class="fs-28">￥{{ (parseFloat(orderInfo.total_price) + parseFloat(orderInfo.vip_true_price)).toFixed(2) }}</text>
				</view>
				<view class="cell flex-between-center" v-if="orderInfo.pay_postage > 0">
					<text class="fs-28">运费</text>
					<text class="fs-28">￥{{ parseFloat(orderInfo.pay_postage).toFixed(2) }}</text>
				</view>
				<view class="cell flex-between-center" v-if="orderInfo.vip_true_price > 0">
					<text class="fs-28">会员优惠</text>
					<text class="fs-28">-￥{{ parseFloat(orderInfo.vip_true_price).toFixed(2) }}</text>
				</view>
				<view class="cell flex-between-center" v-if="orderInfo.first_order_price > 0">
					<text class="fs-28">首单优惠</text>
					<text class="fs-28">-￥{{ parseFloat(orderInfo.first_order_price).toFixed(2) }}</text>
				</view>
				<view class="cell flex-between-center" v-if="orderInfo.coupon_id">
					<text class="fs-28">优惠券抵扣</text>
					<text class="fs-28">-￥{{ parseFloat(orderInfo.coupon_price).toFixed(2) }}</text>
				</view>
				<view class="cell flex-between-center" v-if="orderInfo.use_integral > 0">
					<text class="fs-28">积分抵扣</text>
					<text class="fs-28">-￥{{ parseFloat(orderInfo.deduction_price).toFixed(2) }}</text>
				</view>
				<!-- 积分订单需要展示的抵扣积分数量 -->
				<view class="cell flex-between-center" v-if="orderInfo.type == 4">
					<text class="fs-28">实付积分</text>
					<text class="fs-28">-{{ orderInfo.pay_integral }}</text>
				</view>
				<!-- 抵扣积分 -->
				<view class="cell flex-between-center" v-for="(item, index) in orderInfo.promotions_detail" :key="index" v-show="parseFloat(item.promotions_price)">
					<text class="fs-28">{{ item.title }}</text>
					<text class="fs-28">-￥{{ parseFloat(item.promotions_price).toFixed(2) }}</text>
				</view>
				<view class="cell flex-between-center">
					<text class="fs-28">实付款</text>
					<text class="fs-28">￥{{ parseFloat(orderInfo.pay_price).toFixed(2) }}</text>
				</view>
			</view>
			<!-- 自定义表单内容 -->
			<view class="mt-20 bg--w111-fff rd-16rpx pt-32 pr-24 pl-24 pb-32" v-if="orderInfo.custom_form && orderInfo.custom_form.length">
				<view class="cell flex justify-between" v-for="(item, index) in orderInfo.custom_form" :key="index">
					<text class="fs-28">{{ item.titleConfig.value }}</text>
					<view v-if="item.name == 'uploadPicture' && item.value.length < 5" class="w-462 flex justify-end">
						<view class="pictrue mr-8" v-for="(items, indexs) in item.value" :key="indexs">
							<image class="w-88 h-88 rd-8rpx" :src="items" mode="aspectFill"></image>
						</view>
					</view>
					<scroll-view
						scroll-x="true"
						scroll-with-animation
						class="white-nowrap vertical-middle w-462"
						show-scrollbar="false"
						v-else-if="item.name == 'uploadPicture' && item.value.length >= 5"
					>
						<view class="inline-block mr-12" v-for="(items, indexs) in item.value" :key="index">
							<image class="w-88 h-88 rd-8rpx" :src="items"></image>
						</view>
					</scroll-view>
					<view v-else-if="item.name == 'dateranges'" class="fs-28">
						<text v-if="item.value.length">{{ item.value[0] + '/' + item.value[1] }}</text>
					</view>
					<text v-else class="fs-28">{{ item.value }}</text>
				</view>
			</view>
			<view class="mt-20 bg--w111-fff rd-16rpx pt-32 pr-24 pl-24 pb-32">
				<view class="cell flex-between-center">
					<text class="fs-28">订单编号</text>
					<view>
						<text class="fs-28 pr-12">{{ orderInfo.order_id }}</text>
						<text class="inline-block copy_btn fs-22" @tap="copy(orderInfo.order_id)">复制</text>
					</view>
				</view>
				<view class="cell flex-between-center" v-if="orderInfo.refunded_price">
					<text class="fs-28">退款金额</text>
					<text class="fs-28">￥{{ orderInfo.refunded_price }}</text>
				</view>
				<view class="cell flex-between-center">
					<text class="fs-28">下单时间</text>
					<text class="fs-28">{{ (orderInfo.add_time_y || '') + ' ' + (orderInfo.add_time_h || 0) }}</text>
				</view>
				<view class="cell flex-between-center">
					<text class="fs-28">支付状态</text>
					<text class="fs-28">{{ orderInfo.paid ? '已支付' : '未支付' }}</text>
				</view>
				<view class="cell flex-between-center">
					<text class="fs-28">支付方式</text>
					<text class="fs-28">{{ orderInfo._status._payType }}</text>
				</view>
			</view>
			<view v-if="orderInfo.status != 0">
				<view class="mt-20 bg--w111-fff rd-16rpx pt-32 pr-24 pl-24 pb-32" v-if="orderInfo.delivery_type == 'express'">
					<view class="cell flex-between-center">
						<text class="fs-28">配送方式</text>
						<text class="fs-28">发货</text>
					</view>
					<view class="cell flex-between-center">
						<text class="fs-28">快递公司</text>
						<text class="fs-28">{{ orderInfo.delivery_name || '' }}</text>
					</view>
					<view class="cell flex-between-center">
						<text class="fs-28">快递单号</text>
						<text class="fs-28">{{ orderInfo.delivery_id || '' }}</text>
					</view>
				</view>
				<view class="mt-20 bg--w111-fff rd-16rpx pt-32 pr-24 pl-24 pb-32" v-if="orderInfo.delivery_type == 'send'">
					<view class="cell flex-between-center">
						<text class="fs-28">配送方式</text>
						<text class="fs-28">送货</text>
					</view>
					<view class="cell flex-between-center">
						<text class="fs-28">配送人姓名</text>
						<text class="fs-28">{{ orderInfo.delivery_name || '' }}</text>
					</view>
					<view class="cell flex-between-center">
						<text class="fs-28">联系电话</text>
						<text class="fs-28" @tap="makePhone(orderInfo.delivery_id)">{{ orderInfo.delivery_id || '' }}</text>
					</view>
				</view>
				<view class="mt-20 bg--w111-fff rd-16rpx pt-32 pr-24 pl-24 pb-32" v-if="orderInfo.delivery_type == 'fictitious' && orderInfo.product_type != 1">
					<view class="cell flex-between-center fs-28">
						<text class="fs-28">虚拟发货</text>
						<text class="fs-28">已发货，请注意查收</text>
					</view>
					<view class="cell flex-between-center fs-28" v-if="orderInfo.fictitious_content">
						<text class="fs-28">虚拟备注</text>
						<text class="fs-28" style="flex: 1; padding-left: 20rpx">{{ orderInfo.fictitious_content }}</text>
					</view>
				</view>
			</view>
		</view>
		<view class="h-200 pb-safe"></view>
		<view class="fixed-lb bt w-full bg--w111-fff pb-safe z-999">
			<view class="h-96 px-20 flex-y-center justify-end">
				<view
					class="btn w-144 h-56 rd-30rpx flex-center fs-24 border bg--w111-fff"
					v-if="invoice_func && !invoiceData.id && orderInfo.type != 8 && status.type != -2 && !orderInfo.is_del"
					@tap="invoiceApply"
				>
					申请开票
				</view>
				<view
					class="btn w-144 h-56 rd-30rpx flex-center fs-24 border bg--w111-fff"
					v-if="invoiceData.id && invoiceData.is_invoice != -1"
					@tap="goPage(1, `/pages/users/user_invoice_order/index?order_id=${orderInfo.order_id}&id=${invoiceData.id}`)"
				>
					查看发票
				</view>
				<view class="btn w-144 h-56 rd-30rpx flex-center fs-24 border bg--w111-fff" 
					v-if="invoice_func && invoiceData && invoiceData.is_invoice == -1" @tap="invoiceApply"
					>重新开票</view>
				<view class="btn w-144 h-56 rd-30rpx flex-center fs-24 text--w111-fff bg-color" 
					v-if="status.type == 0 && !orderInfo.is_del" @tap="pay_open()">立即支付</view>
				<view class="btn w-144 h-56 rd-30rpx flex-center fs-24 border bg--w111-fff" 
					v-if="(status.type == 0 || status.type == -9) && !orderInfo.is_del" @tap="showModalChange(3)">取消订单</view>
				<view class="btn w-144 h-56 rd-30rpx flex-center fs-24 border bg--w111-fff" 
					v-if="orderInfo.type == 3 && orderInfo.paid && orderInfo.refund_status == 0" @tap="goJoinPink">
					查看拼团
				</view>
				<view
					class="btn w-144 h-56 rd-30rpx flex-center fs-24 border bg--w111-fff"
					v-if="orderInfo.delivery_type == 'express' && [3, 5].includes(status.class_status) && [2, 3, 4].includes(status.type) && !split.length"
					@tap="goPage(1, `/pages/goods/goods_logistics/index?orderId=${orderInfo.order_id}`)"
				>
					查看物流
				</view>
				<view class="btn w-144 h-56 rd-30rpx flex-center fs-24 text--w111-fff bg-color" v-if="status.class_status == 3 && !split.length" @tap="confirmOrder()">确认收货</view>
				<view class="btn w-144 h-56 rd-30rpx flex-center fs-24 border bg--w111-fff" v-if="(status.type == 4 && !split.length) || status.type == -2 || orderInfo.is_del" @tap="showModalChange(2)">
					删除订单
				</view>
				<view class="btn w-144 h-56 rd-30rpx flex-center fs-24 font-num con_border bg--w111-fff" 
					v-if="status.class_status == 5 || orderInfo.is_del" @tap="goOrderConfirm">再次购买</view>
				<view
					class="btn w-144 h-56 rd-30rpx flex-center fs-24 font-num con_border bg--w111-fff"
					v-else-if="orderInfo.is_apply_refund && orderInfo.refund_status == 0 && cartInfo.length > 1 && orderInfo.paid"
					@tap="openAfter(`/pages/goods/${cartInfo.length > 1 ? 'goods_return_list' : 'goods_return'}/index?orderId=` + orderInfo.order_id + '&id=' + orderInfo.id)"
				>
					批量退款
				</view>
			</view>
		</view>
		<verifyModal
			:visible="showQrcode"
			:qrcode="config.qrc"
			:qrc="qrc"
			:verifyCode="orderInfo._verify_code"
			:writeDay="orderInfo.write_day"
			:writeTimes="orderInfo.write_times"
			:writeOff="orderInfo.write_off"
			:productType="orderInfo.product_type"
			@closeModal="
				() => {
					showQrcode = false;
				}
			"
		></verifyModal>
		<view class="mask more-mask" v-if="moreBtn" @tap="moreBtn = false"></view>
		<invoice-picker
			:inv-show="invShow"
			:is-special="special_invoice"
			:inv-checked="invChecked"
			:order-id="order_id"
			:inv-list="invList"
			:is-order="1"
			@inv-close="invClose"
			@inv-change="invSub"
			@inv-cancel="invCancel"
		></invoice-picker>
		<!-- 赠品抽屉 -->
		<giftDrawer :visible="showGiftDrawer" :giveCartInfo="giveCartInfo" :giveData="giveData" @closeDrawer="closeDrawer"></giftDrawer>
		<!-- 确认框 -->
		<tuiModal :show="showModal" :title="modalTitle" :content="modalContent" :maskClosable="false" :confirmText="confirmText" @click="handleTap" @cancel="hideModal"></tuiModal>
		<home></home>
	</view>
</template>
<script>
let sysHeight = uni.getWindowInfo().statusBarHeight;
import { getOrderDetail, getRefundOrderDetail, orderAgain, orderTake, orderDel, refundOrderDel, orderCancel, refundExpress } from '@/api/order.js';
import { openOrderRefundSubscribe } from '@/utils/SubscribeMessage.js';
import { getUserInfo, invoiceList, makeUpinvoice } from '@/api/user.js';
import { activityCodeApi } from '@/api/activity.js';
import { toLogin } from '@/libs/login.js';
import { mapGetters } from 'vuex';
import colors from '@/mixins/color';
import invoicePicker from '../components/invoicePicker';
import verifyModal from '../components/verifyModal/index.vue';
import giftDrawer from '../components/giftDrawer/index.vue';
import tuiModal from '@/components/tui-modal/index.vue';
import NavBar from '@/components/NavBar.vue';
import {HTTP_REQUEST_URL} from '@/config/app';
export default {
	components: {
		invoicePicker,
		verifyModal,
		giftDrawer,
		tuiModal,
		NavBar
	},
	mixins: [colors],
	data() {
		return {
			imgHost: HTTP_REQUEST_URL,
			sysHeight: sysHeight,
			giveData: {
				give_integral: 0,
				give_coupon: []
			},
			giveCartInfo: [],
			config: {
				qrc: {
					code: '',
					size: 360, // 二维码大小
					level: 4, //等级 0～4
					bgColor: '#FFFFFF', //二维码背景色 默认白色
					color: ['#333', '#333'] //边框颜色支持渐变色
				}
			},
			order_id: '',
			evaluate: 0,
			cartInfo: [], //购物车产品
			pid: 0, //上级订单ID
			split: [], //分单商品
			orderInfo: {
				system_store: {},
				_status: {}
			}, //订单详情
			system_store: {},
			isGoodsReturn: false, //是否为退款订单
			status: {}, //订单底部按钮状态
			refund_close: false,
			isClose: false,
			pay_close: false,
			pay_order_id: '',
			totalPrice: '0',
			isAuto: false, //没有授权的不会自动授权
			isShowAuth: false, //是否隐藏授权
			routineContact: 0,
			express_num: '',
			invoice_func: false,
			invoiceData: {},
			invoice_id: 0,
			invChecked: '',
			moreBtn: false,
			invShow: false,
			confirmModal: false,
			special_invoice: false,
			invList: [],
			userInfo: {},
			isReturen: '',
			showQrcode: false,
			showGiftDrawer: false,
			showModal: false,
			modalTitle: '',
			modalContent: '',
			confirmText: '',
			modalType: 0,
			orderId: '',
			qrc: '',
			isScrolling: false
		};
	},
	computed: {
		giftCount() {
			let count = 0;
			if (this.giveCartInfo.length) {
				count = this.giveCartInfo.length;
			}
			if (this.giveData.give_coupon.length) {
				count = count + this.giveData.give_coupon.length;
			}
			if (this.giveData.give_integral > 0) {
				count = count + 1;
			}
			return count;
		},
		...mapGetters(['isLogin'])
	},
	onLoad: function (options) {
		if (options.order_id) {
			this.$set(this, 'order_id', options.order_id);
			this.isReturen = options.isReturen;
		}
		if (options.invoice_id) {
			this.invoice_id = options.invoice_id;
		}
		this.activityCodeApi();
	},
	onShow() {
		uni.removeStorageSync('form_type_cart');
		if (this.isLogin) {
			this.getOrderInfo();
			this.getUserInfo();
		} else {
			toLogin();
		}
	},
	onPageScroll(option) {
		uni.$emit('scroll');
		if (option.scrollTop > 50) {
			this.isScrolling = true;
		} else if (option.scrollTop < 50) {
			this.isScrolling = false;
		}
	},
	methods: {
		onLoadFun() {
			this.getOrderInfo();
			this.getUserInfo();
			this.isShowAuth = false;
		},
		// 授权关闭
		authColse: function (e) {
			this.isShowAuth = e;
		},
		getpreviewImage: function (index, num) {
			uni.previewImage({
				urls: num ? this.orderInfo.refund_img : this.orderInfo.refund_goods_img,
				current: num ? this.orderInfo.refund_img[index] : this.orderInfo.refund_goods_img[index]
			});
		},
		showModalChange(type) {
			this.modalType = type;
			if (type == 1) {
				this.modalTitle = '确认收货';
				this.modalContent = '为保障权益，请收到货确认无误后，再确认收货';
				this.confirmText = '确定';
			} else if (type == 2) {
				this.modalTitle = '删除订单';
				this.modalContent = '确定删除该订单?';
				this.confirmText = '确定';
			} else if (type == 3) {
				this.modalTitle = '温馨提示';
				this.modalContent = '确认取消该订单?';
				this.confirmText = '确定';
			} else if (type == 4) {
				this.modalTitle = '去处理';
				this.modalContent = '该订单有售后处理中，确认收货需先撤销售后申请';
				this.confirmText = '去撤销';
			}
			this.showModal = true;
		},
		handleTap(e) {
			let index = e.index;
			if (index == 1) {
				if (this.modalType == 1) {
					orderTake(this.order_id)
						.then((res) => {
							this.showModal = false;
							this.getOrderInfo();
							return this.$util.Tips({
								title: '操作成功',
								icon: 'success'
							});
						})
						.catch((err) => {
							return this.$util.Tips({
								title: err
							});
						});
				} else if (this.modalType == 2) {
					orderDel(this.order_id).then((res) => {
						this.showModal = false;
						return this.$util.Tips(
							{
								title: '删除成功',
								icon: 'success'
							},
							{
								tab: 3,
								url: '/pages/goods/order_list/index'
							}
						);
					});
				} else if (this.modalType == 3) {
					this.showModal = false;
					orderCancel(this.order_id).then(() => {
						uni.reLaunch({
							url: '/pages/goods/order_list/index'
						});
					});
				} else if (this.modalType == 4) {
					this.showModal = false;
					uni.navigateTo({
						url: `/pages/goods/order_after_details/index?isRefund=1&order_id=${this.orderInfo.refund[0].order_id}`
					})
				}
			} else {
				this.showModal = false;
			}
		},
		hideModal() {
			this.showModal = false;
			this.confirmText = '';
		},
		goGoodCall() {
			let url = `/pages/extension/customer_list/chat?orderId=${this.order_id}&isReturen=${this.isReturen}`;
			let obj = {
				store_name: this.orderInfo.order_id,
				path: `/pages/goods/order_details/index?order_id=${this.orderInfo.order_id}`,
				image: ''
			};
			this.$util.getCustomer(this.userInfo, url, obj, 1);
		},
		openAfter: function (e) {
			let page = e;
			// #ifdef MP
			uni.showLoading({
				title: '正在加载'
			});
			openOrderRefundSubscribe()
				.then((res) => {
					uni.hideLoading();
					uni.navigateTo({
						url: page
					});
				})
				.catch(() => {
					uni.hideLoading();
				});
			// #endif
			// #ifndef MP
			uni.navigateTo({
				url: page
			});
			// #endif
		},
		/**
		 * 拨打电话
		 */
		makePhone: function (phone) {
			let that = this;
			// #ifdef APP-PLUS
			plus.device.dial(phone, true);
			// #endif
			// #ifdef MP || H5
			uni.makePhoneCall({
				phoneNumber: phone
			});
			// #endif
		},
		showCodeChange() {
			if (!this.orderInfo.paid)
				return this.$util.Tips({
					title: '请支付后查看自提码'
				});
			if (this.orderInfo.pink_id && this.orderInfo._status._type != 5)
				return this.$util.Tips({
					title: '拼团尚未完成'
				});
			this.showQrcode = true;
		},
		/**
		 * 打开地图
		 *
		 */
		showMaoLocation: function () {
			if (!this.system_store.latitude || !this.system_store.longitude)
				return this.$util.Tips({
					title: '缺少经纬度信息无法查看地图！'
				});
			uni.openLocation({
				latitude: parseFloat(this.system_store.latitude),
				longitude: parseFloat(this.system_store.longitude),
				scale: 8,
				name: this.system_store.name,
				address: this.system_store.address + this.system_store.detailed_address,
				success: function () {}
			});
		},
		/**
		 * 获取用户信息
		 *
		 */
		getUserInfo: function () {
			let that = this;
			getUserInfo().then((res) => {
				that.userInfo = res.data;
			});
		},
		/**
		 * 获取订单详细信息
		 *
		 */
		getOrderInfo: function () {
			let that = this;
			uni.showLoading({
				title: '正在加载中'
			});
			getOrderDetail(this.order_id)
				.then((res) => {
					let _type = res.data._status._type;
					uni.hideLoading();
					that.giveData.give_coupon = res.data.give_coupon;
					that.giveData.give_integral = res.data.give_integral;
					that.$set(that, 'orderInfo', res.data);
					that.$set(that, 'pid', res.data.pid);
					that.$set(that, 'split', res.data.split ? res.data.split : []);
					that.$set(that, 'evaluate', _type == 3 ? 3 : 0);
					that.$set(that, 'system_store', res.data.system_store);
					that.$set(that, 'invoiceData', res.data.invoice);
					if (that.invoiceData) {
						that.invoiceData.pay_price = res.data.pay_price;
					}
					that.$set(that, 'invoice_func', res.data.invoice_func);
					that.$set(that, 'special_invoice', res.data.special_invoice);
					that.$set(that, 'routineContact', Number(res.data.routine_contact_type));
					let cartInfo = res.data.cartInfo;
					let cartObj = [],
						giftObj = [];
					cartInfo.forEach((item) => {
						if (item.is_gift == 1) {
							giftObj.push(item);
						} else {
							cartObj.push(item);
						}
					});
					that.$set(that, 'cartInfo', cartObj);
					that.$set(that, 'giveCartInfo', giftObj);
					this.$nextTick(function () {
						that.config.qrc.code = that.orderInfo.verify_code;
					});
					if (this.orderInfo.refund_status != 0) {
						this.isGoodsReturn = true;
					} else {
						this.isReturen = 0;
					}
					if (that.invoice_id && !that.invoiceData) {
						that.invChecked = that.invoice_id || '';
						this.invoiceApply();
					}
					that.getOrderStatus();
				})
				.catch((err) => {
					uni.hideLoading();
					uni.showToast({
						title: err,
						icon:'none'
					})
					uni.redirectTo({
						url: '/pages/goods/order_list/index'
					});
				});
		},
		// 不开发票
		invCancel() {
			this.invChecked = '';
			this.invTitle = '不开发票';
			this.invShow = false;
		},
		// 选择发票
		invSub(id) {
			this.invChecked = id;
			let data = {
				order_id: this.order_id,
				invoice_id: this.invChecked
			};
			makeUpinvoice(data)
				.then((res) => {
					uni.showToast({
						title: '申请成功',
						icon: 'success'
					});
					this.invShow = false;
					this.getOrderInfo();
				})
				.catch((err) => {
					uni.showToast({
						title: err,
						icon: 'none'
					});
				});
		},
		// 关闭发票
		invClose() {
			this.invShow = false;
			this.getInvoiceList();
		},
		//申请开票
		invoiceApply() {
			this.getInvoiceList();
			this.moreBtn = false;
			this.invShow = true;
		},
		getInvoiceList() {
			uni.showLoading({
				title: '正在加载…'
			});
			invoiceList()
				.then((res) => {
					uni.hideLoading();
					this.invList = res.data.map((item) => {
						item.id = item.id.toString();
						return item;
					});
					const result = this.invList.find((item) => item.id == this.invChecked);
					if (result) {
						let name = '';
						name += result.header_type === 1 ? '个人' : '企业';
						name += result.type === 1 ? '普通' : '专用';
						name += '发票';
						this.invTitle = name;
					}
				})
				.catch((err) => {
					uni.showToast({
						title: err,
						icon: 'none'
					});
				});
		},
		more() {
			this.moreBtn = !this.moreBtn;
		},
		copy: function (code) {
			let that = this;
			uni.setClipboardData({
				data: code
			});
		},
		copyCard() {
			let that = this;
			let strArr = [];
			this.orderInfo.virtual_info.forEach((item, index) => {
				strArr.push(`卡号${index + 1}: ${item.card_no}  密码${index + 1}:${item.card_pwd}`);
			});
			uni.setClipboardData({
				data: strArr.toString()
			});
		},
		/**
		 * 设置底部按钮
		 *
		 */
		getOrderStatus: function () {
			let orderInfo = this.orderInfo || {},
				_status = orderInfo._status || {
					_type: 0
				},
				status = {};
			let type = parseInt(_status._type),
				delivery_type = orderInfo.delivery_type,
				seckill_id = orderInfo.seckill_id ? parseInt(orderInfo.seckill_id) : 0,
				bargain_id = orderInfo.bargain_id ? parseInt(orderInfo.bargain_id) : 0,
				discount_id = orderInfo.discount_id ? parseInt(orderInfo.discount_id) : 0,
				combination_id = orderInfo.combination_id ? parseInt(orderInfo.combination_id) : 0;
			status = {
				type: type == 9 ? -9 : type,
				class_status: 0,
				class_again: 0
			};
			if (type == 1 && combination_id > 0) status.class_status = 1; //查看拼团
			if (type == 2 && delivery_type == 'express') status.class_status = 2; //查看物流
			if (type == 2) status.class_status = 3; //确认收货
			if (type == 4 || type == 0) status.class_status = 4; //删除订单
			if (!seckill_id && !bargain_id && !combination_id && !discount_id && !orderInfo.type && (type == 3 || type == 4)) status.class_status = 5; //再次购买（待评价、已完成）
			if (!seckill_id && !bargain_id && !combination_id && !discount_id && !orderInfo.type && (type == 1 || type == 2 || type == 5)) status.class_again = 6; //再次购买 （待发货、待收货、部分核销）
			this.$set(this, 'status', status);
		},
		/**
		 * 去拼团详情
		 *
		 */
		goJoinPink: function () {
			uni.navigateTo({
				url: '/pages/activity/goods_combination_status/index?id=' + this.orderInfo.pink_id
			});
		},
		/**
		 * 再此购买
		 *
		 */
		goOrderConfirm: function () {
			let that = this;
			orderAgain(that.orderInfo.order_id)
				.then((res) => {
					return uni.navigateTo({
						url: '/pages/goods/order_confirm/index?new=1&cartId=' + res.data.cateId
					});
				})
				.catch((err) => {
					return that.$util.Tips({
						title: err
					});
				});
		},
		confirmOrder(orderId) {
			if (this.orderInfo.refund.length) {
				this.showModalChange(4);
				return;
			}
			let that = this;
			// #ifdef MP
			if (wx.openBusinessView && this.orderInfo.order_shipping_open && this.orderInfo.trade_no) {
				uni.showLoading({
					title: '加载中'
				});
				wx.openBusinessView({
					businessType: 'weappOrderConfirm',
					extraData: {
						transaction_id: this.orderInfo.trade_no
					},
					success() {},
					fail(err) {
						uni.hideLoading();
						return that.$util.Tips({
							title: err.errMsg
						});
					},
					complete() {
						uni.hideLoading();
					}
				});
			} else {
				that.showModalChange(1);
			}
			// #endif
			// #ifndef MP
			this.showModalChange(1);
			// #endif
		},
		pay_open() {
			uni.redirectTo({
				url: `/pages/goods/cashier/index?order_id=${this.order_id}&from_type=order`
			});
		},
		goPage(type, url) {
			if (type == 1) {
				uni.navigateTo({
					url
				});
			} else if (type == 2) {
				uni.switchTab({
					url
				});
			} else if (type == 3) {
				uni.navigateBack();
			}
		},
		openSubcribe: function (item, productType) {
			let cartIds = [
				{
					cart_id: item.id,
					cart_num: parseInt(item.cart_num) - parseInt(item.refund_num)
				}
			];
			cartIds = JSON.stringify(cartIds);
			let page = `/pages/goods/goods_return/index?orderId=` + this.order_id + '&id=' + this.orderInfo.id + '&cartIds=' + cartIds + '&productType=' + productType;
			// #ifdef MP
			uni.showLoading({
				title: '正在加载'
			});
			openOrderRefundSubscribe()
				.then((res) => {
					uni.hideLoading();
					uni.navigateTo({
						url: page
					});
				})
				.catch(() => {
					uni.hideLoading();
				});
			// #endif
			// #ifndef MP
			uni.navigateTo({
				url: page
			});
			// #endif
		},
		evaluateTap: function (unique, orderId) {
			uni.navigateTo({
				url: '/pages/goods/goods_comment_con/index?unique=' + unique + '&uni=' + orderId
			});
		},
		closeDrawer() {
			this.showGiftDrawer = false;
		},
		activityCodeApi() {
			activityCodeApi(91, 0, {
				order_id: this.order_id
			}).then((res) => {
				const { routineUrl, wechatUrl } = res.data;
				// #ifdef MP
				this.qrc = routineUrl;
				// #endif
				// #ifdef H5
				if (this.$wechat.isWeixin()) {
					this.qrc = wechatUrl;
				}
				// #endif
			});
		}
	}
};
</script>
<style lang="scss" scoped>
.white_jianbian {
	height: 120rpx;
	background: linear-gradient(0deg, #f5f5f5 0%, rgba(245, 245, 245, 0) 100%);
}
.bt_line {
	height: 1px;
	width: 680rpx;
	left: 50%;
	transform: translateX(-50%);
}
.order_goods ~ .order_goods {
	margin-top: 32rpx;
}
.con_border {
	border: 1px solid var(--view-theme);
	line-height: 22rpx;
}
.cell input {
	width: 450rpx;
	text-align: right;
}
.cell .radio {
	margin: 0 22rpx;
	padding: 10rpx 0;
}
.cell ~ .cell {
	margin-top: 40rpx;
}
.SemiBold {
	font-family: SemiBold;
}
.copy_btn {
	width: 68rpx;
	height: 36rpx;
	background: #f5f5f5;
	border-radius: 20rpx;
	text-align: center;
	line-height: 36rpx;
}
.btn ~ .btn {
	margin-left: 16rpx;
}
.border {
	border: 1rpx solid #ccc;
}
.border_bb {
	border-bottom: 1px solid #eee;
}
.bt-e {
	border-top: 1px solid #eee;
}
.w-578 {
	width: 578rpx;
}
.bg-primary-light {
	background: var(--view-minorColorT);
}
.mt-228 {
	margin-top: 228rpx;
}
.virtual_info {
	margin-top: -160rpx;
}
.bt {
	border-top: 1px solid #eee;
}
.z-1 {
	z-index: 1;
}
.gold {
	color: #dca658;
}
.card-pasd {
	color: #ff7d00;
}
</style>
