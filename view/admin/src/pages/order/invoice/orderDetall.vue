<template>
    <div class="order_detail" v-if="orderDetail.userInfo">
        <div class="msg-box">
            <div class="box-title">收货信息</div>
            <div class="msg-wrapper">
                <div class="msg-item">
                    <div class="item">
                        <span>用户昵称：</span>{{orderDetail.userInfo.nickname}}
                    </div>
                    <div class="item">
                        <span>收货人：</span>{{orderDetail.orderInfo.real_name}}
                    </div>
                </div>
                <div class="msg-item">
                    <div class="item">
                        <span>联系电话：</span>{{orderDetail.orderInfo.user_phone}}
                    </div>
                    <div class="item">
                        <span>收货地址：</span>{{orderDetail.orderInfo.user_address}}
                    </div>
                </div>
            </div>
        </div>
        <div class="msg-box" style="border: none;">
            <div class="box-title">订单信息</div>
            <div class="msg-wrapper">
                <div class="msg-item">
                    <div class="item">
                        <span>订单ID：</span>{{orderDetail.orderInfo.order_id}}
                    </div>
                    <div class="item" style="color: red">
                        <span style="color: red">订单状态：</span>{{orderDetail.orderInfo._status._title}}
                    </div>
                </div>
                <div class="msg-item">
                    <div class="item">
                        <span>商品总数：</span>{{orderDetail.orderInfo.total_num}}
                    </div>
                    <div class="item">
                        <span>商品总价：</span>￥{{orderDetail.orderInfo.total_price}}
                    </div>
                </div>
                <div class="msg-item">
                    <div class="item">
                        <span>交付邮费：</span>￥{{orderDetail.orderInfo.pay_postage}}
                    </div>
                    <div class="item">
                        <span>优惠券金额：</span>￥{{orderDetail.orderInfo.coupon_price}}
                    </div>
                </div>
                <div class="msg-item">
                    <div class="item">
                        <span>会员商品优惠：</span>￥{{orderDetail.orderInfo.vip_true_price||0.00}}
                    </div>
                    <div class="item">
                        <span>积分抵扣：</span>￥{{orderDetail.orderInfo.deduction_price||0.00}}
                    </div>
                </div>
                <div class="msg-item">
                    <div class="item">
                        <span>实际支付：</span>{{orderDetail.orderInfo.pay_price}}
                    </div>
                    <div class="item">
                        <span>创建时间：</span>{{orderDetail.orderInfo.add_time}}
                    </div>
                </div>
                <div class="msg-item">
                    <div class="item">
                        <span>支付方式：</span>{{orderDetail.orderInfo._status._payType}}
                    </div>
                    <div class="item">
                        <span>推广人：</span>{{orderDetail.userInfo.spread_name}}
                    </div>
                </div>
                <div class="msg-item">
                    <div class="item">
                        <span>商家备注：</span>{{orderDetail.orderInfo.mark}}
                    </div>
                </div>
            </div>
        </div>
        <div class="goods-box">
            <Table :columns="columns1" :data="orderList">
                <template slot-scope="{ row, index }" slot="id">
                    {{row.productInfo.id}}
                </template>
                <template slot-scope="{ row, index }" slot="name">
                    <div class="product_info">
                        <img :src="row.productInfo.image" alt="">
                        <p>{{row.productInfo.store_name}}</p>
                    </div>
                </template>
                <template slot-scope="{ row, index }" slot="className">
                    {{row.class_name}}
                </template>
                <template slot-scope="{ row, index }" slot="price">
                    {{row.productInfo.attrInfo.price}}
                </template>
                <template slot-scope="{ row, index }" slot="total_num">
                    {{orderDetail.orderInfo.total_num}}
                </template>
            </Table>
        </div>
        <Spin fix v-if="spinShow"></Spin>
    </div>
</template>

<script>
    import { orderInvoiceInfo } from '@/api/order'
    export default {
        name: "order_detail",
        props:{
            orderId:{
                type:String | Number,
                default:''
            },
        },
        data(){
            return {
                orderDetail:{},
                orderList:[],
                columns1: [
                    {
                        title: '商品ID',
                        slot: 'id',
                        maxWidth:80
                    },
                    {
                        title: '商品名称',
                        slot: 'name',
                        minWidth: 160
                    },
                    {
                        title: '商品分类',
                        slot: 'className'
                    },
                    {
                        title: '商品售价',
                        slot: 'price'
                    },
                    {
                        title: '商品数量',
                        slot: 'total_num'
                    }
                ],
                spinShow: false
            }
        },
        mounted() {
            this.getOrderInfo()
        },
        methods:{
            getOrderInfo(){
                this.spinShow = true;
                orderInvoiceInfo(this.orderId).then(res=>{
                    this.spinShow = false;
                    this.orderDetail = res.data;
                    this.orderList = res.data.orderInfo.cartInfo
                }).catch(err=>{
                    this.spinShow = false;
                    this.$Message.error(err.msg);
                    this.$emit('detall',false)
                })
            }
        }
    }
</script>

<style lang="stylus" scoped>
    .order_detail
        .msg-box
            border-bottom 1px solid #E8EAED
            .box-title
                padding-top 20px
                font-size 16px
                color #333
            .msg-wrapper
                margin-top 15px
                padding-bottom 10px
                .msg-item
                    display flex
                    .item
                        flex 1
                        margin-bottom 15px
                        span
                            color #333
            &:first-child .box-title
                padding-top 0
        .product_info
            display flex
            align-items center
            img
                width 36px
                height 36px
                border-radius 4px
                margin-right 10px


</style>
