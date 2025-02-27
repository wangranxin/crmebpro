<template>
	<view class="gradeActive">
		<!-- #ifdef MP -->
		<NavBar titleText="激活会员" iconColor="#FFFFFF" textColor="#FFFFFF" showBack></NavBar>
		<!-- #endif -->

		<view class="headerBg"></view>
		<view class="header">
			<view class="title">激活会员卡</view>
			<view>新用户免费激活会员卡</view>
		</view>
		<view class="conter">
			<view class="headerT">
				<view class="name">{{list.length?'请填写以下信息':'我的成长特权'}}</view>
			</view>
			<view class="form">
				<view class="item acea-row row-middle" v-for="(item,index) in list" :key="index">
					<view class="name acea-row row-middle" :class="{ asterisk: item.required==1 }">
						<view>{{item.info}}</view>
					</view>
					<view class="input" v-if="item.format == 'text'">
						<input type="text" :placeholder="item.tip" placeholder-class="placeholder" v-model="item.value" />
					</view>
					<!-- number -->
					<view class="input" v-if="item.format=='num'">
						<input type="number" :placeholder="item.tip" placeholder-class="placeholder" v-model="item.value" />
					</view>
					<!-- email -->
					<view class="input" v-if="item.format=='mail'">
						<input type="text" :placeholder="item.tip" placeholder-class="placeholder" v-model="item.value" />
					</view>
					<!-- date -->
					<view class="input" v-if="item.format=='date'">
						<picker mode="date" :value="item.value" @change="bindDateChange($event,index)">
							<view class="acea-row row-middle row-right">
								<view v-if="item.value == ''">{{item.tip}}</view>
								<view v-else>{{item.value}}</view>
								<text class='iconfont icon-ic_rightarrow'></text>
							</view>
						</picker>
					</view>
					<!-- id -->
					<view class="input" v-if="item.format=='id'">
						<input type="idcard" :placeholder="item.tip" placeholder-class="placeholder" v-model="item.value" />
					</view>
					<!-- phone -->
					<view class="input" v-if="item.format=='phone'">
						<input type="tel" :placeholder="item.tip" placeholder-class="placeholder" v-model="item.value" />
					</view>
					<!-- radio -->
					<view class="input" v-if="item.format=='radio'">
						<radio-group @change="radioChange($event,index)">
							<label class="label">
								<radio value="0" :checked="item.value == 0" />{{item.singlearr[0]}}
							</label>
							<label>
								<radio value="1" :checked="item.value == 1" />{{item.singlearr[1]}}
							</label>
						</radio-group>
					</view>
					<!-- address -->
					<view class="input" @click="addressList" v-if="item.format=='address'">
						<picker mode="multiSelector" @change="bindRegionChange($event,index)" @columnchange="bindMultiPickerColumnChange" :value="valueRegion" :range="multiArray">
							<view class='acea-row row-middle row-right'>
								<view class="picker">{{region[0]}}，{{region[1]}}，{{region[2]}}</view>
								<text class='iconfont icon-ic_rightarrow'></text>
							</view>
						</picker>
					</view>
				</view>
			</view>
		</view>
		<view class="bnt" @click="activate">确认激活</view>
		<ewcomerPop v-if="isComerGift" :fromActive="1" :comerGift="comerGift" @comerPop="comerPop"></ewcomerPop>
		<home></home>
	</view>
