<template>
	<view :style="colorStyle">
		<skeleton :show="showSkeleton" :isNodes="isNodes" ref="skeleton" loading="chiaroscuro" selector="skeleton"
			bgcolor="#FFF"></skeleton>
		<view class="product-con skeleton" :style="{visibility: showSkeleton ? 'hidden' : 'visible'}">
			<view>
				<view class="w-full fixed-lt z-99" :style="[navBarStyle]">
					<view class="w-full px-20 h-80 flex-between-center">
						<!-- #ifdef MP -->
						<view class="fixed flex-center opac" :style="[menuButtonStyle]">
						<!-- #endif -->
						<!-- #ifndef MP -->
						<view class="menu_box flex-center opac">
						<!-- #endif -->
							<image src="@/static/img/back_icon.png" class="w-32 h-32" @tap="backTap"></image>
							<text class="menu_line"></text>
							<image src="@/static/img/menu_icon.png" class="w-32 h-32" @tap="moreNav"></image>
						</view>
						<!-- #ifdef MP -->
						<view class="fixed rd-50-p111- flex-center opac"
							:style="[shareButtonStyle]"
							@tap="listenerActionSheet"
							v-show="diyProduct.openShare">
						<!-- #endif -->
						<!-- #ifndef MP -->
						<view class="w-60 h-60 rd-50-p111- flex-center opac" @tap="listenerActionSheet" v-show="diyProduct.openShare">
						<!-- #endif -->
						<text class="iconfont icon-ic_share1 fs-36 text--w111-000"></text>
						</view>
					</view>
				</view>
				<homeList
					:navH="46 + sysHeight"
					:currentPage="currentPage"
					:sysHeight="sysHeight"
					:openNavList="diyProduct.navList"></homeList>
				<view>
					<!-- 商品轮播图 -->
					<productConSwiper
					:currents='attrPicIndex'
					:sku="attrValue"
					:showSku="showSkuBox"
					:showDot="diyProduct.swiperDot"
					:autoHeight="diyProduct.pictureConfig"
					:imgUrls="productSwiper"
					:videoline="productVideoLink"
					@changeAttrPic="changeAttrPic"
					@attrPicIndex="attrPicIndexs"
					@previewImageOpen="showImg"
					class="skeleton-rect"></productConSwiper>
					<view class="mirror-image">
						<image :src="productSwiper[attrPicIndex]"></image>
					</view>
					<!-- 氛围图 -->
					<view class="atmosphere-card rd-t-40rpx relative w-full h-152 flex justify-between"
						:style="{backgroundImage:cardBg}" v-if="activityBg">
						<view class="flex items-baseline text--w111-fff pt-32 pl-40">
							<baseMoney :money="attr.productSelect.pay_price"
							symbolSize="32"
							integerSize="48"
							decimalSize="32"
							color="#ffffff"
							weight></baseMoney>
							<view class="svip-badge fs-22 flex-center" v-if="showVipPrice">SVIP到手价</view>
							<view class="price-badge fs-22 flex-center" v-else>到手价</view>
							<view class="pl-12 flex-y-center text--w111-fff relative bottom-6"> 
								<text class="Regular fs-26">¥{{ attr.productSelect.price }}</text> 
								<text class="fs-20">原价</text>
							</view>
						</view>
					</view>
					<!-- 商品介绍卡片 -->
					<view class="info_card rd-40rpx relative">
						<view class="px-32">
							<view class="w-full pt-36">
								<!-- 骨架屏展示 -->
								<view v-if="showSkeleton">
									<view class="w-full h-46 skeleton-rect mt-20" ></view>
									<view class="mt-24 flex">
										<view class="w-200 h-46 skeleton-rect"></view>
										<view class="w-100 h-46 skeleton-rect ml-24"></view>
									</view>
									<view class="mt-24 flex-between-center">
										<view class="w-200 h-46 skeleton-rect"></view>
										<view class="w-200 h-46 skeleton-rect"></view>
										<view class="w-200 h-46 skeleton-rect"></view>
									</view>
									<view class="mt-24 flex-between-center">
										<view class="w-96 h-46 skeleton-rect"></view>
										<view class="flex-1 h-46 skeleton-rect ml-22"></view>
									</view>
									<view class="mt-24 flex-between-center">
										<view class="w-96 h-46 skeleton-rect"></view>
										<view class="flex-1 h-46 skeleton-rect ml-22"></view>
									</view>
									<view class="mt-24 flex-between-center">
										<view class="w-96 h-46 skeleton-rect"></view>
										<view class="flex-1 h-46 skeleton-rect ml-22"></view>
									</view>
									<view class="mt-24 flex">
										<view class="w-400 h-200 skeleton-rect"></view>
										<view class="w-400 h-200 skeleton-rect ml-24"></view>
									</view>
								</view>
								<!-- sku小图 -->
								<view class="flex mb-32" v-if="storeInfo.spec_type">
									<view class="flex-1 flex-center">
										<view :class="isSwiper ? 'active_pic' : 'scroll_pic'" @tap="selectSwiper">
											<image :src="storeInfo.image"></image>
										</view>
										<view class="w-76 h-80 flex-col flex-center fs-22 text--w111-333 lh-30rpx">
											<text>{{skuArr.length}}款</text>
											<text>可选</text>
										</view>
									</view>
									<scroll-view scroll-x="true" scroll-with-animation :scroll-left="scrollToView"
										class="white-nowrap vertical-middle w-530" show-scrollbar="false">
										<view class="flex w-full">
											<view class="inline-block mr-16"
												:class="item.suk == attrValue && !isSwiper ? 'active_pic' : 'scroll_pic'"
												v-for="(item, index) in skuArr" :key="index"
												@tap="changeAttrPic(index)">
												<image :src="item.small_image"></image>
											</view>
										</view>
									</scroll-view>
								</view>
								<!-- 价格 -->
								<view class="flex-y-center" v-if="!activityBg">
									<view class="flex items-baseline">
										<baseMoney
											:money="attr.productSelect.pay_price"
											symbolSize="32"
											integerSize="48"
											decimalSize="32" weight></baseMoney>
										<view class="svip-badge fs-22 flex-center" v-if="showVipPrice">SVIP到手价</view>
										<view class="price-badge fs-22 flex-center" v-else>到手价</view>
										<view class="pl-12 flex-y-center relative bottom-6"> 
											<text class="Regular fs-26">¥{{ attr.productSelect.price }}</text> 
											<text class="fs-20">原价</text>
										</view>
									</view>
								</view>
								<!-- 活动标签 -->
								<view class="flex-between-center mt-24"
									v-if="discountInfo.discount.length || coupon.list.length || computedPrice.deduction.vip_price > 0">
									<scroll-view
										scroll-x="true"
										show-scrollbar="false"
										class="white-nowrap vertical-middle w-600">
										<view class="inline-block h-44 rd-8rpx text-center lh-44rpx fs-22 text-primary-con px-8 bg-primary-light mr-16"
											v-for="(item,index) in discountInfo.discount" :key="index + 'i'">{{item.title}}</view>
										<view
											class="inline-block h-44 rd-8rpx text-center lh-44rpx fs-22 text-primary-con px-8 bg-primary-light mr-16"
											v-for="(item,index) in coupon.list" :key="index + 'k'"
											>
												<text v-show="item.use_min_price == 0">无门槛券</text>
												<text v-show="item.use_min_price != 0 && item.coupon_type == 1">满{{ item.use_min_price }}减{{ item.coupon_price }}</text>
												<text v-show="item.use_min_price != 0 && item.coupon_type == 2">满{{ item.use_min_price }}享{{ item.coupon_price }}折</text>
											</view>
										<view
											class="inline-block h-44 rd-8rpx text-center lh-44rpx fs-22 text-primary-con px-8 bg-primary-light mr-16"
											v-if="computedPrice.deduction.vip_price > 0"
											>会员优惠</view>
									</scroll-view>
									<view class="fs-22 text-primary-con" @tap="openPerferentDrawer">查看 <text
											class="iconfont icon-ic_rightarrow fs-24"></text> </view>
								</view>
								<!-- 商品名称 -->
								<view class="mt-20 fs-30 lh-42rpx text--w111-333 fw-500">
									<text v-if="storeInfo.brand_name" class="brand-tag">{{ storeInfo.brand_name }}</text>{{  storeInfo.store_name }}
								</view>
								<!-- 库存销量 -->
								<view class="flex-between-center mt-24 text--w111-999 fs-22 lh-30rpx">
									<text v-show="diyProduct.isOpen.includes(0)">
										<text class="text-line">￥{{  attr.productSelect.ot_price }}</text>
									</text>
									<text v-show="diyProduct.isOpen.includes(2)">库存：{{  attr.productSelect.stock }}{{  storeInfo.unit_name }}</text>
									<text v-show="diyProduct.isOpen.includes(1)">销量：{{  storeInfo.fsales || 0 }}{{  storeInfo.unit_name || ''}}</text>
								</view>
								<!-- 商品标签 -->
								<view class="flex items-end flex-wrap mt-24 w-full"
									v-if="storeInfo.store_label && storeInfo.store_label.length">
									<BaseTag
										:text="label.label_name"
										:color="label.color"
										:background="label.bg_color"
										:borderColor="label.border_color"
										:circle="label.border_color ? true : false"
										:imgSrc="label.icon"
										size="middle"
										v-for="(label, idx) in storeInfo.store_label" :key="idx"></BaseTag>
								</view>
							</view>
						</view>
						<view class="px-20">
							<!-- SVIP card -->
							<view class="h-80 rd-16rpx bg--w111-FFF0D1 flex-between-center mt-24 px-20"
								v-if="showVip">
								<view class="flex-y-center">
									<image src="@/static/img/vip_leval.png" class="w-36 h-36"></image>
									<view class="pl-8">
										<text class="fs-24 text--w111-7E4B06">开通 SVIP会员 预计省</text>
										<text class="text-primary-con fs-28 lh-40rpx px-4">{{diff}}</text>
										<text class="fs-24 text--w111-7E4B06">元</text>
									</view>
								</view>
								<view class="fs-24 text--w111-7E4B06" @tap="goPage(1,'/pages/annex/vip_paid/index')">
									<text>立即开通</text>
									<text class="iconfont icon-ic_rightarrow fs-24"></text>
								</view>
							</view>
							<!-- 排行榜 card -->
							<view class="h-80 rd-16rpx bg--w111-fff flex-between-center mt-24 px-20"
								@tap="goPage(1,'/pages/columnGoods/rank/index?type=' + storeInfo.rank_type)"
								v-if="storeInfo.rank > 0 && diyProduct.showRank">
								<view class="flex-y-center">
									<image :src="imgHost + '/statics/images/product/cup_icon.png'" class="w-32 h-32"></image>
									<image :src="imgHost + '/statics/images/product/rank_icon.png'" class="w-76 h-24 mx-8"></image>
									<text class="fs-26 text--w111-333">{{storeInfo.rank_type | rankType}}·第{{storeInfo.rank}}名</text>
								</view>
								<text class="iconfont icon-ic_rightarrow fs-24 text--w111-666"></text>
							</view>
						</view>
					</view>
					<view class="px-20" v-if="showPromoterCard">
						<view class="rd-16rpx bg--w111-fff mt-24 px-20">
							<view class="w-full h-90 flex-between-center bb-f5" 
								@tap="borkerageShare">
								<view class="fs-26 lh-36rpx" v-show="isLogin">
									<text>推荐好友下单预计可赚</text>
									<text class="font-num">¥{{brokerage}}</text>
									<text>元</text>
								</view>
								<view class="fs-26 lh-36rpx" v-show="!isLogin">推荐好友下单赚取佣金</view>
								<view class="flex-y-center share-tag fs-24">
									<image src="@/static/img/mall05.png"></image>
									<text class="pl-8">{{isLogin ? '去分享' : '去登录'}}</text>
									<text class="iconfont icon-ic_rightarrow fs-24"></text>
								</view>
							</view>
							<view class="pt-16 flex-between-center">
								<text class="fs-28 lh-40rpx fw-500">分享文案</text>
								<text class="font-num fs-22 lh-30rpx" @tap="copyShare">复制</text>
							</view>
							<view class="fs-26 lh-36rpx pt-16 pb-24 space-line">{{storeInfo.share_content || storeInfo.store_name}}</view>
						</view>
					</view>
					<view class="px-20">
						<!-- 活动 SKU选择 服务保障 -->
						<view class="rd-24rpx bg--w111-fff mt-20">
							<view class="flex-y-center h-100 rd-t-24rpx pl-20"
								v-if="activity.length && diyProduct.showService.includes(0)">
								<text class="fs-26 text--w111-888">活动</text>
								<view class="flex-y-center ml-32">
									<block v-for="(item, index) in activity" :key="index">
										<view
											class="inline-block px-16 h-48 lh-48rpx rd-24px bg-primary-light text-primary-con mr-16"
											v-if="item.type === '3'" @tap="goActivity(item)">
											<text class="iconfont icon-ic_user fs-28"></text>
											<text class="fs-24 pl-4">参与拼团</text>
											<text class="iconfont icon-ic_rightarrow fs-24"></text>
										</view>
										<view
											class="inline-block px-16 h-48 lh-48rpx rd-24px bg-primary-light text-primary-con mr-16"
											v-if="item.type === '1'" @tap="goActivity(item)">
											<text class="iconfont icon-ic_clock fs-28"></text>
											<text class="fs-24 pl-4">限时秒杀</text>
											<text class="iconfont icon-ic_rightarrow fs-24"></text>
										</view>
										<view
											class="inline-block px-16 h-48 lh-48rpx rd-24px bg-primary-light text-primary-con mr-16"
											v-if="item.type === '2'" @tap="goActivity(item)">
											<text class="iconfont icon-ic_sale fs-28"></text>
											<text class="fs-24 pl-4">参与砍价</text>
											<text class="iconfont icon-ic_rightarrow fs-24"></text>
										</view>
									</block>
								</view>
							</view>
							<view class="flex-between-center h-100 px-20" @tap="selecAttr"
								v-if="attr.productAttr.length && diyProduct.showService.includes(1)">
								<view class="flex-y-center">
									<text class="fs-26 text--w111-888">选择</text>
									<view class="ml-32 text--w111-333 fs-26 w-560 line1">{{ attrValue }}</view>
								</view>
								<text class="iconfont icon-ic_rightarrow fs-24 text--w111-666"></text>
							</view>
							<view class="flex-between-center h-100 px-20"
								v-if="storeInfo.ensure && storeInfo.ensure.length && diyProduct.showService.includes(2)"
								@tap="()=>{showServiceDrawer = true}">
								<view class="flex-y-center">
									<text class="fs-26 text--w111-888">服务</text>
									<view class="ml-32 text--w111-333 fs-26 w-524 line1">{{ensureInfo.ensureTitle}}</view>
								</view>
								<text class="iconfont icon-ic_rightarrow fs-24 text--w111-666"></text>
							</view>
							<view class="flex-between-center h-100 px-20"
								v-if="storeInfo.specs && storeInfo.specs.length && diyProduct.showService.includes(3)"
								@tap="seeSpecs">
								<view class="flex-y-center">
									<text class="fs-26 text--w111-888">参数</text>
									<view class="ml-32 text--w111-333 fs-26 w-524 line1">{{storeInfo.specsTitle}}</view>
								</view>
								<text class="iconfont icon-ic_rightarrow fs-24 text--w111-666"></text>
							</view>
						</view>
						<!-- 评价卡片 -->
						<view class="rd-24rpx bg--w111-fff mt-20 py-32"
							v-if="replyCount && diyProduct.showReply"
							@tap="goPage(1,'/pages/goods/goods_comment_list/index?product_id=' + id)">
							<view class="px-20 flex-between-center">
								<view>
									<text class="text--w111-333 fs-30 fw-500">评价</text>
									<text class="fs-24 text--w111-666 pl-8">({{replyCount}})</text>
								</view>
								<view class="flex-y-center">
									<text class="fs-28 text-primary-con">{{replyChance}}%</text>
									<text class="fs-24 text--w111-999 pr-12">好评率</text>
									<text class="iconfont icon-ic_rightarrow fs-24 text--w111-666"></text>
								</view>
							</view>
							<!-- 滑动内容 -->
							<block v-if="replyCount">
								<userEvaluation :reply="reply" @changeLogin="changeLogin" :fromTo="1"></userEvaluation>
							</block>
						</view>
						<!-- 种草社区 -->
						<view class="rd-24rpx bg--w111-fff mt-20 py-30" v-if="diyProduct.showCommunity && communityCount > 0">
							<view class="px-20 flex-between-center" @tap="goPage(1,'/pages/goods/goodsDiscover/index?id=' + id)">
								<view>
									<text class="text--w111-333 fs-30 fw-500">种草秀</text>
									<text class="fs-24 text--w111-666 pl-8">({{communityCount}})</text>
								</view>
								<text class="iconfont icon-ic_rightarrow fs-24 text--w111-666"></text>
							</view>
							<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-690 mt-24 pl-24"
								show-scrollbar="false">
								<view class="inline-block mr-22" v-for="(item,index) in communityList" :key="index"
									@tap="goPage(1,`/pages/discover/${item.content_type == 1 ? 'discoverDetails' : 'discoverVideo'}/index?id=${item.id}`)">
									<view class="flex">
										<image class="w-206 h-206 rd-16rpx" :src="item.image" mode="aspectFill"></image>
									</view>
								</view>
							</scroll-view>
						</view>
						<!-- 搭配购 -->
						<view class="rd-24rpx bg--w111-fff mt-20 py-30" v-if="discountsData.length && diyProduct.showMatch"
							@tap="goPage(1,'/pages/goods/match_pay/index?id=' + id)">
							<view class="px-20 flex-between-center">
								<view>
									<text class="text--w111-333 fs-30 fw-500">搭配购</text>
									<text class="fs-24 text--w111-666 pl-8">({{discountsData.length}})</text>
								</view>
								<text class="iconfont icon-ic_rightarrow fs-24 text--w111-666"></text>
							</view>
							<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-690 mt-24 pl-20"
								show-scrollbar="false">
								<view class="inline-block mr-20" v-for="item in discountsData" :key="item.id">
									<view class="flex">
										<image class="w-164 h-164 rd-16rpx" :src="item.image" mode="aspectFill"></image>
										<view class="flex-1 pl-24">
											<view class="w-278 text--w111-333 fs-26 break_word line1">{{item.title}}</view>
											<view class="w-278 mt-20 text-center lh-30rpx break_word fs-22 text--w111-999 line2">
												<text v-for="(items,index) in item.products" :key="index">{{items.store_name}} +</text>
											</view>
											<view class="flex-y-center fs-20 mt-20" v-if="item.max_discounts_price > 0">
												<text class="iconfont icon-a-ic_Money111 font-num fs-28"></text>
												<text class="text--w111-999 px-4">最多可省</text>
												<text class="font-color fs-24 fw-600">¥{{item.max_discounts_price}}</text>
											</view>
										</view>
									</view>
								</view>
							</scroll-view>
						</view>
						<!-- 优品推荐 -->
						<view class="rd-24rpx bg--w111-fff mt-20 py-32" v-if="good_list.length && diyProduct.showRecommend">
							<view class="pl-20 fs-30 fw-500 text--w111-333">优品推荐</view>
							<view class="grid-column-3 grid-gap-x-38rpx grid-gap-y-26rpx px-20 mt-28" v-if="good_list.length <= 6">
								<view v-for="(item, index) in good_list" :key="index"
									@tap="goPage(1,'/pages/goods_details/index?id=' + item.id)">
									<image class="w-198 h-198 rd-20rpx" :src="item.image" mode="aspectFill"></image>
									<view class="w-full line1 fs-26 lh-26rpx pt-16">{{ item.store_name }}</view>
									<view class="text-primary-con pt-14 fs-28 lh-28rpx fw-600">¥{{ item.price }}</view>
								</view>
							</view>
							<scroll-view scroll-x="true" class="w-690 mt-24 pl-20" show-scrollbar="false" v-else>
								<view class="white-nowrap vertical-middle">
									<view class="inline-block recommend_pro" v-for="(item,index) in good_list" :key="index"
										v-if="(index%2)==0" @tap="goPage(1,'/pages/goods_details/index?id=' + item.id)">
										<image class="w-198 h-198 rd-20rpx" :src="item.image">
										</image>
										<view class="w-198 line1 fs-26 lh-26rpx pt-16 text--w111-333">{{item.store_name}}</view>
										<view class="text-primary-con pt-14 fs-28 lh-28rpx fw-600">¥{{item.price}}</view>
									</view>
								</view>
								<view class="white-nowrap vertical-middle mt-24">
									<view class="inline-block recommend_pro" v-for="(item,index) in good_list" :key="index"
										v-if="(index%2)!=0" @tap="goPage(1,'/pages/goods_details/index?id=' + item.id)">
										<image class="w-198 h-198 rd-20rpx" :src="item.image">
										</image>
										<view class="w-198 line1 fs-26 lh-26rpx pt-16 text--w111-333">{{item.store_name}}</view>
										<view class="text-primary-con pt-14 fs-28 lh-28rpx fw-600">¥{{item.price}}</view>
									</view>
								</view>
							</scroll-view>
						</view>
						<!-- 底部操作按钮 -->
						<view class="page_footer bg--w111-fff-s111-80 w-full z-99 fixed-lb pb-safe">
							<view class="w-full h-104 pl-32 pr-20 flex">
								<view class="flex">
									<view class="flex-col flex-center mr-38" @tap="goPage(2,'/pages/index/index')"
										v-if="diyProduct.menuList.includes(0)">
										<text class="iconfont icon-ic_mall fs-40"></text>
										<text class="fs-18">首页</text>
									</view>
									<view class="flex-col flex-center mr-38" @tap="listenerActionSheet"
										v-if="diyProduct.menuList.includes(1)">
										<text class="iconfont icon-ic_transmit1 fs-40"></text>
										<text class="fs-18">分享</text>
									</view>
									<!-- #ifdef MP -->
									<button class="flex-col flex-center mr-38 bg-transparent" hover-class='none' open-type='contact'
										v-if="diyProduct.menuList.includes(2) && routineContact">
										<text class="iconfont icon-ic_customerservice fs-40"></text>
										<text class="fs-18">客服</text>
									</button>
									<button class="flex-col flex-center mr-38 bg-transparent" hover-class='none'
										v-else-if="diyProduct.menuList.includes(2) && !routineContact" @tap="goGoodCall">
										<text class="iconfont icon-ic_customerservice fs-40"></text>
										<text class="fs-18">客服</text>
									</button>
									<!-- #endif -->
									<!-- #ifdef H5 || APP-PLUS -->
									<view class="flex-col flex-center mr-38" v-if="diyProduct.menuList.includes(2)"
										@tap="goGoodCall">
										<text class="iconfont icon-ic_customerservice fs-40"></text>
										<text class="fs-18">客服</text>
									</view>
									<!-- #endif -->
									<view class="flex-col flex-center mr-38" @tap="setCollect" v-if="diyProduct.menuList.includes(3)">
										<text class="iconfont fs-40" :class="storeInfo.userCollect ? 'icon-ic_star1' : 'icon-ic_star'"></text>
										<text class="fs-18">收藏</text>
									</view>
									<view class="flex-col flex-center mr-38 animated relative"
										:class="animated == true ? 'bounceIn' : ''"
										@tap="goPage(2,'/pages/order_addcart/order_addcart')"
										v-if="diyProduct.menuList.includes(4)">
										<text class="iconfont icon-ic_ShoppingCart fs-40"></text>
										<text class="num-badge bg-color" v-if="parseFloat(CartCount) > 0">{{ CartCount || 0 }}</text>
										<text class="fs-18">购物车</text>
									</view>
								</view>
								<view class="flex-1 flex-y-center self-center" v-if="diyProduct.menuStatus">
									<!-- 后台选择了分销版菜单 -->
									<!-- attr.productSelect.stock > 0 &&  -->
									<!-- showMenuPromoterShare -->
									<view class="w-full mr-16" v-show="showMenuPromoterShare">
										<view class="w-full h-72 flex fs-26"
											v-if="storeInfo.cart_button && diyProduct.showCart">
											<view class="cart_icon flex-center" @tap="joinCart">
											  <text class="iconfont icon-ic_ShoppingCart1 fs-36"></text>
											</view>
											<view class="flex-1 daoshou flex-col flex-center text--w111-fff" @tap="borkerageShare">
											  <text class="fs-20">分享预计赚</text>
											  <text class="fw-bold fs-24">¥{{brokerage}}</text>
											</view>
										</view>
										<view class="w-full h-72 flex-col flex-center join_cart1 rd-36px text--w111-fff fs-26" 
											@tap="borkerageShare" v-else>
											<span class="fs-20">分享预计赚</span>
											<span class="fw-bold fs-24">¥{{brokerage}}</span>
										</view>
									</view>
									<view class="w-full h-72 flex-center bg-color rd-36px text--w111-fff fs-26" @tap="goBuy">
										立即购买
									</view>
								</view>
								<!-- 后台选择了常规版菜单 -->
								<template v-else>
									<view class="flex-1 grid-column-2 self-center grid-gap-16rpx"
										v-if="attr.productSelect.stock <= 0 && storeInfo.cart_button && diyProduct.showCart">
											<!-- 无库存允许加入购物车并且可视化开启了购物车 -->
										<view class="w-full h-72 flex-center join_cart rd-36px text--w111-fff fs-26" @tap="joinCart">加入购物车</view>
										<view class="w-full h-72 flex-center bg-hui rd-36px text--w111-fff fs-26">已售罄</view>
									</view>
									<view class="flex-1 flex-y-center"
										v-else-if="attr.productSelect.stock <= 0 && storeInfo.cart_button && !diyProduct.showCart">
											<!-- 无库存允许加入购物车并且可视化关闭了购物车 -->
										<view class="w-full h-72 flex-center bg-hui rd-36px text--w111-fff fs-26">已售罄</view>
									</view>
									<view class="flex-1 grid-column-2 self-center grid-gap-16rpx"
										v-else-if="attr.productSelect.stock >0 && storeInfo.cart_button && diyProduct.showCart">
										<!-- 有库存允许加入购物车并且可视化开启了购物车 -->
										<view class="w-full h-72 flex-center join_cart rd-36px text--w111-fff fs-26" @tap="joinCart">加入购物车</view>
										<view class="w-full h-72 flex-center bg-color rd-36px text--w111-fff fs-26"
											@tap="goBuy">立即购买</view>
									</view>
									<view class="flex-1 flex-y-center" v-else>
										<!-- 有库存不允许加入购物车并且可视化关闭了购物车 -->
										<view class="w-full h-72 flex-center bg-color rd-36px text--w111-fff fs-26"
											@tap="goBuy">立即购买</view>
									</view>
								</template>
								
							</view>
						</view>
						<view class="rd-24rpx bg--w111-fff mt-20 pb-safe" id="past3">
							<view class="fs-30 fw-500 lh-40rpx flex-center py-32">商品详情</view>
							<view class="conter">
								<!-- #ifdef MP-WEIXIN -->
								<jyf-parser :html="description" :tag-style="tagStyle" ref="article"></jyf-parser>
								<!-- #endif -->
								<!-- #ifdef H5 || APP-PLUS -->
								<view class="description" v-html="description"></view>
								<!-- #endif -->
							</view>
						</view>
						<view class="h-148"></view>
						<!-- 服务抽屉 -->
						<service-modal :visible="showServiceDrawer" :ensureInfo="ensureInfo" @closeDrawer="()=>{showServiceDrawer = false}"></service-modal>
						<!-- 优惠弹窗 -->
						<preferential-modal
							:visible="showPerferentDrawer"
							:discountInfo="discountInfo"
							:coupon="coupon"
							:computedPrice="computedPrice"
							:productSelect="attr.productSelect"
							@ChangCouponsUseState="ChangCouponsUseState"
							@closeDrawer="()=>{showPerferentDrawer = false}"
							@ruleToggle="ruleToggle"></preferential-modal>
						<!-- sku弹窗 -->
						<productWindow
						:attr="attr"
						:isShow="1"
						:iSplus="1"
						:type="0"
						isExtends
						:storeInfo="storeInfo"
						@myevent="onMyEvent"
						@ChangeAttr="ChangeAttr"
						@ChangeCartNum="ChangeCartNum"
						@attrVal="attrVal"
						@iptCartNum="iptCartNum"
						@getImg="showImg"
						@onConfirm="onConfirm" id="product-window" :is_vip="is_vip"></productWindow>
						<specs :specsInfo="specsInfo" @myevent="mySpecs"></specs>
						<cusPreviewImg 
						ref="cusPreviewImg" 
						:list="skuArr" 
						:price="computedPrice.deduction.pay_price" 
						@changeSwitch="changeSwitch"
						></cusPreviewImg>
						<!-- #ifdef H5 || APP-PLUS -->
						<zb-code ref="qrcode" onval loadMake :show="codeShow" cid="1" :val="codeVal" @result="qrR" />
						<!-- #endif -->
						<!-- 分享按钮 -->
						<view class="generate-posters pb-safe" :class="posters ? 'on' : ''">
							<view class="generateCon acea-row row-middle">
								<!-- #ifdef H5 -->
								<button class="item" hover-class="none" v-if="weixinStatus === true"
									@tap="H5ShareBox = true">
									<view class="pictrue">
										<image src="../../static/images/weixin.png"></image>
									</view>
									<view class="">分享给好友</view>
								</button>
								<!-- #endif -->
								<!-- #ifdef MP -->
								<button class="item" open-type="share" hover-class="none">
									<view class="pictrue">
										<image src="../../static/images/weixin.png"></image>
									</view>
									<view class="">分享给好友</view>
								</button>
								<!-- #endif -->
								<!-- #ifdef APP-PLUS -->
								<view class="item" @tap="appShare('WXSceneSession')">
									<view class="pictrue">
										<image src="../../static/images/weixin.png"></image>
									</view>
									<view class="">分享给好友</view>
								</view>
								<view class="item" @tap="appShare('WXSenceTimeline')">
									<view class="pictrue">
										<image src="./static/weixinCircle.png"></image>
									</view>
									<view class="">分享朋友圈</view>
								</view>
								<!-- #endif -->
								<view class="item" @tap="getpreviewImage">
									<view class="pictrue">
										<image src="../../static/images/changan.png"></image>
									</view>
									<view class="">预览发图</view>
								</view>
								<!-- #ifndef H5  -->
								<button class="item" hover-class="none" @tap="savePosterPath">
									<view class="pictrue">
										<image src="../../static/images/haibao.png"></image>
									</view>
									<view class="">保存海报</view>
								</button>
								<!-- #endif -->
							</view>
							<view class="generateClose flex-center" @tap="posterImageClose">取消</view>
						</view>
						<div class="fixed-center" v-if="posters && !posterImageStatus">
							<image class="poster-loading" :src="imgHost + '/statics/images/poster_loading.png'" mode="widthFix" ></image>
						</div>
						<!-- 海报展示 -->
						<view class="poster-mask" v-if="posterImageStatus || posters"></view>
						<view class="poster-pop" v-if="posterImageStatus">
							<image :src="posterImage"></image>
						</view>
						<canvas class="canvas" canvas-id="myCanvas" v-if="canvasStatus"></canvas>
						<!-- 发送给朋友图片 -->
						<view class="share-box" v-if="H5ShareBox">
							<image :src="imgHost + '/statics/images/share-info.png'" @tap="H5ShareBox = false">
							</image>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	let sysHeight = uni.getWindowInfo().statusBarHeight;
	import zbCode from '@/components/zb-code/zb-code.vue'
	import {
		getProductDetail,
		getProductCtivity,
		getProductRecommend,
		getProductCode,
		collectAdd,
		collectDel,
		postCartAdd
	} from '@/api/store.js';
	import {
		getUserInfo,
		userShare
	} from '@/api/user.js';
	import { getIntegralProductDetail } from '@/api/activity.js';
	import { getCoupons, setCouponReceive } from '@/api/api.js';
	import { getCartCounts } from '@/api/order.js';
	import { toLogin } from '@/libs/login.js';
	import { mapGetters } from 'vuex';
	import countDown from '@/components/countDown';
	import productConSwiper from '@/components/productConSwiper';
	import productWindow from '@/components/productWindow';
	import specs from './components/specs/index.vue';
	import userEvaluation from '@/components/userEvaluation';
	import kefuIcon from '@/components/kefuIcon';
	import cusPreviewImg from '@/components/cusPreviewImg';
	import homeList from '@/components/homeList'
	import serviceModal from './components/serviceModal/index.vue';
	import preferentialModal from './components/preferentialModal/index.vue';
	import parser from '@/components/jyf-parser/jyf-parser';
	import { silenceBindingSpread } from '@/utils';
	import { TOKENNAME, HTTP_REQUEST_URL} from '@/config/app.js';
	import { Debounce } from '@/utils/validate.js';
	let app = getApp();
	import colors from "@/mixins/color";
	export default {
		components: {
			zbCode,
			productConSwiper,
			productWindow,
			userEvaluation,
			kefuIcon,
			cusPreviewImg,
			specs,
			countDown,
			homeList,
			serviceModal,
			preferentialModal,
			'jyf-parser': parser
		},
		directives: {
			trigger: {
				inserted(el, binging) {
					el.click();
				}
			}
		},
		mixins: [colors],
		data() {
			let that = this;
			return {
				showSkeleton: true, //骨架屏显示隐藏
				isNodes: 0, //控制什么时候开始抓取元素节点,只要数值改变就重新抓取
				//属性是否打开
				coupon: {
					type: -1,
					list: [],
					count: []
				},
				attrTxt: '请选择', //属性页面提示
				attrValue: '', //已选属性
				animated: false, //购物车动画
				id: 0, //商品id
				replyCount: 0, //总评论数量
				reply: [], //评论列表
				storeInfo: {
					brand_name: ''
				}, //商品详情
				productValue: [], //系统属性
				cart_num: 1, //购买数量
				isOpen: false, //是否打开属性组件
				actionSheetHidden: true,
				posterImageStatus: false,
				PromotionCode: '', //二维码图片
				canvasStatus: false, //海报绘图标签
				posterImage: '', //海报路径
				// posterbackgd: '/static/images/posterbackgd.png',
				sharePacket: {
					isState: true //默认不显示
				}, //分销商详细
				uid: 0, //用户uid
				circular: false,
				autoplay: false,
				interval: 3000,
				duration: 500,
				good_list: [],
				replyChance: 0,
				CartCount: 0,
				isDown: true,
				storeSelfMention: true,
				posters: false,
				weixinStatus: false,
				ensureInfo: {
					show: false,
					ensure: [],
					ensureTitle: ''
				},
				specsInfo: {
					show: false,
					specs: []
				},
				discountInfo: {
					show: false,
					discount: []
				},
				attr: {
					cartAttr: false,
					productAttr: [],
					productSelect: {}
				},
				limitInfo: {
					discount_price: 0,
					price: 0,
					discount: 1,
					datatime: 0
				},
				promotions_type: 0,
				description: '',
				H5ShareBox: false, //公众号分享图片
				activity: [],
				lock: false,
				scrollTop: 0,
				returnShow: true, //判断顶部返回是否出现
				diff: '',
				is_money_level: 1,
				is_vip: 0, //是否是会员
				routineContact: 0,
				discountsData: [], //套餐数据
				siteName: '', //商城名称
				themeColor: '#e93323',
				fontColor: '#e93323',
				showAnimate: true,
				skuArr: [],
				//二维码参数
				codeShow: false,
				codeVal: "", // 要生成的二维码值
				cid: '1',
				shareQrcode: 0,
				followCode: '',
				currentPage: false,
				sysHeight: sysHeight,
				isShow: 0,
				imgHost: HTTP_REQUEST_URL,
				fromType: 0, //判断是否回退分类二和三刷新
				cartNum: 0,
				activityBg: '',
				posterTitle: '',
				showServiceDrawer:false,
				showPerferentDrawer:false,
				pageScrollStatus:false,
				cartType:'',
				computedPrice:{
					deduction:{
						pay_price:0,
						sum_price:0,
						vip_price:0
					},
					coupon:{
						use_min_price: 0,
						coupon_type: 0,
						coupon_price: 0,
					}
				},
				isSwiper: true, //默认展示轮播图
				productSwiper: [], //轮播图复制空间
				productVideoLink:'', //轮播图视频链接复制空间
				swiperCurrent: 0,
				userInfo: {},
				showSkuBox:false,
				communityList:[],
				communityCount:0,
				scrollToView: 0,
				attrPicIndex: 0,
				tagStyle: {
					img: 'width:100%;display:block;',
					table: 'width:100%',
					video: 'width:100%;float:left;'
				},
				brokerage: ''
			};
		},
		filters:{
			rankType(val){
				let obj = {
					1:'销量排行榜',
					2:'好评排行榜',
					3:'收藏排行榜'
				};
				return obj[val]
			}
		},
		computed:{
			...mapGetters(['isLogin','diyProduct', 'storeBrokerageStatus']),
			showVip(){
				// 不是付费会员 并且  有会员价 并且这个商品设置了付费会员 并且可视化开启了展示
				return !this.is_money_level && this.computedPrice.deduction.vip_price && this.storeInfo.is_vip && this.diyProduct.showSvip
			},
			cardBg(){
				return `url(${this.activityBg})`
			},
			showVipPrice(){
				if(this.storeInfo.is_vip == 1 && this.computedPrice.deduction.price_type == 'member' && this.computedPrice.deduction.vip_price){
					return true
				}else {
					return false
				}
			},
			navBarStyle(){
				return {
					background: this.pageScrollStatus ? '#fff' : 'transparent',
					paddingTop: (this.sysHeight) * 2 + 'rpx'
				}
			},
			showPromoterCard(){
				if(this.diyProduct.showPromoter){
					let type = this.diyProduct.showPromoterType; //0 全部用户 1 分销员 2 普通用户
					if([0,2].includes(type) && !this.isLogin){
						return true
					}else if(type == 0 || (type ==1 && this.isLogin && this.userInfo.is_promoter == 1) || (type == 2 && this.isLogin && this.userInfo.is_promoter == 0)){
						return true
					}else {
						return false
					}
				}else{
					return false
				}
			},
			showMenuPromoterShare(){
				let type = this.diyProduct.showMenuPromoterShare; //0 全部用户 1 分销员 2 普通用户
				if([0,2].includes(type) && !this.isLogin){
					return true
				}else if(type == 0 || (type ==1 && this.isLogin && this.userInfo.is_promoter == 1) || (type == 2 && this.isLogin && this.userInfo.is_promoter == 0)){
					return true
				}else {
					return false
				}
			},
			// #ifdef MP
			shareButtonStyle(){
				let res = wx.getMenuButtonBoundingClientRect();
				return {
					width: res.height + 'px',
					height: res.height + 'px',
					top: res.top + 'px',
					left: (res.left - 38) + 'px'
				}
			},
			menuButtonStyle(){
				let res = wx.getMenuButtonBoundingClientRect();
				return {
					width: res.width + 'px',
					height: res.height + 'px',
					top: res.top + 'px',
					left: '11px',
					borderRadius: res.height + 'px',
				}
			}
			// #endif
		},
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV == true) {
						this.getCartCount();
					}
				},
				deep: true
			},
		},
		onLoad(options) {
			let that = this;
			var pages = getCurrentPages();
			that.returnShow = pages.length === 1 ? false : true;
			that.id = options.id;
			that.isShow = options.isShow;
			that.fromType = options.fromType;
			//扫码携带参数处理
			// #ifdef MP
			if (options.scene) {
				let value = that.$util.getUrlParams(decodeURIComponent(options.scene));
				if (value.id) options.id = value.id;
				//记录推广人uid
				if (value.spid) app.globalData.spid = value.spid;
			}
			if (!options.id) {
				this.showSkeleton = false;
				return that.$util.Tips({
					title: '缺少参数无法查看商品'
				}, {
					tab: 3,
					url: 1
				});
			} else {
				that.id = options.id;
			}
			// #endif
			//记录推广人uid
			if (options.spid) app.globalData.spid = options.spid;
			that.getGoodsDetails();
		},
		onReady: function() {
			this.isNodes++;
			let uid = this.isLogin ? this.$store.state.app.uid : ''
			// #ifdef H5
			this.codeVal = window.location.origin + '/pages/goods_details/index?id=' + this.id +
				'&spid=' + uid
			// #endif
			// #ifdef APP-PLUS
			this.codeVal = HTTP_REQUEST_URL + '/pages/goods_details/index?id=' + this.id +
				'&spid=' + uid
			// #endif
		},
		/**
		 * 用户点击右上角分享
		 */
		// #ifdef MP
		onShareAppMessage: function() {
			let that = this;
			that.$set(that, 'actionSheetHidden', !that.actionSheetHidden);
			userShare();
			return {
				title: that.storeInfo.store_name || '',
				imageUrl: that.storeInfo.image || '',
				path: '/pages/goods_details/index?id=' + that.id + '&spid=' + that.uid
			};
		},
		onShareTimeline() {
			let that = this;
			that.$set(that, 'actionSheetHidden', !that.actionSheetHidden);
			userShare();
			return {
				title: that.storeInfo.store_name || '',
				imageUrl: that.storeInfo.image || '',
				path: '/pages/goods_details/index?id=' + that.id + '&spid=' + that.uid
			};
		},
		// #endif
		methods: {
			changeLogin() {
				toLogin()
			},
			seeEnsure() {
				this.ensureInfo.show = true;
			},
			seeSpecs() {
				this.specsInfo.show = true;
			},
			seeDiscount() {
				this.discountInfo.show = true;
			},
			moreNav() {
				this.currentPage = !this.currentPage
			},
			//点击sku图片打开轮播图
			showImg(index) {
				this.$refs.cusPreviewImg.open(this.attrValue)
			},
			//滑动轮播图选择商品
			changeSwitch(e) {
				let productSelect = this.skuArr[e];
				var skuList = productSelect.suk.split(',');
				skuList.forEach((i, index) => {
					this.$set(this.attr.productAttr[index], 'index', skuList[index]);
				})
				if (productSelect) {
					// this.getGoodsCtivity(productSelect.unique);
					this.$set(this.attr.productSelect, 'image', productSelect.image);
					this.$set(this.attr.productSelect, 'price', productSelect.price);
					this.$set(this.attr.productSelect, 'stock', productSelect.stock);
					this.$set(this.attr.productSelect, 'unique', productSelect.unique);
					this.$set(this.attr.productSelect, 'cart_num', 1);
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.$set(this, 'attrValue', productSelect.suk);
					this.$set(this, 'attrTxt', '已选择');
				}
				this.$refs.productConSwiper.currents = e;
			},
			qrR(res) {
				// #ifdef H5
				if (!this.$wechat.isWeixin() || this.shareQrcode != '1') {
					this.PromotionCode = res;
					this.followCode = ''
				}
				// #endif
				// #ifdef APP-PLUS
				this.PromotionCode = res;
				// #endif
			},
			boxStatus(data) {
				this.showAnimate = data
			},
			// 图片预览；
			getpreviewImage: function() {
				if (this.posterImage) {
					let photoList = [];
					photoList.push(this.posterImage)
					uni.previewImage({
						urls: photoList,
						current: this.posterImage
					});
				} else {
					this.$util.Tips({
						title: '您的海报尚未生成'
					});
				}
			},
			// app分享
			// #ifdef APP-PLUS
			appShare(scene) {
				let that = this
				let routes = getCurrentPages(); // 获取当前打开过的页面路由数组
				let curRoute = routes[routes.length - 1].$page.fullPath // 获取当前页面路由，也就是最后一个打开的页面路由
				uni.share({
					provider: "weixin",
					scene: scene,
					type: 0,
					href: `${HTTP_REQUEST_URL}${curRoute}&spid=${that.uid}`,
					title: that.storeInfo.store_name,
					summary: that.storeInfo.store_info,
					imageUrl: that.storeInfo.small_image,
					success: function(res) {
						// uni.showToast({
						// 	title: '分享成功',
						// 	icon: 'success'
						// })
						// that.posters = false;
					},
					fail: function(err) {
						uni.showToast({
							title: '分享失败',
							icon: 'none',
							duration: 2000
						})
						// that.posters = false;
					}
				});
			},
			// #endif
			closeChange: function() {
				this.$set(this.sharePacket, 'isState', true);
			},
			goActivity: function(e) {
				let item = e;
				if (item.type == 1) {
					uni.navigateTo({
						url: `/pages/activity/goods_details/index?id=${item.id}&type=1&time=${item.time}&status=1`
					});
				} else if (item.type == 2) {
					uni.navigateTo({
						url: `/pages/activity/goods_bargain_details/index?id=${item.id}&spid=${this.uid}`
					});
				} else {
					uni.navigateTo({
						url: `/pages/activity/goods_details/index?id=${item.id}&type=3`
					});
				}
			},
			/**
			 * 购物车手动填写
			 *
			 */
			iptCartNum: function(e) {
				this.$set(this.attr.productSelect, 'cart_num', e);
			},
			backTap() {
				let pages = getCurrentPages(); // 获取当前打开过的页面路由数，
				if (pages.length > 1) {
					uni.navigateBack()
				} else {
					uni.switchTab({
						url: '/pages/index/index'
					});
				}
			},
			/*
			 * 获取用户信息
			 */
			getUserInfo: function() {
				let that = this;
				getUserInfo().then(res => {
					that.$set(that.sharePacket, 'isState', that.sharePacket.priceName != 0 ? false : true);
					that.$set(that, 'uid', res.data.uid);
					that.$set(that, 'is_money_level', res.data.is_money_level);
					that.userInfo = res.data;
				});
			},
			/**
			 * 购物车数量加和数量减
			 *
			 */
			ChangeCartNum: function(changeValue) {
				//changeValue:是否 加|减
				//获取当前变动属性
				let productSelect = this.productValue[this.attrValue];
				//如果没有属性,赋值给商品默认库存
				if (productSelect === undefined && !this.attr.productAttr.length) productSelect = this.attr
					.productSelect;
				//无属性值即库存为0；不存在加减；
				if (productSelect === undefined) return;
				let stock = productSelect.stock || 0;
				let num = this.attr.productSelect;
				if (changeValue) {
					num.cart_num++;
					if (num.cart_num > stock) {
						this.$set(this.attr.productSelect, 'cart_num', stock ? stock : 1);
						this.$set(this, 'cart_num', stock ? stock : 1);
					}
				} else {
					num.cart_num--;
					if (num.cart_num < 1) {
						this.$set(this.attr.productSelect, 'cart_num', 1);
						this.$set(this, 'cart_num', 1);
					}
				}
			},
			//滑动轮播图选择商品
			changeSwitch(e) {
				let productSelect = this.skuArr[e];
				let skuList = productSelect.suk.split(',');
				skuList.forEach((i, index) => {
					this.$set(this.attr.productAttr[index], 'index', skuList[index]);
				})
				if (productSelect) {
					this.getGoodsCtivity(productSelect.unique);
					this.$set(this.attr.productSelect, 'image', productSelect.image);
					this.$set(this.attr.productSelect, 'price', productSelect.price);
					this.$set(this.attr.productSelect, 'ot_price', productSelect.ot_price);
					this.$set(this.attr.productSelect, 'stock', productSelect.stock);
					this.$set(this.attr.productSelect, 'unique', productSelect.unique);
					this.$set(this.attr.productSelect, 'cart_num', 1);
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.$set(this, 'attrValue', productSelect.suk);
					this.$set(this, 'attrTxt', '已选择');
					this.attrPicIndex = e;
				}
			},
			attrPicIndexs(e){
				this.attrPicIndex = e;
			},
			selectSwiper(){
				this.attrPicIndex = 0;
				this.isSwiper = true;
				this.ChangeAttr(this.skuArr[0].suk);
				this.changeSwitch(0);
				this.$set(this,'productSwiper', this.storeInfo.slider_image);
				this.$set(this,'productVideoLink',this.storeInfo.video_link);
				this.showSkuBox = false;
			},
			changeAttrPic(index){
				let imgList = []
				this.skuArr.forEach(i=>{
					imgList.push(i.image)
				})
				this.isSwiper = false;
				// this.ChangeAttr(this.skuArr[index].suk);
				this.changeSwitch(index);
				this.$set(this,'productVideoLink','');
				this.$set(this,'productSwiper', imgList);
				this.showSkuBox = true;
				this.scrollToView = (index-2)*48;
			},
			attrVal(val) {
				this.$set(this.attr.productAttr[val.indexw], 'index', this.attr.productAttr[val.indexw].attr_values[val
					.indexn]);
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
					if (productSelect.stock >= 0) {
						this.getGoodsCtivity(productSelect.unique);
						this.$set(this.attr.productSelect, 'sum_price', productSelect.price);
						this.$set(this.attr.productSelect, 'image', productSelect.image);
						this.$set(this.attr.productSelect, 'price', productSelect.price);
						this.$set(this.attr.productSelect, 'ot_price', productSelect.ot_price);
						this.$set(this.attr.productSelect, 'stock', productSelect.stock);
						this.$set(this.attr.productSelect, 'unique', productSelect.unique);
						this.$set(this.attr.productSelect, 'cart_num', this.storeInfo.min_qty ? this.storeInfo.min_qty : 1);
						this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
						this.$set(this, 'attrValue', res);
						this.$set(this, 'attrTxt', '已选择');
					} else {
						this.getGoodsCtivity(productSelect.unique);
						this.$set(this.attr.productSelect, 'image', this.storeInfo.image);
						this.$set(this.attr.productSelect, 'price', this.storeInfo.price);
						this.$set(this.attr.productSelect, 'ot_price', this.storeInfo.ot_price);
						this.$set(this.attr.productSelect, 'stock', 0);
						this.$set(this.attr.productSelect, 'unique', '');
						this.$set(this.attr.productSelect, 'cart_num', this.storeInfo.min_qty ? this.storeInfo.min_qty : 1);
						this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
						this.$set(this, 'attrValue', '');
						this.$set(this, 'attrTxt', '请选择');
					}
				}
			},
			// 推荐商品
			getGoodsList() {
				let that = this;
				getProductRecommend(that.id).then(res => {
					that.good_list = res.data || [];

				}).catch(err => {
					return this.$util.Tips({
						title: err
					});
				})
			},
			// 获取产品活动详情
			getGoodsCtivity(unique) {
				let that = this;
				getProductCtivity(that.id, {unique: unique ? unique : ''}).then(res => {
					that.$set(that, 'activityBg', res.data.activity_background.image || '');
					that.$set(that, 'activity', res.data.activity ? res.data.activity : []);
					that.$set(that, 'discountsData', res.data.discounts_products ? res.data.discounts_products : []); //套餐数据
					let promotions = res.data.promotions[0];
					if (res.data.promotions.length ) {
						let discount = that.$util.$h.Div(promotions.discount, 100);
						let discountPrice = that.$util.$h.Mul(discount, this.storeInfo.price);
						that.$set(that.limitInfo, 'price', this.storeInfo.price || 0);
						that.$set(that.limitInfo, 'datatime', promotions.stop_time || 0);
						that.$set(that.limitInfo, 'discount', discount);
						that.$set(that.limitInfo, 'discount_price', discountPrice);
					}
					that.$set(that.discountInfo, 'discount', res.data.promotions);
					res.data.coupons.map(item=>{
						this.$set(item,'ruleshow',false);
					})
					that.$set(that.coupon,'list',res.data.coupons);
					that.$set(that,'computedPrice',res.data.computed);
					that.$set(this.attr.productSelect, 'pay_price', res.data.computed.deduction.pay_price);
					that.showSkeleton = false;
				}).catch(err => {
					return this.$util.Tips({
						title: err
					});
				})

			},
			/**
			 * 获取产品详情
			 *
			 */
			getGoodsDetails: function() {
				let that = this;
				getProductDetail(that.id).then(res => {
						let storeInfo = res.data.storeInfo;
						that.$set(that, 'storeInfo', storeInfo);
						this.storeInfo = storeInfo;
						this.brokerage = res.data.brokerage;
						this.productSwiper = storeInfo.slider_image;
						this.productVideoLink = storeInfo.video_link;
						this.description = storeInfo.description.replace(/<img/gi, '<img style="max-width: 100%;display:block;"');
						this.$set(this.ensureInfo, 'ensure', storeInfo.ensure);
						let ensureTitle = storeInfo.ensure.map(item=> item.name).join(' · ');
						let specsTitle = storeInfo.specs.map(item=> item.name).join(' · ');
						this.$set(this.ensureInfo, 'ensureTitle', ensureTitle);
						this.$set(this.storeInfo, 'specsTitle', specsTitle);
						this.$set(this.specsInfo, 'specs', storeInfo.specs);
						this.$set(this.attr, 'productAttr', res.data.productAttr);
						this.$set(this, 'productValue', res.data.productValue);
						// this.skuArr = Object.values(res.data.productValue);
						this.skuArr = Object.values(res.data.productValue).sort((a, b) => a.suk.localeCompare(b.suk));
						if (!this.skuArr.length) {
							this.skuArr = [{
								image: this.storeInfo.image,
								suk: this.storeInfo.store_name,
								price: this.storeInfo.price
							}];
						}
						that.$set(that, 'is_vip', res.data.storeInfo.is_vip);
						that.siteName = res.data.site_name;
						that.posterTitle = res.data.product_poster_title;
						that.$set(that, 'reply', res.data.reply);
						that.$set(that,'communityList',res.data.elegant_list);
						that.$set(that,'communityCount',res.data.elegant_count);
						that.$set(that, 'replyCount', res.data.replyCount);
						that.$set(that, 'replyChance', res.data.replyChance);
						that.$set(that.sharePacket, 'priceName', res.data.priceName);
						that.$set(that, 'storeSelfMention', res.data.store_self_mention);
						that.$set(that, 'shareQrcode', res.data.share_qrcode);
						that.$set(that, 'routineContact', Number(res.data.routine_contact_type));
						uni.setNavigationBarTitle({
							title: storeInfo.store_name.substring(0, 13) + '...'
						});
						that.$set(that, 'diff', that.$util.$h.Sub(storeInfo.price, storeInfo.vip_price));
						that.getGoodsList();
						// #ifdef H5
						that.ShareInfo();
						// #endif
						if (that.isLogin) {
							that.getUserInfo();
						}
						that.DefaultSelect();
						that.getCartCount();
						that.preloadImage();
						
					}).catch(err => {
						//状态异常返回上级页面
						return that.$util.Tips({
							title: err.toString()
						}, {
							tab: 3,
							url: 1
						});
					});

			},
			preloadImage: function () {
				// 预加载海报生成动图
				let that = this;
			    uni.downloadFile({
					url: that.imgHost + '/statics/images/poster_loading.png'
			    });
			},
			/**
			 * 默认选中属性
			 *
			 */
			DefaultSelect: function() {
				let productAttr = this.attr.productAttr;
				let valueobj = [];
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
					this.$set(productAttr[i], 'index', value[i]);
				}
				//sort();排序函数:数字-英文-汉字；
				let productSelect = this.productValue[value.join(',')];
				this.$set(this.attr.productSelect, 'store_name', this.storeInfo.store_name);
				if (productSelect && productAttr.length) {
					this.getGoodsCtivity(productSelect.unique);
					this.$set(this.attr.productSelect, 'image', productSelect.image);
					this.$set(this.attr.productSelect, 'price', productSelect.price);
					this.$set(this.attr.productSelect, 'ot_price', productSelect.ot_price);
					this.$set(this.attr.productSelect, 'stock', productSelect.stock);
					this.$set(this.attr.productSelect, 'unique', productSelect.unique);
					this.$set(this.attr.productSelect, 'cart_num', this.storeInfo.min_qty ? this.storeInfo.min_qty : 1);
					this.$set(this, 'attrValue', value.join(','));
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.$set(this, 'attrTxt', '已选择');
				} else if (!productSelect && productAttr.length) {
					this.getGoodsCtivity();
					this.$set(this.attr.productSelect, 'image', this.storeInfo.image);
					this.$set(this.attr.productSelect, 'price', this.storeInfo.price);
					this.$set(this.attr.productSelect, 'ot_price', this.storeInfo.ot_price);
					this.$set(this.attr.productSelect, 'stock', 0);
					this.$set(this.attr.productSelect, 'unique', '');
					this.$set(this.attr.productSelect, 'cart_num', this.storeInfo.min_qty ? this.storeInfo.min_qty : 1);
					this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
					this.$set(this, 'attrValue', '');
					this.$set(this, 'attrTxt', '请选择');
				} else if (!productSelect && !productAttr.length) {
					this.getGoodsCtivity();
					this.$set(this.attr.productSelect, 'image', this.storeInfo.image);
					this.$set(this.attr.productSelect, 'price', this.storeInfo.price);
					this.$set(this.attr.productSelect, 'ot_price', this.storeInfo.ot_price);
					this.$set(this.attr.productSelect, 'stock', this.storeInfo.stock);
					this.$set(this.attr.productSelect, 'unique', this.storeInfo.unique || '');
					this.$set(this.attr.productSelect, 'cart_num', this.storeInfo.min_qty ? this.storeInfo.min_qty : 1);
					this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
					this.$set(this, 'attrValue', '');
					this.$set(this, 'attrTxt', '请选择');
				}
			},
			openPerferentDrawer(){
				this.showPerferentDrawer = true;
			},
			/**
			 *
			 *
			 * 收藏商品
			 */
			setCollect: Debounce(function() {
				if (this.isLogin === false) {
					toLogin();
				} else {
					let that = this;
					if (this.storeInfo.userCollect) {
						collectDel(this.storeInfo.id).then(res => {
							that.$set(that.storeInfo, 'userCollect', !that.storeInfo.userCollect);
							return that.$util.Tips({
								title: res.msg
							});
						});
					} else {
						collectAdd(this.storeInfo.id).then(res => {
							that.$set(that.storeInfo, 'userCollect', !that.storeInfo.userCollect);
							return that.$util.Tips({
								title: res.msg
							});
						});
					}
				}
			}),
			/**
			 * 打开属性插件
			 */
			selecAttr() {
				this.currentPage = false;
				this.$set(this.attr, 'cartAttr', true);
				this.$set(this, 'isOpen', true);
			},
			onMyEvent: function() {
				this.$set(this.attr, 'cartAttr', false);
				this.$set(this, 'isOpen', false);
			},
			myEnsure() {
				this.$set(this.ensureInfo, 'show', false);
			},
			mySpecs() {
				this.$set(this.specsInfo, 'show', false);
			},
			myDiscount() {
				this.$set(this.discountInfo, 'show', false);
			},
			onConfirm(){
				this.$set(this.attr, 'cartAttr', false);
				this.isOpen = true;
				if(this.cartType == 'cart'){
					this.goCat(undefined,'cart');
				}else{
					this.goCat(true,'buy');
				}
			},
			/**
			 * 打开属性加入购物车
			 *
			 */
			joinCart: function(e) {
				this.currentPage = false;
				//是否登录
				if (!this.isLogin) {
					toLogin();
				} else {
					this.goCat(undefined,'cart');
				}
			},
			/*
			 * 加入购物车
			 */
			goCat: function(news,cartType) {
				let that = this,productSelect = that.productValue[this.attrValue];
				this.cartType = cartType;
				//打开属性
				if (that.attrValue) {
					//默认选中了属性，但是没有打开过属性弹窗还是自动打开让用户查看默认选中的属性
					that.attr.cartAttr = !that.isOpen ? true : false;
				} else {
					if (that.isOpen) that.attr.cartAttr = true;
					else that.attr.cartAttr = !that.attr.cartAttr;
				}
				//只有关闭属性弹窗时进行加入购物车
				if (that.attr.cartAttr === true && that.isOpen === false) return (that.isOpen = true);
				//如果有属性,没有选择,提示用户选择
				if (that.attr.productAttr.length && productSelect === undefined && that.isOpen === true)
					return that.$util.Tips({
						title: '产品库存不足，请选择其它属性'
					});
				if (that.attr.productSelect.cart_num <= 0) {
					that.attr.productSelect.cart_num = 1
					that.isOpen = false
					return that.$util.Tips({
						title: '请先选择属性'
					});
				}
				let q = {
					productId:that.id,
					cartNum: that.attr.productSelect.cart_num,
					new: news === undefined ? 0 : 1,
					uniqueId: that.attr.productSelect !== undefined ? that.attr.productSelect.unique : ''
				};
				postCartAdd(q).then(function(res) {
						that.isOpen = false;
						that.attr.cartAttr = false;
						if (news) {
							//领券下单
							if(that.computedPrice.coupon && that.computedPrice.coupon.id){
								if(that.computedPrice.coupon.used == null){
									setCouponReceive(that.computedPrice.coupon.id).then(resp => {
										uni.navigateTo({
											url: '/pages/goods/order_confirm/index?new=1&cartId=' + res.data.cartId + '&couponId=' + resp.data.id + '&couponTitle=' + that.computedPrice.coupon.coupon_title
										});
									}).catch(err => {
										uni.navigateTo({
											url: '/pages/goods/order_confirm/index?new=1&cartId=' + res.data.cartId
										});
									})
								}else{
									uni.navigateTo({
										url: '/pages/goods/order_confirm/index?new=1&cartId=' + res.data.cartId + '&couponId=' + that.computedPrice.coupon.id + '&couponTitle=' + that.computedPrice.coupon.coupon_title
									});
								}
							}else{
								uni.navigateTo({
									url: '/pages/goods/order_confirm/index?new=1&cartId=' + res.data.cartId
								});
							}
							
						} else {
							that.$util.Tips({
								title: '添加购物车成功',
								success: () => {
									that.cartNum = res.data.cartNum;
									that.getCartCount(true);
								}
							});
						}
					})
					.catch(err => {
						that.isOpen = false;
						return that.$util.Tips({
							title: err
						});
					});
			},
			/**
			 * 获取购物车数量
			 * @param boolean 是否展示购物车动画和重置属性
			 */
			getCartCount: function(isAnima) {
				let that = this;
				const isLogin = that.isLogin;
				if (isLogin) {
					getCartCounts().then(res => {
						that.CartCount = res.data.count;
						this.$store.commit('indexData/setCartNum', that.CartCount + '')
						//加入购物车后重置属性
						if (isAnima) {
							that.animated = true;
							setTimeout(function() {
								that.animated = false;
							}, 500);
						}
					});
				}
			},
			/**
			 * 立即购买
			 */
			goBuy: function(e) {
				this.currentPage = false;
				if (!this.isLogin) {
					toLogin();
				} else {
					this.goCat(true,'buy');
				}
			},
			listenerActionSheet: function() {
				this.onMyEvent();
				this.currentPage = false
				this.posters = true;
				this.goPoster();
			},
			// 分享关闭
			listenerActionClose: function() {
				this.posters = false;
			},
			//隐藏海报
			posterImageClose: function() {
				this.posterImageStatus = false;
				this.posters = false;
			},
			//替换安全域名
			setDomain: function(url) {
				url = url ? url.toString() : '';
				//本地调试打开,生产请注销
				if (url.indexOf('https://') > -1) return url;
				else return url.replace('http://', 'https://');
			},
			//图片转符合安全域名路径
			downloadFileImage(url) {
				url = this.setDomain(url)
				return new Promise((resolve, reject) => {
					let that = this;
					uni.downloadFile({
						url: url,
						success: function(res) {
							resolve(res.tempFilePath);
						},
						fail: function(err) {
							console.error(err.errMsg);
						}
					});
				})
			},
			/**
			 * 生成海报
			 */
			async goPoster() {
				let that = this;
				let storeImage = await this.downloadFileImage(this.storeInfo.image);
				let posterbackgd = await this.downloadFileImage(this.imgHost + '/statics/images/product/posterbackgd.png');
				// #ifdef MP-WEIXIN
				// 小程序端获取小程序码
				let res = await getProductCode(that.id);
				if(!res.data.code){
					this.posters = false;
					return that.$util.Tips({
						title: '小程序二维码需要发布正式版后才能获取到'
					});
				}
				let PromotionCode = await this.downloadFileImage(res.data.code)
				// #endif
				// #ifdef H5
				let PromotionCode = '';
				if(this.$wechat.isWeixin()){
					// 公众号端获取公众号二维码
					let res = await getProductCode(that.id);
					PromotionCode = await this.downloadFileImage(res.data.code)
				}else{
					// h5端获取本地生成的二维码
					PromotionCode = this.PromotionCode;
				}
				// #endif
				// #ifdef APP-PLUS
				let PromotionCode = this.PromotionCode;
				// #endif
				that.$set(that, 'canvasStatus', true);
				
				let arr2 = [posterbackgd, storeImage, PromotionCode];
				that.$nextTick(function() {
					setTimeout(()=>{
						that.$util.PosterCanvas(that.fontColor, that.themeColor, that.siteName,arr2, 
						that.storeInfo.store_name, 
						that.storeInfo.price, 
						that.storeInfo.ot_price, 
						that.posterTitle,
							function(tempFilePath) {
								that.$set(that, 'posterImage', tempFilePath);
								that.$set(that, 'posterImageStatus', true);
								that.$set(that, 'canvasStatus', false);
								that.$set(that, 'actionSheetHidden', !that.actionSheetHidden);
							});
					},1000)
				})
			},

			/*
			 * 保存到手机相册
			 */
			// #ifdef MP
			savePosterPath: function() {
				let that = this;
				uni.getSetting({
					success(res) {
						if (!res.authSetting['scope.writePhotosAlbum']) {
							uni.authorize({
								scope: 'scope.writePhotosAlbum',
								success() {
									uni.saveImageToPhotosAlbum({
										filePath: that.posterImage,
										success: function(res) {
											that.posterImageClose();
											that.$util.Tips({
												title: '保存成功',
												icon: 'success'
											});
										},
										fail: function(res) {
											that.$util.Tips({
												title: '保存失败'
											});
										}
									});
								}
							});
						} else {
							uni.saveImageToPhotosAlbum({
								filePath: that.posterImage,
								success: function(res) {
									that.posterImageClose();
									that.$util.Tips({
										title: '保存成功',
										icon: 'success'
									});
								},
								fail: function(res) {
									that.$util.Tips({
										title: '保存失败'
									});
								}
							});
						}
					}
				});
			},
			// #endif
			//#ifdef APP-PLUS
			savePosterPath() {
				let that = this
				uni.saveImageToPhotosAlbum({
					filePath: that.posterImage,
					success: function(res) {
						that.posterImageClose();
						that.$util.Tips({
							title: '保存成功',
							icon: 'success'
						});
					},
					fail: function(res) {
						that.$util.Tips({
							title: '保存失败'
						});
					}
				});
			},
			// #endif


			//#ifdef H5
			ShareInfo() {
				let data = this.storeInfo;
				let href = location.href;
				if (this.$wechat.isWeixin()) {
					getUserInfo().then(res => {
						href = href.indexOf('?') === -1 ? href + '?spid=' + res.data.uid : href + '&spid=' + res.data.uid;
						let configAppMessage = {
							desc: data.store_info,
							title: data.store_name,
							link: href,
							imgUrl: data.image
						};
						this.$wechat
							.wechatEvevt(['updateAppMessageShareData', 'updateTimelineShareData',
								'onMenuShareAppMessage',
								'onMenuShareTimeline'
							], configAppMessage)
							.then(res => {})
							.catch(err => {});
					});
				}
			},
			//#endif
			goDiscounts() {
				uni.navigateTo({
					url: '/pages/goods_details/discountsGoodsList?id=' + this.id
				})
			},
			goPage(type, url){
				if(type == 1){
					uni.navigateTo({
						url
					})
				}else if(type == 2){
					uni.switchTab({
						url
					})
				}else if(type == 3){
					uni.navigateBack();
				}

			},
			ChangCouponsUseState(index) {
				let that = this;
				that.coupon.list[index].is_use = true;
				that.$set(that.coupon, 'list', that.coupon.list);
				that.$set(that.coupon, 'coupon', false);
			},
			/**
			 * 领取完毕移除当前页面领取过的优惠券展示
			 */
			ChangCoupons: function(e) {

			},
			ruleToggle(index){
				this.coupon.list[index].ruleshow = !this.coupon.list[index].ruleshow;
			},
			goGoodCall() {
				let userInfo = {}
				if(typeof this.userInfo === 'string'){
					userInfo = JSON.parse(this.userInfo)
				}else{
					userInfo = this.userInfo
				}
				let url = `/pages/extension/customer_list/chat?productId=${this.id}`
				let obj = {
					store_name: this.storeInfo.store_name,
					path: `/pages/goods_details/index?id=${this.storeInfo.id}`,
					image:this.storeInfo.image
				}
				this.$util.getCustomer(userInfo,url,obj,1)
			},
			copyShare(){
				let that = this;
				uni.setClipboardData({
					data: that.storeInfo.share_content || that.storeInfo.store_name,
					success:()=>{
						uni.showToast({
							title: '复制成功，快去分享吧',
							icon: 'none'
						})
					}
				})
			},
			borkerageShare(){
				if(this.isLogin){
					if(this.userInfo.is_promoter){
						//分销员直接去分享
						uni.navigateTo({
							url: '/pages/goods/goodsShare/index?id=' + this.id
						})
					}else if(this.userInfo.is_promoter == 0 && this.storeBrokerageStatus == 1){
						// 不是分销员并且是指定分销模式就跳转去申请页面
						uni.navigateTo({
							url: '/pages/users/distributor/apply'
						})
					}else if(this.userInfo.is_promoter == 0 && this.storeBrokerageStatus == 3){
						// 不是分销员并且是指定满额模式就跳转去满额分销页面
						uni.navigateTo({
							url: '/pages/users/distributor/index'
						})
					}
				}else{
					toLogin();
				}
			}
		},
		onPageScroll(object) {
			this.$set(this,'currentPage',false);
			if (object.scrollTop > 340) {
				this.pageScrollStatus = true;
			} else if (object.scrollTop < 340) {
				this.pageScrollStatus = false;
			}
			// 触发图片懒加载
			uni.$emit('scroll');
		},
	};
