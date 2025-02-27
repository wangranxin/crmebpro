import {
	getCategoryList,
	getProductslist,
	getAttr,
	postCartNum
} from '@/api/store.js';
import {cartDel} from "@/api/order.js";
import {toLogin} from '@/libs/login.js';
export default {
	data() {
		return {
			attr: {
				cartAttr: false,
				productAttr: [],
				productSelect: {}
			},
			productValue: [],
			beforeCartNum: 0
		};
	},
	created() {
		
	},
	methods: {
		/**
		 * 默认选中属性
		 * 
		 */
		DefaultSelect: function() {
			let productAttr = this.attr.productAttr;
			let value = [];
			if (this.storeInfo.default_sku) {
				value = this.storeInfo.default_sku.split(',');
			} else {
				for (var key in this.productValue) {
					if (this.productValue[key].stock > 0) {
						value = this.attr.productAttr.length ? key.split(',') : [];
						break;
					}
				}
			}
			for (let i = 0; i < productAttr.length; i++) {
				this.$set(productAttr[i], "index", value[i]);
			}
			//sort();排序函数:数字-英文-汉字；
			let productSelect = this.productValue[value.join(",")];
			this.$set(this.attr.productSelect,"store_name",this.storeName);
			if (productSelect && productAttr.length) {
				this.$set(this.attr.productSelect, "image", productSelect.image);
				this.$set(this.attr.productSelect, "price", productSelect.price);
				this.$set(this.attr.productSelect, "stock", productSelect.stock);
				this.$set(this.attr.productSelect, "unique", productSelect.unique);
				this.$set(this.attr.productSelect, 'cart_num', this.storeInfo.min_qty ? this.storeInfo.min_qty : 1);
				this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
				this.$set(this, "attrValue", value.join(","));
			} else if (!productSelect && productAttr.length) {
				this.$set(this.attr.productSelect, "image", this.storeInfo.image);
				this.$set(this.attr.productSelect, "price", this.storeInfo.price);
				this.$set(this.attr.productSelect, "stock", 0);
				this.$set(this.attr.productSelect, "unique", "");
				this.$set(this.attr.productSelect, 'cart_num', this.storeInfo.min_qty ? this.storeInfo.min_qty : 1);
				this.$set(this, "attrValue", "");
				this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
			} else if (!productSelect && !productAttr.length) {
				this.$set(this.attr.productSelect, "image", this.storeInfo.image);
				this.$set(this.attr.productSelect, "price", this.storeInfo.price);
				this.$set(this.attr.productSelect, "stock", this.storeInfo.stock);
				this.$set(this.attr.productSelect,"unique",this.storeInfo.unique || "");
				this.$set(this.attr.productSelect, 'cart_num', this.storeInfo.min_qty ? this.storeInfo.min_qty : 1);
				this.$set(this, "attrValue", "");
				this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
			}
		},
		/**
		 * 属性变动赋值
		 * 
		 */
		ChangeAttr: function(res) {
			let productSelect = this.productValue[res];
			if (!productSelect) {
				this.$set(this.attr.productSelect, 'image', this.storeInfo.image);
				this.$set(this.attr.productSelect, 'price', this.storeInfo.price);
				this.$set(this.attr.productSelect, 'ot_price', this.storeInfo.ot_price);
				this.$set(this.attr.productSelect, 'stock', 0);
				this.$set(this.attr.productSelect, 'unique', '');
				this.$set(this.attr.productSelect, 'cart_num', this.storeInfo.min_qty ? this.storeInfo.min_qty : 1);
				this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
				this.$set(this, 'attrValue', '');
				this.$set(this, 'attrTxt', '请选择');
			}else{
				if (productSelect && productSelect.stock >= 0) {
					this.$set(this.attr.productSelect, "image", productSelect.image);
					this.$set(this.attr.productSelect, "price", productSelect.price);
					this.$set(this.attr.productSelect, "stock", productSelect.stock);
					this.$set(this.attr.productSelect, "unique", productSelect.unique);
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.$set(this.attr.productSelect, 'cart_num', this.storeInfo.min_qty ? this.storeInfo.min_qty : 1);
					this.$set(this, "attrValue", res);
				} else {
					this.$set(this.attr.productSelect, 'image', this.storeInfo.image);
					this.$set(this.attr.productSelect, 'price', this.storeInfo.price);
					this.$set(this.attr.productSelect, 'stock', 0);
					this.$set(this.attr.productSelect, 'unique', '');
					this.$set(this.attr.productSelect, 'cart_num', this.storeInfo.min_qty ? this.storeInfo.min_qty : 1);
					this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
					this.$set(this, 'attrValue', '');
				}
			}
		},
		attrVal(val) {
			this.$set(this.attr.productAttr[val.indexw], 'index', this.attr.productAttr[val.indexw].attr_values[val
				.indexn]);
		},
		/**
		 * 购物车手动填写
		 * 
		 */
		iptCartNum: function(e) {
			this.$set(this.attr.productSelect, 'cart_num', e);
		},
		onMyEvent: function() {
			this.$set(this.attr, 'cartAttr', false);
		},
		// 改变多属性购物车
		ChangeCartNumDuo(changeValue) {
			//获取当前变动属性
			let productSelect = this.productValue[this.attrValue];
			//如果没有属性,赋值给商品默认库存
			if (productSelect === undefined && !this.attr.productAttr.length)
				productSelect = this.attr.productSelect;
			//无属性值即库存为0；不存在加减；
			if (productSelect === undefined) return;
			let stock = productSelect.stock || 0;
			let num = this.attr.productSelect;
			this.ChangeCartNum(changeValue, num, stock, 1);
		},
		// 改变单属性购物车
		ChangeCartNumDan(changeValue, index, item) {
			//如果是点击减少，并且该商品有起购数量，并且此时该商品在购物车的数量等于起购数量，就禁止再继续往下减
			if(!changeValue && item.min_qty && item.cart_num == item.min_qty) return
			let num = this.tempArr[index];
			let stock = this.tempArr[index].stock;
			//设置点击加号添加的数量设置为1
			this.beforeCartNum = 1;
			this.ChangeCartNum(changeValue, num, stock, 0, item.id);
		},
		ChangeSubDel: function(event) {
			let that = this,
				list = that.cartData.cartList,
				ids = [];
			list.forEach(item => {
				ids.push(item.id)
			});
			cartDel(ids.join(",")).then(res => {
				that.$set(that.cartData, 'cartList', []);
				that.cartData.iScart = false;
				that.totalPrice = 0.00;
				that.page = 1;
				that.loadend = false;
				that.tempArr = [];
				that.productslist();
				that.getCartNum();
			})
		},
		ChangeOneDel: function(id, index) {
			let that = this,
				list = that.cartData.cartList;
			cartDel(id.toString()).then(res => {
				list.splice(index, 1);
				if (!list.length) {
					that.cartData.iScart = false;
					that.page = 1;
					that.loadend = false;
					that.tempArr = [];
					that.productslist();
				};
				that.getCartNum();
			})
		},
		// 多规格加入购物车；
		goCatNum() {
			this.goCat(1, this.id, 1);
		},
		closeList(e) {
			this.$set(this.cartData, 'iScart', e);
		},
		/*
		* 已经加入购物车时的购物加减；
		* @param {changeValue} true 添加数量 false 减少数量
		* @param {index} 购物车列表下标
		*/
		ChangeCartList(changeValue, index) {
			let list = this.cartData.cartList;
			let item = list[index];
			let stock = list[index].trueStock;
			//判断该商品如果有起购数量，初次添加的购物数量为起购数量
			this.beforeCartNum = item.productInfo.min_qty ? item.productInfo.min_qty : 1;
			this.ChangeCartNum(changeValue, item, stock, 0, item.product_id, index, 1);
			if (!list.length) {
				this.cartData.iScart = false;
				this.page = 1;
				this.loadend = false;
				this.tempArr = [];
				this.productslist();
			}
		},
		// 购物车加减计算函数
		ChangeCartNum(changeValue, num, stock, isDuo, id, index, cart) {
			if (changeValue) {
				num.cart_num++;
				if (num.cart_num > stock) {
					if (isDuo) {
						this.$set(this.attr.productSelect, "cart_num", stock ? stock : 1);
						this.$set(this, "cart_num", stock ? stock : 1);
					} else {
						num.cart_num = stock ? stock : 0;
						this.$set(this, 'tempArr', this.tempArr);
						this.$set(this.cartData, 'cartList', this.cartData.cartList);
					}
					return this.$util.Tips({
						title: "该产品没有更多库存了"
					});
				} else {
					if (!isDuo) {
						if (cart) {
							this.goCat(0, id, 1, 1, num.product_attr_unique);
							this.getTotalPrice();
						} else {
							this.goCat(0, id, 1);
						}
					}
				}
			} else {
				num.cart_num--;
				if (num.cart_num == 0) {
					this.cartData.cartList.splice(index, 1);
					if (isDuo) {
						this.$set(this.attr.productSelect, "cart_num", 1);
						this.$set(this, "cart_num", 1);
					}
				}
				if (num.cart_num < 0) {
					if (isDuo) {
						this.$set(this.attr.productSelect, "cart_num", 1);
						this.$set(this, "cart_num", 1);
					} else {
						num.cart_num = 0;
						this.$set(this, 'tempArr', this.tempArr);
						this.$set(this.cartData, 'cartList', this.cartData.cartList);
					}
				} else {
					if (!isDuo) {
						if (cart) {
							this.goCat(0, id, 0, 1, num.product_attr_unique);
							this.getTotalPrice();
						} else {
							this.goCat(0, id, 0);
						}
					}
				}
			}
			this.tempArr.forEach((item)=>{
				if(item.id == id){
					item.cart_num = num.cart_num;
				}
			})
		},
		/*
		 * 加入购物车
		 */
		goCat: function(duo, id, type, cart, unique) {
			let that = this;
			
			if (duo) {
				let productSelect = that.productValue[this.attrValue];
				//如果有属性,没有选择,提示用户选择
				if (
					that.attr.productAttr.length &&
					productSelect === undefined
				)
				return that.$util.Tips({
					title: "产品库存不足，请选择其它属性"
				});
			}
			let q = {
				product_id: id,
				num: duo ? that.attr.productSelect.cart_num : that.beforeCartNum,
				type: type,
				unique: duo ? that.attr.productSelect.unique : cart ? unique : ""
			};
			postCartNum(q).then(function(res) {
					if (duo) {
						that.attr.cartAttr = false;
						// that.page = 1;
						// that.loadend = false;
						that.tempArr.forEach((item, index) => {
							if (item.id == that.id) {
								let arrtStock = that.attr.productSelect.stock
								let objNum = parseInt(item.cart_num) + parseInt(that.attr.productSelect.cart_num);  
								item.cart_num = objNum > arrtStock?arrtStock:objNum
							}
						})
						// that.productslist();
					}
					that.$util.Tips({
						title: res.msg
					});
					that.getCartNum();
					if (!cart) {
						that.getCartList(1);
					}
				})
				.catch(err => {
					return that.$util.Tips({
						title: err
					});
				});
		},
		goCartDuo(item) {
			if (!this.isLogin) {
				toLogin();
			} else {
				if(item.cart_button == 0){
					if(item.is_presale_product){
						uni.navigateTo({
							url: `/pages/activity/goods_details/index?id=${item.id}&type=6`
						})
					}else{
						uni.navigateTo({
							url: `/pages/goods_details/index?id=${item.id}&fromType=1`
						})
					}
				}else{
					this.storeName = item.store_name;
					this.getAttrs(item.id);
					this.$set(this, 'id', item.id);
					this.$set(this.attr, 'cartAttr', true);
				}
			}
		},
		// 点击默认单属性购物车
		goCartDan(item, index) {
			if (!this.isLogin) {
				toLogin();
			} else {
				if(item.cart_button == 0){
					if(item.is_presale_product){
						uni.navigateTo({
							url: `/pages/activity/goods_details/index?id=${item.id}&type=6`
						})
					}else{
						uni.navigateTo({
							url: `/pages/goods_details/index?id=${item.id}&fromType=1`
						})
					}
				}else{
					//判断该商品如果有起购数量，初次添加的购物数量为起购数量
					item.cart_num = item.min_qty ? item.min_qty : 1;
					this.beforeCartNum = item.min_qty ? item.min_qty : 1;
					this.goCat(0, item.id, 1);
				}
			}
		},
	}
};