</template>
<script>
	import {
		levelInfo,
		levelActivate
	} from '@/api/user.js';
	import {
		getCity
	} from '@/api/api.js';
	import {
		HTTP_REQUEST_URL
	} from '@/config/app';
	import ewcomerPop from '@/components/ewcomerPop/index.vue'
	import NavBar from '@/components/NavBar.vue'
	export default {
		components: {
			ewcomerPop,
			NavBar
		},
		data() {
			return {
				imgHost: HTTP_REQUEST_URL,
				list: [],
				district: [],
				multiArray: [],
				multiIndex: [0, 0, 0],
				valueRegion: [0, 0, 0],
				region: ['省', '市', '区'],
				comerGift: {},
				isComerGift: false
			};
		},
		onLoad() {
			this.getInfo();
			this.getCityList();
		},
		onPageScroll(object) {
			uni.$emit('scroll');
		},
		methods: {
			comerPop() {
				this.isComerGift = false;
				uni.navigateTo({
					url: '/pages/users/user_vip/index'
				})
			},
			activate() {
				let that = this;
				for (var i = 0; i < that.list.length; i++) {
					let data = that.list[i]
					if (data.required || data.value) {
						if (data.format === 'date' || data.format === 'address') {
							if (!data.value) {
								return that.$util.Tips({
									title: `${data.tip}`
								});
							}
						}
						if (data.format === 'text') {
							if (!data.value.trim()) {
								return that.$util.Tips({
									title: `${data.tip}`
								});
							}
						}
						if (data.format === 'num') {
							if (data.value <= 0) {
								return that.$util.Tips({
									title: `${data.tip}`
								});
							}
						}
						if (data.format === 'mail') {
							if (!/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(data.value)) {
								return that.$util.Tips({
									title: `${data.tip}`
								});
							}
						}
						if (data.format === 'phone') {
							if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(data.value)) {
								return that.$util.Tips({
									title: `${data.tip}`
								});
							}
						}
						if (data.format === 'id') {
							if (!/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/i.test(data.value)) {
								return that.$util.Tips({
									title: `${data.tip}`
								});
							}
						}
					}
				}
				levelActivate(this.list).then(res => {
					res.data['register_give_integral'] = res.data.level_give_integral;
					res.data['register_give_money'] = res.data.level_give_money;
					res.data['register_give_coupon'] = res.data.level_give_coupon;
					res.data['coupon_count'] = res.data.level_give_coupon.length;
					this.comerGift = res.data;
					if (res.data.level_give_integral > 0 || res.data.level_give_money > 0 || res.data.level_give_coupon.length > 0) {
						this.isComerGift = true;
					} else {
						uni.navigateTo({
							url: '/pages/users/user_vip/index'
						})
					}
				}).catch(err => {
					return this.$util.Tips({
						title: err
					});
				})
			},
			// 省市区地址处理逻辑；
			addressList() {
				this.getCityList();
			},
			// 获取地址数据
			getCityList() {
				let that = this;
				getCity().then(res => {
					this.district = res.data
					that.initialize();
				})
			},
			// 处理地址数据
			initialize: function() {
				let that = this,
					province = [],
					city = [],
					area = [];
				if (that.district.length) {
					let cityChildren = that.district[0].c || [];
					let areaChildren = cityChildren.length ? (cityChildren[0].c || []) : [];
					that.district.forEach(function(item) {
						province.push(item.n);
					});
					cityChildren.forEach(function(item) {
						city.push(item.n);
					});
					areaChildren.forEach(function(item) {
						area.push(item.n);
					});
					this.multiArray = [province, city, area]
				}
			},
			bindRegionChange(e, index) {
				let multiIndex = this.multiIndex,
					province = this.district[multiIndex[0]] || {
						c: []
					},
					city = province.c[multiIndex[1]] || {
						v: 0
					},
					multiArray = this.multiArray,
					value = e.detail.value;

				this.region = [multiArray[0][value[0]], multiArray[1][value[1]], multiArray[2][value[2]]]
				this.list[index].value = city.v;
				this.valueRegion = [0, 0, 0]
				this.initialize();
			},
			bindMultiPickerColumnChange(e) {
				let that = this,
					column = e.detail.column,
					value = e.detail.value,
					currentCity = this.district[value] || {
						c: []
					},
					multiArray = that.multiArray,
					multiIndex = that.multiIndex;
				multiIndex[column] = value;
				switch (column) {
					case 0:
						let areaList = currentCity.c[0] || {
							c: []
						};
						multiArray[1] = currentCity.c.map((item) => {
							return item.n;
						});
						multiArray[2] = areaList.c.map((item) => {
							return item.n;
						});
						break;
					case 1:
						let cityList = that.district[multiIndex[0]].c[multiIndex[1]].c || [];
						multiArray[2] = cityList.map((item) => {
							return item.n;
						});
						break;
					case 2:
						break;
				}
				// #ifdef MP || APP-PLUS
				this.$set(this.multiArray, 0, multiArray[0]);
				this.$set(this.multiArray, 1, multiArray[1]);
				this.$set(this.multiArray, 2, multiArray[2]);
				// #endif
				// #ifdef H5 
				this.multiArray = multiArray;
				// #endif
				this.multiIndex = multiIndex
			},
			radioChange(e, index) {
				this.list[index].value = e.detail.value
			},
			bindDateChange: function(e, index) {
				const date = e.detail.value;
				const current = new Date().toLocaleDateString();
				const checked = new Date(date).toLocaleDateString();
				if (checked < current) {
					this.list[index].value = e.detail.value
				} else {
					this.$util.Tips({
						title: this.list[index].info + '需小于当前的日期'
					})
				}
			},
			getInfo() {
				levelInfo().then(res => {
					res.data.forEach(item => {
						if (item.format == 'radio') {
							item.value = '0'
						} else {
							item.value = ''
						}
					})
					this.list = res.data;
				}).catch(err => {
					this.$util.Tips({
						title: err
					})
				})
			}
		},
		onReachBottom() {}
	}
