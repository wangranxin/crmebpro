<template>
  <Modal
    v-model="modals"
    scrollable
    title="库存管理"
    width="800"
    @on-cancel="cancel"
    footer-hide
    class-name="vertical-center-modal"
  >
    <Card :bordered="false" :padding="0" dis-hover class="cards">
      <div class="batch" v-if="specType && goodsType != 6">
        <div class="name" @click="batchTap">
          批量<span class="iconfont iconxiayi"></span>
        </div>
        <div class="input acea-row row-center-wrapper" v-if="batchShow">
          <Input
            v-model="batchStock"
            type="number"
            class="width15"
            @on-change="inputTap"
          >
            <Select
              v-model="batchPm"
              slot="append"
              style="width: 60px"
              @on-change="batchStockTap"
            >
              <Option :value="1">入库</Option>
              <Option :value="0">出库</Option>
            </Select>
          </Input>
        </div>
      </div>
      <Table
        :columns="goodsType == 6 ? reservationColumns : columns"
        ref="selection"
        :data="stockData"
        :loading="loading"
        no-data-text="暂无数据"
        highlight-row
        no-filtered-data-text="暂无筛选结果"
        max-height="450"
      >
        <template slot-scope="{ row, index }" slot="image">
          <div class="product-data">
            <img class="image" :src="row.image" />
          </div>
        </template>
        <template slot-scope="{ row, index }" slot="time">
          <div
            v-for="(item, jindex) in row.reservationTimeData"
            :key="jindex"
            class="time h-32 lh-32px"
          >
            {{ item.show_time }}
          </div>
        </template>
        <template slot-scope="{ row, index }" slot="timeStock">
          <div
            v-for="(item, jindex) in row.reservationTimeData"
            :key="jindex"
            class="time h-32 lh-32px"
          >
            {{ item.stock }}
          </div>
        </template>
        <template slot-scope="{ row, index }" slot="timeNum">
          <InputNumber
            v-for="(item, jindex) in row.reservationTimeData"
            :key="jindex"
            class="time w-118 fs-12 mr14"
            :max="9999999999"
            :min="0"
            :precision="0"
            v-model="stockData[index].reservationTimeData[jindex].num"
          />
        </template>
        <template slot-scope="{ row, index }" slot="num">
          <div class="acea-row row-middle">
            <Input
              v-model="row.changeNum"
              type="number"
              class="width15"
              @on-change="changeTap(row)"
            >
              <Select
                v-model="row.pm"
                slot="append"
                style="width: 60px"
                @on-change="stockTap(row)"
              >
                <Option :value="1">入库</Option>
                <Option :value="0">出库</Option>
              </Select>
            </Input>
            <span class="ml20">={{ row.resultNum }}</span>
          </div>
        </template>
      </Table>
      <div class="footer acea-row row-right">
        <Button class="mr" @click="cancel">取消</Button>
        <Button type="primary" @click="productSaveStocks">提交</Button>
      </div>
    </Card>
  </Modal>
</template>

