<template>
	<!-- 添加新发票 -->
	<view :style="colorStyle" class="invoice-form">
		<!-- #ifdef MP -->
		<view v-if="!id" class="h-100 px-24 rd-24rpx bg--w111-fff mb-20 fs-30 flex-between-center" 
			@tap="exportInvoice">
			<view class="flex-y-center"><text class="iconfont icon-ic_wechat"></text>获取微信发票抬头</view>
			<text class="iconfont icon-ic_rightarrow"></text>
		</view>
		<!-- #endif -->
		<view class="panel form">
			<view class="acea-row row-middle">
				<view class="label">发票类型</view>
				<view class="button-group">
					<view v-for="item in invoiceTypeList" :key="item.value" 
					:class="{ active: formData.type == item.value }" class="button" 
					@click="changeType(item)">{{ item.name }}</view>
				</view>
			</view>
			<view v-if="formData.type == 1" class="acea-row row-middle">
				<view class="label">抬头类型</view>
				<view class="button-group">
					<view v-for="item in headerTypeList" :key="item.value" 
					:class="{ active: formData.header_type == item.value }" class="button" 
					@click="changeHeaderType(item.value)">{{ item.name }}
					</view>
				</view>
			</view>
			<view class="acea-row row-middle">
				<view class="label">发票抬头</view>
				<input name="name" v-model="formData.name" placeholder="请输入发票抬头" />
			</view>
			<view v-show="formData.header_type == 2" class="acea-row row-middle">
				<view class="label">单位税号</view>
				<input name="duty_number" v-model="formData.duty_number" placeholder="请输入单位税号" />
			</view>
			<view class="acea-row row-middle">
				<view class="label">联系电话</view>
				<input name="drawer_phone" v-model="formData.drawer_phone" placeholder="请输入联系电话" />
			</view>
			<view class="acea-row row-middle">
				<view class="label">电子邮箱</view>
				<input name="email" v-model="formData.email" placeholder="请输入电子邮箱" />
			</view>
			<template v-if="formData.type == 2">
				<view class="acea-row row-middle">
					<view class="label">开户银行</view>
					<input name="bank" v-model="formData.bank" placeholder="请输入开户银行" />
				</view>
				<view class="acea-row row-middle">
					<view class="label">银行账号</view>
					<input name="card_number" v-model="formData.card_number" placeholder="请输入银行账号" />
				</view>
				<view class="acea-row row-middle">
					<view class="label">企业地址</view>
					<input name="address" v-model="formData.address" placeholder="请输入企业地址" />
				</view>
				<view class="acea-row row-middle">
					<view class="label">企业电话</view>
					<input name="tell" v-model="formData.tell" placeholder="请输入企业电话" />
				</view>
			</template>
		</view>
		<view class="default acea-row row-between-wrapper">
			<view>设置为默认抬头</view>
			<view :class="{ checked: formData.is_default }" class="switch" @click="changeDefault"></view>
		</view>
		<!-- <view class="default acea-row row-between-wrapper">
			<view class="w-full h-80 rd-40rpx flex-center font-num" @tap="wxInvoice">导入</view>
		</view> -->
		<view class="pb-safe">
			<view class="button-section">
				<button class="button" @tap="formSubmit">保存</button>
			</view>
		</view>
		<home></home>
	</view>
</template>