</script>

<style lang="scss">
	/deep/uni-video {
		width: 100% !important;
	}

	/deep/video {
		width: 100% !important;
	}
	.z-99{
		z-index: 99;
	}
	.menu_box{
		width: 154rpx;
		height: 58rpx;
		border-radius: 29rpx;
		z-index: 999;
	}
	.menu_line{
		width: 1px;
		height: 30rpx;
		background: #B3B3B3;
		margin:0 20rpx;
	}
	.opac{
		background-color: rgba(255,255,255,0.6);
		border: 1rpx solid rgba(0,0,0,0.1)
	}
	.info_card {
	  background: linear-gradient(180deg, #ffffff 0%, #ffffff 54%, rgba(255, 255, 255, 0) 100%);
	  margin-top: -34rpx;
	}
	.mirror-image{
		height: 34rpx;
		overflow-y: hidden;
		position: relative;
		&:after{
			content: '';
			width: 100%;
			height: 34rpx;
			position: absolute;
			left: 0;
			top: 0;
			background: rgba(0, 0, 0, 0.2);
		}
		image{
			width: 100%;
			transform: scaleY(-1);
		}
	}
	.bb-f5{
		border-bottom: 1px solid #f5f5f5;
	}
	.share-tag{
		width: 154rpx;
		height: 50rpx;
		background: #423F3C;
		border-radius: 30rpx;
		padding: 0 12rpx;
		color: #FFF0D1;
		image{
			width: 26rpx;
			height: 26rpx;
		}
	}
	.atmosphere-card{
		margin-top: -34rpx;
		background-size: cover;
		background-repeat: no-repeat;
	}
	.svip-badge{
		position: relative;
		bottom: 10rpx;
		margin-left: 8rpx;
		width: 142rpx;
		height: 40rpx;
		background-image: url('@/static/img/svip_tag.png');
		background-size: cover;
		color: #604210;
	}
	.price-badge{
		position: relative;
		bottom: 10rpx;
		margin-left: 8rpx;
		width: 92rpx;
		height: 40rpx;
		background-image: url('@/static/img/price_badge.png');
		background-size: cover;
		color: #604210;
	}
	.bottom-6{
		bottom: 6rpx;
	}
	.break_word {
	  overflow-wrap: break-word;
	  white-space: normal;
	}
	.white-nowrap {
	  white-space: nowrap;
	}
	.text-line{
		text-decoration: line-through;
	}
	.w-530{
		width: 530rpx;
	}
	.w-524{
		width: 524rpx;
	}
	.w-76{
		width: 76rpx;
	}
	.active_pic {
	  display: flex;
	  justify-content: center;
	  align-items: center;
	  background-color: #fff;
	  border-radius: 16rpx;
	  border: 1px solid #333;
	  padding: 4rpx;
	  image {
	    width: 68rpx;
	    height: 68rpx;
	    border-radius: 12rpx;
	  }
	}
	.scroll_pic {
		height:80rpx;
	  image {
	    width: 80rpx;
	    height: 80rpx;
	    border-radius: 16rpx;
	  }
	}
	.icon-ic_star1{
		color: var(--view-theme);
	}
	.join_cart{
		background-color: var(--view-bntColor);
	}
	.text-primary-con{
		color: var(--view-theme);
	}
	.bg-primary-light{
		background: var(--view-minorColorT);
	}
	.recommend_pro ~ .recommend_pro{
		margin-left: 38rpx;
	}
	.join_cart1{
	  background-color: #FAAD14;
	}
	.cart_icon{
	  width: 76rpx;
	  height: 72rpx;
	  background: rgba(250, 173, 20, 0.35);
	  border-radius: 20px 0 0 20px;
	  padding-left: 8rpx;
	}
	.daoshou{
	  border-radius: 0 20px 20px 0;
	  background: rgba(250, 173, 20, 1);
	}
	.page_footer{
		backdrop-filter: blur(10px);
		border-top: 1px solid #f5f5f5;
	}
	.num-badge{
		color: #fff;
		position: absolute;
		font-size: 18rpx;
		padding: 2rpx 10rpx 3rpx;
		border-radius: 200rpx;
		top: 0;
		right: -10rpx;
	}
	.bg-transparent{
		background: transparent;
	}
	.generate-posters {
		width: 100%;
		background-color: #fff;
		position: fixed;
		left: 0;
		bottom: 0;
		z-index: 388;
		transform: translate3d(0, 100%, 0);
		transition: all 0.3s cubic-bezier(0.25, 0.5, 0.5, 0.9);
		border-top: 1rpx solid #eee;
		border-radius: 40rpx 40rpx 0 0;

		.generateCon {
			height: 220rpx;
		}

		.generateClose {
			width: 654rpx;
			height: 72rpx;
			background: #F5F5F5;
			border-radius: 36rpx;
			font-size: 26rpx;
			font-weight: 500;
			margin: 0 auto 40rpx;
		}

		.item {
			.pictrue {
				width: 86rpx;
				height: 86rpx;
				border-radius: 50%;
				margin: 0 auto 12rpx auto;

				image {
					width: 100%;
					height: 100%;
					border-radius: 50%;
				}
			}
		}
	}

	.generate-posters.on {
		transform: translate3d(0, 0, 0);
	}

	.generate-posters .item {
		flex: 1;
		text-align: center;
		font-size: 24rpx;
	}
	.canvas {
		z-index: 300;
		width: 750px;
		height: 1300px;
		position: relative;
		bottom: -10000rpx;
	}
	.poster-mask{
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: rgba(0,0,0,0.8);
		z-index: 30;
		backdrop-filter: blur(5px);
	}
	.share-box{
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 999;
		image{
			width: 100%;
			height: 100vh;
		}
	}
	.poster-loading{
		position: relative;
		width: 100%;
		z-index: 31;
	}
	.fixed-center{
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 40;
	}
	.poster-pop {
		width: 460rpx;
		height: 800rpx;
		position: fixed;
		left: 50%;
		transform: translateX(-50%);
		z-index: 399;
		top: 50%;
		margin-top: -559rpx;
	}

	.poster-pop image {
		width: 100%;
		height: 100%;
		display: block;
		border-radius: 18rpx;
	}

	.poster-pop .close {
		width: 46rpx;
		height: 75rpx;
		position: fixed;
		right: 0;
		top: -73rpx;
		display: block;
	}

	.poster-pop .save-poster {
		background-color: #df2d0a;
		font-size: ：22rpx;
		color: #fff;
		text-align: center;
		height: 76rpx;
		line-height: 76rpx;
		width: 100%;
	}

	.poster-pop .keep {
		color: #fff;
		text-align: center;
		font-size: 25rpx;
		margin-top: 10rpx;
	}
	.conter {
		display: block;
		overflow: auto;
	}
</style>