<script>
import { FormItem } from "element-ui";
import { productAttrsApi, productSaveStocksApi } from "@/api/product.js";
export default {
  name: "stockEdit",
  props: {
    goodsType: {
      type: Number,
      default: 0,
    },
  },
  data() {
    return {
      id: 0,
      specType: 0,
      batchShow: false,
      batchStock: 0,
      batchPm: 1,
      modals: false,
      loading: false,
      stockData: [],
      columns: [
        {
          title: "图片",
          slot: "image",
          minWidth: 20,
        },
        {
          title: "产品规格",
          key: "suk",
          minWidth: 90,
        },
        {
          title: "商品条形码",
          key: "bar_code",
          minWidth: 45,
        },
        {
          title: "商品编码",
          key: "code",
          minWidth: 35,
        },
        {
          title: "当前库存",
          key: "stock",
          minWidth: 10,
        },
        {
          title: "入/出库数量",
          slot: "num",
          minWidth: 200,
        },
      ],
      reservationColumns: [
        {
          title: "图片",
          slot: "image",
          minWidth: 90,
        },
        {
          title: "产品规格",
          key: "suk",
          minWidth: 90,
        },
        {
          title: "时间段",
          slot: "time",
          minWidth: 90,
        },
        {
          title: "当前库存",
          slot: "timeStock",
          minWidth: 90,
        },
        {
          title: "库存",
          slot: "timeNum",
          minWidth: 90,
        },
      ],
    };
  },
  methods: {
    // 批量设置；
    countBatch() {
      this.batchStock = Math.abs(this.batchStock);
      this.stockData.forEach((item) => {
        item.changeNum = this.batchStock;
        if (this.batchPm) {
          item.pm = 1;
          item.resultNum = parseInt(item.stock) + parseInt(item.changeNum);
        } else {
          item.pm = 0;
          if (parseInt(item.stock) <= 0) {
            item.resultNum = 0;
          } else {
            let num = parseInt(item.stock) - parseInt(item.changeNum);
            item.resultNum = num <= 0 ? 0 : num;
          }
        }
      });
    },
    // 批量加减库存
    inputTap() {
      this.batchStock = this.batchStock.replace(/^\d{10}$/g, "0");
      this.countBatch();
    },
    // 批量设置入库或是出库
    batchStockTap() {
      this.countBatch();
    },
    // 单个设置
    countStock(row) {
      if (row.pm) {
        row.resultNum = parseInt(row.stock) + parseInt(row.changeNum);
      } else {
        if (parseInt(row.stock) <= 0) {
          row.resultNum = 0;
        } else {
          let num = parseInt(row.stock) - parseInt(row.changeNum);
          row.resultNum = num <= 0 ? 0 : num;
        }
      }
      this.stockData.forEach((item) => {
        if (row.id == item.id) {
          item.changeNum = row.changeNum;
          item.resultNum = row.resultNum;
          item.pm = row.pm;
        }
      });
    },
    // 设置加减库存
    stockTap(row) {
      this.countStock(row);
    },
    // 设置入库或是出库
    changeTap(row) {
      row.changeNum = row.changeNum.replace(/^\d{10}$/g, "0");
      this.countStock(row);
    },
    batchTap() {
      this.batchShow = !this.batchShow;
    },
    productAttrs(data) {
      this.specType = data.spec_type;
      this.id = data.id;
      productAttrsApi(data.id)
        .then((res) => {
          let data = res.data;
          data.forEach((item) => {
            item.resultNum = item.stock;
            item.changeNum = 0;
            item.pm = 1;
            if (item.reservationTimeData) {
              item.reservationTimeData.forEach((j) => {
                j.num = j.stock;
              });
            }
          });
          this.stockData = data;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    productSaveStocks() {
      let attrs = [];
      this.stockData.forEach((item) => {
        let data = {
          unique: item.unique,
          pm: item.pm,
          stock: item.changeNum,
        };
        if (item.reservationTimeData) {
          data.reservation_time_data = [];
          item.reservationTimeData.forEach((j) => {
            let obj = {
              id: j.id,
              stock: j.num,
            };
            data.reservation_time_data.push(obj);
          });
        }
        attrs.push(data);
      });
      productSaveStocksApi(
        {
          attrs: attrs,
        },
        this.id
      )
        .then((res) => {
          this.$Message.success("修改成功");
          this.cancel();
          this.$emit("stockChange", res.data.stock);
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    cancel() {
      this.modals = false;
      this.batchShow = false;
      this.batchPm = 1;
      this.batchStock = 0;
    },
  },
};
</script>

<style lang="stylus" scoped>
/*定义滑块 内阴影+圆角*/
/deep/::-webkit-scrollbar-thumb {
	-webkit-box-shadow: inset 0 0 6px #999;
}

/deep/::-webkit-scrollbar {
	width: 4px !important;
	/*对垂直流动条有效*/
}

.time~.time{
	margin-top: 10px;
}

.footer {
	margin-top 20px;
}

.product-data .image {
	width: 40px !important;
	height: 40px !important;
  border-radius: 4px;
}

.cards {
	position relative;
}

.batch {
	position absolute;
	right -20px;
	z-index 9;
	top: 10px;
	width: 176px;

	.input {
		width: 176px;
		height: 56px;
		background: #FFFFFF;
		box-shadow: 0px 2px 6px 0px rgba(0, 0, 0, 0.05);
		border-radius: 4px;
		margin-left -80px;
	}

	.name {
		font-size 13px;
		color: #1890FF;
		margin-bottom 10px;
		cursor pointer;

		.iconfont {
			font-size 12px;
			margin-left 4px;
		}
	}
}

/deep/.ivu-select-single .ivu-select-selection .ivu-select-placeholder,
/deep/.ivu-select-single .ivu-select-selection .ivu-select-selected-value {
	font-size 13px !important;
}

/deep/.ivu-input:focus {
	border-color #dcdee2 !important;
	box-shadow unset !important;
}

/deep/.ivu-input:hover {
	border-color #dcdee2 !important;
}

/deep/.ivu-input {
	border-right 0 !important;
	transition: unset !important;
}

/deep/.ivu-input-group-append {
	background-color #fff !important;
}

/deep/.ivu-table {
	overflow unset !important;
}

/deep/.ivu-table-cell {
	overflow unset !important;
	font-size 13px !important;
}
</style>