</script>
<style lang="scss">
	.gradeActive {
		padding-bottom: 20rpx;
		overflow: hidden;

		.headerBg {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 568rpx;
			background: linear-gradient(180deg, #1E1C19 0%, #19140E 100%);
		}

		.header {
			position: relative;
			background-image: url(data:image/png;base64,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),
				url(data:image/png;base64,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);
			background-position: 392rpx 34rpx, left top;
			background-repeat: no-repeat;
			background-size: 240rpx 155rpx, 100% 100%;
			width: 686rpx;
			height: 328rpx;
			padding: 60rpx 0 0 64rpx;
			margin: 44rpx auto 0;
			font-size: 24rpx;
			line-height: 34rpx;
			color: rgba(126, 75, 6, 0.8);

			.title {
				margin-bottom: 8rpx;
				font-weight: 700;
				font-size: 44rpx;
				line-height: 62rpx;
			}
		}

		.bnt {
			position: fixed;
			right: 32rpx;
			bottom: 32rpx;
			bottom: calc(32rpx + constant(safe-area-inset-bottom));
			bottom: calc(32rpx + env(safe-area-inset-bottom));
			left: 32rpx;
			height: 88rpx;
			border-radius: 44rpx;
			background: #FACC7D;
			text-align: center;
			font-weight: 500;
			font-size: 28rpx;
			line-height: 88rpx;
			color: #7E4B06;
		}

		.conter {
			position: relative;
			margin: -104rpx 0 0;
			.form {
				background: #FFFFFF;
				min-height: 300rpx;
				border-radius: 0 0 40rpx 40rpx;
			}

			.item {
				padding: 32rpx 40rpx;
				font-size: 28rpx;
				line-height: 40rpx;
				color: #333333;

				/deep/uni-radio {
					vertical-align: middle;
					margin-right: 14rpx;
				}

				/deep/uni-radio .uni-radio-input {
					width: 28rpx;
					height: 28rpx;
					border: 1px solid #E6993A;
				}

				/deep/uni-radio .uni-radio-input.uni-radio-input-checked {
					border: 1px solid #E6993A !important;
					background-color: #E6993A !important;
				}

				/deep/.wx-radio-input {
					width: 29rpx;
					height: 29rpx;
					border: 1px solid #CCCCCC;
				}

				/deep/.wx-radio-input.wx-radio-input-checked {
					border: 1px solid #E6993A !important;
					background-color: #E6993A !important;
				}

				/deep/uni-radio .uni-radio-input.uni-radio-input-checked:before {
					font-size: 15rpx;
				}

				.label {
					margin-right: 48rpx;
				}

				.name {
					padding-left: 26rpx;
					background-repeat: no-repeat;
					background-position: left center;
					background-size: 14rpx;

					&.asterisk {
						background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAMAAAAolt3jAAAAAXNSR0IArs4c6QAAAJlQTFRFAAAAgAAAvwAA1SoA2yQA2yQk3yAg2yQS7jMi3zAg7zAg5jEi7DAi6DIg6jIh6zMj7DIj6TIj6jIj6jEj6jMj6jMi6TIj6DMh6DMi6jIi6TIi6TIj6TIj6TIj6DIi6TMi6TIi6DMi6TMj6TMi6TIi6DMi6TMj6TIj6DMi6TIi6TIi6DMi6TMi6DMj6DIj6TMj6TMj6TIi6TMjzfWdyAAAADJ0Uk5TAAIEBgcHCA4PEBA0NTg9QUJRbG1ub4mRnJ2forfHyMjQ0t3e3+Lj5O3v8PX19/n6+/1j+QpmAAAAeUlEQVQIHQXBB0LCAAAEsFApyF6yhwMtRYZw/3+cCdBJOgBoJAUA/AUAnH8Bs9UApxr91cx78jntfn2/Tj+Svcm2Tm7X+yWptxMUo2WVVMtRAWgdkkMJYJP1OhuAeY7t9jFvwPjxHDJ8PsbQrLKARX5eoLcrodz1+AePZAvB1OQ7HAAAAABJRU5ErkJggg==);
					}
				}

				.placeholder {
					font-size: 28rpx;
					font-weight: 400;
					color: #CCCCCC;
				}

				.input {
					flex: 1;
					text-align: right;

					.iconfont {
						margin-left: 8rpx;
						font-size: 24rpx;
						color: #999;
					}

					input {
						font-size: 28rpx;
					}
				}
			}

			.headerT {
				background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAu4AAABoCAMAAACZtYUUAAABiVBMVEX//Pb/+vAAAAD/+/T///////////7//vz//fj//////////////////////////////////////fr/+Oz///////////////////7/////////////////+vD///////7///////7//vz//////////////v3/+e///vz//vz/+/T//vv//fn/+ez//Pf//vv//vv/+/P//vv//vv//fr/+vH//vr//Pf//fr/+/T//ff//ff//vv//Pf+9+n+9eP/+/X//fj//vv/+vP/+/T+9ub99OD/+e///vr//PT//fn/+vH+9OL++Ov/+fD//PX/+/b//vr+9eX//ff///7+9ub+9+n+9+n++Oz+9+r///7///z//vz//fr//Pr//vz/+vH//Pf/+/T//fr/+e7+9uf++O3///7+9+n+9eX//fn989///Pb99eP+9OH/+fD/+Ov/9+n/////+vP+9+v+8t3/+e3/+/P/9ub98tz+8dr99OD+8tv/9uj/9eP/9uX/9OH+8Nn/89/MFskxAAAAX3RSTlP8/AD7/Or5/f368Of28u3u5Nb8/N3Gz8z44sLb9Pnf7cm+8tHY0/Xy6+f4+Pr3+MbC5+PM8PTV9PXs5+Leyvv289zZ19H8/OzS7+7e/fDk4NrP7+u7+PXl3eqiJodBZwSHNhcAABqLSURBVHja7J37T1tlGMfBKQmlpSOsZcHdsKv1EsIY2RJksLECQ2HAXGHKZdkPtLZpymUpBAaUub/c7/uc93KuPVfYYe7z+PCq8Rbz8ZPHitrxjQNbmw8e/nTzn/df5+tckSF+/WPiweaWk9X2ur8dS7yX/EO8/8r7f77SFk9//vCG+OU8/v4TI2+96r6xnvoW/PDzwyfTKyvFhYWlpdEXL14W1taGh4enQBXs7++zr2GnfGaZI21qdnNumBYNaLDRsaONJ+psJAdsHDk9ODXxQRsH/tVPVHxwnrYcq/kimCOegj8Zb968+YuxsDzx213m74/rGx503yh14Zf97vnkyOTkzEyxSLr/Dd1fFobBVP9Uf7V/nwHbI+ZM46gt53pa0N1Aw8iOd+pmDtpyas8Hd7woHR2nNhxfceqMOQh//PSYfFe6z8zMTILnP0HirtKGm+7rN7/9Np17AEYmx6Tu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);
				background-repeat: no-repeat;
				background-size: 100% 100%;
				height: 104rpx;
				padding: 40rpx 0 0 40rpx;
				font-weight: 600;
				font-size: 30rpx;
				line-height: 42rpx;
				color: #333333;

				.pictrue {
					width: 124rpx;
					height: 22rpx;

					image {
						width: 100%;
						height: 100%;
						display: block;
					}

					&.on {
						transform: rotateX(180deg);
					}
				}
			}
		}
	}
</style>