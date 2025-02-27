<template>
  <Modal
    v-model="priceModals"
    scrollable
    title="订单改价"
    width="800"
  >
    <Form
      ref="formValidate"
      :model="formValidate"
      :label-width="80"
      @submit.native.prevent
    >
      <FormItem :label-width="0">
        <Table
          :columns="columns"
          :data="cartInfo"
          border
          no-data-text="暂无数据"
          highlight-row
          no-filtered-data-text="暂无筛选结果"
          max-height="350"
        >
          <template slot-scope="{ row, index }" slot="name">
            <div class="line1">{{ row.productInfo.store_name }}</div>
          </template>
          <template slot-scope="{ row, index }" slot="price">
            <div>¥{{ row.sum_price }}</div>
          </template>
          <template slot-scope="{ row, index }" slot="true_price">
            <div>¥{{ row.truePrice }}</div>
          </template>
          <template slot-scope="{ row, index }" slot="change_price">
            <div>
              <Input
                v-model="row.changePrice"
                type="text"
                @on-change="changeTap(row, index)"
              >
                <template #prepend>
                  <Select
                    v-model="row.priceType"
                    transfer
                    class="w-90"
                    @on-change="changeTap(row, index)"
                  >
                    <Option :value="1">一口价</Option>
                    <Option :value="2">减价</Option>
                    <Option :value="3">折扣</Option>
                  </Select>
                </template>
                <template #append>
                  <div class="fs-12 text-wlll-909399">
                    {{ row.priceType == 3 ? "%" : "元" }}
                  </div>
                </template>
              </Input>
            </div>
          </template>
          <template slot-scope="{ row, index }" slot="result_price">
            <div>¥{{ row.resultPrice || 0 }}</div>
          </template>
        </Table>
      </FormItem>
      <FormItem label="赠送积分：">
        <div class="acea-row row-between">
          <div>
            <InputNumber
              :precision="0"
              :min="0"
              :max="9999999999"
              v-model="formValidate.gain_integral"
            />
            <span class="ml-20">免邮：</span>
            <i-switch
              v-model="formValidate.is_postage"
              :true-value="1"
              :false-value="0"
              size="large"
            >
              <span slot="open">开启</span>
              <span slot="close">关闭</span>
            </i-switch>
          </div>
          <div>
            <div class="text-right">
              <span>应付邮费：¥{{ payPostage }}</span>
              <span class="pl-8"
                >实际支付邮费：<span class="text-wlll-f5222d"
                  >¥{{ resultPayPostage }}</span
                ></span
              >
            </div>
            <div class="text-right">
              <span
                >总价：¥{{ orderInfo.pay_price }}</span
              >
              <span class="pl-8"
                >修改后总价：<span class="text-wlll-f5222d"
                  >¥{{
                    (Number(resultPayPrice ? resultPayPrice : '0') + Number(resultPayPostage)).toFixed(2)
                  }}</span
                ></span
              >
            </div>
          </div>
        </div>
      </FormItem>
    </Form>
    <div slot="footer">
      <Button @click="cancel">取消</Button>
      <Button type="primary" @click="submit">确认</Button>
    </div>
  </Modal>
</template>