<script>
	import {
		invoiceSave,
		invoiceDetail
	} from '@/api/user.js';
	import colors from '@/mixins/color.js';
	export default {
		mixins: [colors],
		data() {
			return {
				invoiceTypeList: [{
						name: '普通发票',
						value: 1,
						info: '纸质发票开出后将以邮寄形式交付'
					},
					{
						name: '专用发票',
						value: 2,
						info: '纸质发票开出后将以邮寄形式交付'
					}
				],
				headerTypeList: [
					{name: '个人',value: 1},
					{name: '企业',value: 2}
				],
				formData:{
					id: '', // 修改时为必须参数
					header_type: 1, // 抬头类型1: 个人2： 企业
					type: 1, // 发票类型1：普通2：专用
					drawer_phone: '', // 开票人手机号
					name: '', // 名称（发票抬头）
					duty_number: '', // 税号（个人为非必需，企业是必需参数）
					tell: '', // 公司注册电话
					address: '', // 注册地址
					bank: '', // 开户行
					card_number: '', // 银行卡号
					is_default: 0, // 是否默认
					email: '', // 邮箱
				},
				popupType: false,
				typeName: '',
				urlQuery: '',
				from: '',
				specialInvoice: true,
				order_id: ''
			};
		},
		computed: {
			backUrl() {
				switch (this.from) {
					case 'order_confirm':
						return `/pages/goods/order_confirm/index${this.urlQuery}`;
						break;
					default:
						return '/pages/users/user_invoice_list/index?from=invoice_form';
						break;

				}
			}
		},
		onPageScroll(object) {
			uni.$emit('scroll');
		},
		onLoad(options) {
			for (let key in options) {
				switch (key) {
					case 'couponTitle':
					case 'new':
					case 'cartId':
					case 'pinkId':
					case 'couponId':
					case 'addressId':
						this.urlQuery += `${this.urlQuery ? '&' : '?'}${key}=${options[key]}`;
						break;
					case 'from':
						this.from = options[key];
						break;
					case 'header_type':
						this.header_type = options[key];
						break;
					case 'id':
						this.id = options[key];
						this.getInvoiceDetail();
						break;
					case 'specialInvoice':
						if (options[key] === 'false') {
							this.specialInvoice = false;
						}
						break;
				}
			}
			if (options.order_id) this.order_id = options.order_id
			const invoiceItem = this.invoiceTypeList.find(item => item.value === this.formData.type);
			this.typeName = invoiceItem.name;
		},
		onShow() {
			uni.removeStorageSync('form_type_cart');
		},
		methods: {
			exportInvoice(){
				// #ifdef MP-WEIXIN
				let that = this;
				wx.chooseInvoiceTitle({
				  success(res) {
					that.formData.header_type = res.type == 0 ? 2 : 1; //0 单位 1 个人
					that.formData.name = res.title; //抬头名称
					that.formData.duty_number = res.taxNumber; //抬头税号
					that.formData.address = res.companyAddress; //单位地址
					that.formData.drawer_phone = res.telephone; //手机号码
					that.formData.bank = res.bankName; //银行名称
					that.formData.card_number = res.bankAccount; //银行账号
				  },
				  fail(err) {
				  	console.log(err);
				  }
				})
				// #endif
			},
			// 获取发票数据
			getInvoiceDetail() {
				uni.showLoading({
					title: '加载中'
				});
				invoiceDetail(this.id).then(res => {
					uni.hideLoading();
					this.formData  =res.data;
				}).catch(err => {
					uni.showToast({
						title: err,
						icon: 'none'
					});
				});
			},
			// 保存
			formSubmit(e) {
				let formData = this.formData;
				if (formData.header_type == 1) {
					if (!formData.name) {
						return uni.showToast({
							title: '请输入需要开具发票的姓名',
							icon: 'none'
						});
					}
					if (!formData.drawer_phone) {
						return uni.showToast({
							title: '请输入您的手机号',
							icon: 'none'
						});
					}
					// if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(formData.drawer_phone)) {
					// 	return uni.showToast({
					// 		title: '请正确输入您的手机号',
					// 		icon: 'none'
					// 	});
					// }
					if (!formData.email) {
						return uni.showToast({
							title: '请输入您的联系邮箱',
							icon: 'none'
						});
					}
					if (!/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(formData.email)) {
						return uni.showToast({
							title: '请正确输入您的联系邮箱',
							icon: 'none'
						});
					}
				}
				if (formData.header_type == 2) {
					if (formData.type == 1) {
						if (!formData.name) {
							return uni.showToast({
								title: '请输入需要开具发票的企业名称',
								icon: 'none'
							});
						}
						if (!formData.duty_number) {
							return uni.showToast({
								title: '请输入纳税人识别号',
								icon: 'none'
							});
						}
						if (!/[0-9A-HJ-NPQRTUWXY]{2}\d{6}[0-9A-HJ-NPQRTUWXY]{10}/.test(formData.duty_number)) {
							return uni.showToast({
								title: '请正确输入单位税号',
								icon: 'none'
							});
						}
						if (!formData.drawer_phone) {
							return uni.showToast({
								title: '请输入您的手机号',
								icon: 'none'
							});
						}
						// if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(formData.drawer_phone)) {
						// 	return uni.showToast({
						// 		title: '请正确输入您的手机号',
						// 		icon: 'none'
						// 	});
						// }
						if (!formData.email) {
							return uni.showToast({
								title: '请输入您的联系邮箱',
								icon: 'none'
							});
						}
						if (!/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(formData.email)) {
							return uni.showToast({
								title: '请正确输入您的联系邮箱',
								icon: 'none'
							});
						}
					}
					if (formData.type == 2) {
						if (!formData.name) {
							return uni.showToast({
								title: '请输入需要开具发票的企业名称',
								icon: 'none'
							});
						}
						if (!formData.duty_number) {
							return uni.showToast({
								title: '请输入纳税人识别号',
								icon: 'none'
							});
						}
						if (!/[0-9A-HJ-NPQRTUWXY]{2}\d{6}[0-9A-HJ-NPQRTUWXY]{10}/.test(formData.duty_number)) {
							return uni.showToast({
								title: '请正确输入纳税人识别号',
								icon: 'none'
							});
						}
						// if (!formData.drawer_phone) {
						// 	return uni.showToast({
						// 		title: '请输入您的手机号',
						// 		icon: 'none'
						// 	});
						// }
						if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(formData.drawer_phone)) {
							return uni.showToast({
								title: '请正确输入您的手机号',
								icon: 'none'
							});
						}
						if (!formData.email) {
							return uni.showToast({
								title: '请输入您的联系邮箱',
								icon: 'none'
							});
						}
						if (!/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(formData.email)) {
							return uni.showToast({
								title: '请正确输入您的联系邮箱',
								icon: 'none'
							});
						}
						if (!formData.bank) {
							return uni.showToast({
								title: '请输入您的开户银行',
								icon: 'none'
							});
						}
						if (!formData.card_number) {
							return uni.showToast({
								title: '请输入您的银行账号',
								icon: 'none'
							});
						}
						// if (!/^\d{16}|\d{19}$/.test(formData.card_number)) {
						// 	return uni.showToast({
						// 		title: '请正确输入您的银行账号',
						// 		icon: 'none'
						// 	});
						// }
						if (!formData.address) {
							return uni.showToast({
								title: '请输入您所在的企业地址',
								icon: 'none'
							});
						}
						if (!formData.tell) {
							return uni.showToast({
								title: '请输入您的企业电话',
								icon: 'none'
							});
						}
					}
				}
				// formData.is_default = this.is_default;
				formData.id = this.id;
				uni.showLoading({
					title: '保存中'
				});
				invoiceSave(formData).then(res => {
					let that = this;
					uni.showToast({
						title: '保存成功',
						icon: 'success',
						success() {
							switch (that.from) {
								case 'order_confirm':
									let name = '';
									name += formData.header_type == 1 ? '个人' : '企业';
									name += formData.type == 1 ? '普通' : '专用';
									name += '发票';
									if (that.id) {
										uni.navigateTo({
											url: `/pages/goods/order_confirm/index${that.urlQuery}&invoice_id=${that.id}&invoice_name=${name}`
										})
									} else {
										uni.navigateTo({
											url: `/pages/goods/order_confirm/index${that.urlQuery}&invoice_id=${res.data.id}&invoice_name=${name}`
										})
									}
									break;
								case 'order_details':
									if (that.id) {
										uni.navigateTo({
											url: `/pages/goods/order_details/index?order_id=${that.order_id}&invoice_id=${that.id}`
										})
									} else {
										uni.navigateTo({
											url: `/pages/goods/order_details/index?order_id=${that.order_id}&invoice_id=${res.data.id}`
										})
									}
									break;
								default:
									uni.navigateTo({
										url: '/pages/users/user_invoice_list/index?from=invoice_form'
									});
									break;
							}

						}
					});
				}).catch(err => {
					uni.showToast({
						title: err,
						icon: 'none'
					});
				});
			},
			// 调起发票类型弹窗
			callType() {
				this.popupType = true;
			},
			// 选择发票类型
			changeType(item) {
				if (item.value == 2 && this.formData.header_type == 1) {
					this.formData.header_type = 2;
				}
				this.typeName = item.name;
				this.formData.type = item.value;
			},
			// 关闭发票弹窗
			closeType() {
				this.popupType = false;
			},
			// 选择抬头类型
			changeHeaderType(value) {
				this.formData.header_type = value;
				this.formData.type = 1;
			},
			// 设置为默认抬头
			changeDefault() {
				this.formData.is_default = 1 - this.formData.is_default;
			},
		}
	}