<script>
import { getOrdeUpdateInfo, putOrdeUpdate } from "@/api/order";
export default {
  name: "changePrice",
  data() {
    return {
      id: 0, //订单id；
      priceModals: false,
      columns: [
        {
          title: "商品",
          slot: "name",
          minWidth: 150,
        },
        {
          title: "单价",
          slot: "price",
          minWidth: 80,
        },
        {
          title: "数量",
          key: "cart_num",
          minWidth: 70,
        },
        {
          title: "应收金额",
          slot: "true_price",
          minWidth: 80,
        },
        {
          title: "改价",
          slot: "change_price",
          minWidth: 200,
        },
        {
          title: "改价后金额",
          slot: "result_price",
          minWidth: 80,
        },
      ],
      cartInfo: [],
      payPostage: 0, //应付邮费；
      payPrice: 0, //总价（不含邮费）
      resultPayPrice: 0, //修改后总价（不含邮费）
      formValidate: {
        is_postage: 0,
        gain_integral: 0,
        cart_info: [],
      },
      orderInfo:{
        pay_price:"",
      }
    };
  },
  computed: {
    //实际支付邮费；
    resultPayPostage() {
      return this.formValidate.is_postage ? 0 : this.payPostage;
    },
  },
  mounted() {},
  methods: {
    ordeUpdateInfo(id) {
      getOrdeUpdateInfo(id)
        .then((res) => {
          let orderInfo = res.data.orderInfo;
          this.orderInfo = orderInfo;
          this.formValidate.gain_integral = parseFloat(orderInfo.gain_integral);
          this.payPostage = parseFloat(orderInfo.pay_postage);
          this.payPrice = this.$computes.Sub(
            parseFloat(orderInfo.pay_price),
            this.payPostage
          );
          let resultPayPrice = 0;
          let cartInfo = res.data.cartInfo;
          cartInfo.forEach((item) => {
            item.priceType = 1;
            item.truePrice = parseFloat(item.truePrice);
            item.changePrice = item.truePrice;
            item.resultPrice = item.changePrice;
            resultPayPrice = this.$computes.Add(
              resultPayPrice,
              this.$computes.Mul(item.resultPrice, item.cart_num)
            );
          });
          this.cartInfo = cartInfo.filter(item=> item.is_gift == 0);
          this.resultPayPrice = resultPayPrice;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    cleanPrice(value) {
      // 移除非数字和非小数点的字符
      let cleanedValue = value.replace(/[^\d.]/g, '');
      // 确保只有一个小数点
      let parts = cleanedValue.split('.');
      if (parts.length > 2) {
        cleanedValue = parts[0] + '.' + parts.slice(1).join('');
      }
      // 确保小数点后最多有两位数字
      if (cleanedValue.includes('.')) {
        let [integerPart, decimalPart] = cleanedValue.split('.');
        cleanedValue = integerPart + '.' + decimalPart.slice(0, 2);
      }
      return cleanedValue;
    },
    // 计算改价
    changeTap(item, index) {
      item.changePrice = this.cleanPrice(item.changePrice);
      if (item.priceType == 1) {
        item.resultPrice = this.$computes.Mul(item.changePrice, 1); //乘一为了保留2为小数生效；
      } else if (item.priceType == 2) {
        let money = this.$computes.Sub(item.truePrice, item.changePrice);
        item.resultPrice = money > 0 ? money : 0;
      } else {
        if(item.changePrice > 100) return this.$Message.error("折扣不可超过100");
        item.resultPrice = this.$computes.Mul(
          item.truePrice,
          this.$computes.Div(item.changePrice, 100)
        );
      }
      this.cartInfo[index] = item;
      let resultPayPrice = 0;
      this.cartInfo.forEach((item) => {
        resultPayPrice = this.$computes.Add(
          resultPayPrice,
          this.$computes.Mul(item.resultPrice, item.cart_num)
        );
      });
      this.resultPayPrice = resultPayPrice;
    },
    cancel() {
      this.priceModals = false;
    },
    submit() {
      let data = [],disabled = null;
      this.cartInfo.forEach((item) => {
        if(item.priceType == 3 && item.changePrice > 100) {
          disabled = true
        }
        data.push({
          id: item.id,
          true_price: item.resultPrice,
        });
      });
      if(disabled) return this.$Message.error("折扣不可超过100");
      this.formValidate.cart_info = data;
      putOrdeUpdate(this.id, this.formValidate)
        .then(async (res) => {
          this.$Message.success(res.msg);
          this.priceModals = false;
          this.$emit("submitSuccess", res);
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
  },
};
</script>

<style scoped lang="less">
/deep/.ivu-table .ivu-table-header tr th {
  background-color: #f3f8fe !important;
}
/deep/.ivu-modal-body {
  padding: 16px 20px;
}
/deep/.ivu-input-group-append {
  background: #fff;
}
/deep/.ivu-input-group .ivu-input {
  border-right: 0;
}
.text-wlll-f5222d{
  color: #f5222d;
}
</style>