</script>

<style lang="scss" scoped>
	.invoice-form {
		padding: 24rpx 20rpx;

		.label {
			color: #999999;
		}
	}

	/deep/.disabled .uni-radio-input {
		background-color: #F8F8F8;
	}

	.form {
		font-size: 28rpx;
		color: #282828;
	}

	.form input,
	.form radio-group {
		flex: 1;
		margin-left: 24rpx;
	}

	.form input {
		font-size: 26rpx;
	}

	.form label {
		margin-right: 50rpx;
	}

	.form radio {
		margin-right: 8rpx;
	}

	.form checkbox-group {
		height: 90rpx;
	}

	.form checkbox {
		margin-right: 20rpx;
	}
	.icon-ic_wechat {
		margin-right: 20rpx;
		font-size: 48rpx;
		color: #00C800;
	}
	.panel {
		padding: 8rpx 0;
		border-radius: 24rpx;
		background-color: #FFFFFF;
	}

	.panel .acea-row {
		padding: 32rpx 24rpx;
	}

	.panel .button-group {
		margin-left: 24rpx;

		.button {
			display: inline-block;
			height: 56rpx;
			padding: 0 32rpx;
			border: 1rpx solid #F5F5F5;
			border-radius: 28rpx;
			background: #F5F5F5;
			font-weight: 500;
			font-size: 24rpx;
			line-height: 54rpx;
			color: #333333;

			+.button {
				margin-left: 16rpx;
			}

			&.active {
				border-color: var(--view-theme);
				background-color: var(--view-minorColorT);
				color: var(--view-theme);
			}
		}
	}

	.default {
		height: 128rpx;
		padding: 0 24rpx;
		border-radius: 24rpx;
		margin-top: 20rpx;
		background-color: #FFFFFF;
		font-size: 28rpx;
		line-height: 40rpx;
		color: #333333;

		.switch {
			position: relative;
			width: 79rpx;
			height: 48rpx;
			padding: 4rpx;
			border-radius: 24rpx;
			background-color: #DDDDDD;
			transition: background-color 0.3s;

			&::after {
				content: "";
				position: absolute;
				top: 4rpx;
				left: 4rpx;
				width: 40rpx;
				height: 40rpx;
				border-radius: 20rpx;
				background-color: #FFFFFF;
				box-shadow: 0rpx 3rpx 6rpx 0rpx rgba(0, 0, 0, 0.08);
				transition: transform 0.3s cubic-bezier(0.4, 0.4, 0.25, 1.35);
			}

			&.checked {
				background-color: var(--view-theme);

				&::after {
					transform: translateX(30rpx);
				}
			}
		}
	}

	.input-placeholder {
		font-size: 26rpx;
		color: #BBBBBB;
	}

	.icon-xiangyou {
		margin-left: 25rpx;
		font-size: 26rpx;
		color: #BFBFBF;
		margin-top: 2rpx;
	}

	.popup {
		position: fixed;
		bottom: 0;
		left: 0;
		z-index: 99;
		width: 100%;
		padding-bottom: 100rpx;
		border-top-left-radius: 16rpx;
		border-top-right-radius: 16rpx;
		background-color: #F5F5F5;
		overflow: hidden;
		transform: translateY(100%);

		transition: 0.3s;
	}

	.popup.on {
		transform: translateY(0);
	}

	.popup .title {
		position: relative;
		height: 137rpx;
		font-size: 32rpx;
		line-height: 137rpx;
		text-align: center;
	}

	.popup scroll-view {
		height: 466rpx;
		padding-right: 30rpx;
		padding-left: 30rpx;
		box-sizing: border-box;
	}

	.popup label {
		padding: 35rpx 30rpx;
		border-radius: 16rpx;
		margin-bottom: 20rpx;
		background-color: #FFFFFF;
	}

	.popup .text {
		flex: 1;
		min-width: 0;
		font-size: 28rpx;
		color: #282828;
	}

	.popup .info {
		margin-top: 10rpx;
		font-size: 22rpx;
		color: #909090;
	}

	.popup .icon-guanbi {
		position: absolute;
		top: 50%;
		right: 30rpx;
		z-index: 2;
		transform: translateY(-50%);
		font-size: 30rpx;
		color: #707070;
		cursor: pointer;
	}

	.popup .text .acea-row {
		display: inline-flex;
		max-width: 100%;
	}

	.popup .name {
		flex: 1;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
		font-size: 30rpx;
	}

	.popup .label {
		width: 56rpx;
		height: 28rpx;
		border: 1rpx solid #E93323;
		margin-left: 18rpx;
		font-size: 20rpx;
		line-height: 26rpx;
		text-align: center;
		color: #E93323;
	}

	.popup .type {
		width: 124rpx;
		height: 42rpx;
		margin-top: 14rpx;
		background-color: #FCF0E0;
		font-size: 24rpx;
		line-height: 42rpx;
		text-align: center;
		color: #D67300;
	}

	.popup .type.special {
		background-color: #FDE9E7;
		color: #E93323;
	}

	.button-section {
		position: fixed;
		right: 0;
		bottom: 0;
		left: 0;
		padding: 20rpx 20rpx calc(20rpx + env(safe-area-inset-bottom));
		background-color: #FFFFFF;
	}

	.button-section .button {
		height: 80rpx;
		border-radius: 40rpx;
		background-color: var(--view-theme);
		font-weight: 500;
		font-size: 28rpx;
		line-height: 80rpx;
		color: #FFFFFF;
	}

	.button-section .navigator {
		height: 86rpx;
		border: 1rpx solid var(--view-theme);
		border-radius: 43rpx;
		margin-top: 26rpx;
		font-size: 30rpx;
		line-height: 86rpx;
		text-align: center;
		color: var(--view-theme);
	}
</style>