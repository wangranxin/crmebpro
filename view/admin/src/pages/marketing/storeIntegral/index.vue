<template>
<!-- 营销-积分商品 -->
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding= "0">
      <div class="new_card_pd">
        <!-- 查询条件 -->
      <Form
        ref="tableFrom"
        inline
        :model="tableFrom"
        :label-width="labelWidth"
        :label-position="labelPosition"
        @submit.native.prevent
      >
        <FormItem label="创建时间：" label-for="user_time">
          <!--<DatePicker clearable @on-change="onchangeTime" v-model="timeVal" :value="timeVal"  format="yyyy/MM/dd" type="daterange" placement="bottom-end" placeholder="选择时间" v-width="'100%'"></DatePicker>-->
          <DatePicker
            :editable="false"
            @on-change="onchangeTime"
            :value="timeVal"
            format="yyyy/MM/dd"
            type="datetimerange"
            placement="bottom-start"
            placeholder="自定义时间"
            class="mr20 input-add"
            :options="options"
          ></DatePicker>
        </FormItem>
        <FormItem label="上架状态：">
          <Select
            placeholder="请选择"
            clearable
            class="input-add"
            v-model="tableFrom.is_show"
          >
            <Option value="1">上架</Option>
            <Option value="0">下架</Option>
          </Select>
        </FormItem>
        <FormItem label="商品搜索：" label-for="store_name">
          <Input
            class="input-add mr14"
            placeholder="请输入活动标题，ID"
            v-model="tableFrom.store_name"
          />
          <Button type="primary" @click="tableSearchs()">查询</Button>
        </FormItem>
      </Form>
      </div>
    </Card>
      <Card :bordered="false" dis-hover class="ivu-mt">
        <!-- 操作 -->
      <Button
        v-auth="['marketing-store_seckill-create']"
        type="primary"
        @click="add"
        class="mr10"
        >添加积分商品</Button
      >
      <!-- 积分商品-表格 -->
      <Table
        :columns="columns1"
        :data="tableList"
        :loading="loading"
        highlight-row
        no-userFrom-text="暂无数据"
        no-filtered-userFrom-text="暂无筛选结果"
        class="ivu-mt"
      >
        <template slot-scope="{ row, index }" slot="image">
          <viewer>
            <div class="tabBox_img">
              <img v-lazy="row.image" />
            </div>
          </viewer>
        </template>
        <template  slot-scope="{ row, index }" slot="title">
          <Tooltip
              theme="dark"
              max-width="300"
              :delay="600"
              :content="row.title"
              :transfer="true"
            >
              <div class="line2">{{ row.title }}</div>
            </Tooltip>
        </template>
        <template slot-scope="{ row, index }" slot="stop_time">
          <span> {{ row.stop_time | formatDate }}</span>
        </template>
        <template slot-scope="{ row, index }" slot="is_show">
          <i-switch
            v-model="row.is_show"
            :value="row.is_show"
            :true-value="1"
            :false-value="0"
            @on-change="onchangeIsShow(row)"
            size="large"
          >
            <span slot="open">上架</span>
            <span slot="close">下架</span>
          </i-switch>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <!-- <a @click="orderList(row)">兑换记录</a> -->
          <a @click="edit(row)">编辑</a>
          <Divider type="vertical" />
          <a @click="copy(row)">复制</a>
          <Divider type="vertical" />
          <a @click="del(row, '删除积分商品', index)">删除</a>
        </template>
      </Table>
      <div class="acea-row row-right page" v-show="total > 0">
        <Page
          :total="total"
          :current="tableFrom.page"
          show-elevator
          show-total
          @on-change="pageChange"
          :page-size="tableFrom.limit"
        />
      </div>
    </Card>
  </div>
</template>

<script>
import { mapState } from "vuex";
import {
  integralProductListApi,
  integralIsShowApi,
  storeSeckillApi,
} from "@/api/marketing";
import { formatDate } from "@/utils/validate";
import timeOptions from "@/utils/timeOptions";
export default {
  name: "storeIntegral",
  filters: {
    formatDate(time) {
      if (time !== 0) {
        let date = new Date(time * 1000);
        return formatDate(date, "yyyy-MM-dd");
      }
    },
  },
  data() {
    return {
      loading: false,
      options: timeOptions,
      columns1: [
        {
          title: "ID",
          key: "id",
          width: 80,
        },
        {
          title: "商品图片",
          slot: "image",
          minWidth: 90,
        },
        {
          title: "活动标题",
          slot: "title",
          minWidth: 150,
        },
        {
          title: "兑换积分",
          key: "integral",
          minWidth: 100,
        },
        {
          title: "兑换金额",
          key: "price",
          minWidth: 100,
        },
        {
          title: "限量",
          key: "quota_show",
          minWidth: 80,
        },
        {
          title: "限量剩余",
          key: "quota",
          minWidth: 80,
        },
        {
          title: "创建时间",
          key: "add_time",
          minWidth: 130,
        },
        {
          title: "排序",
          key: "sort",
          minWidth: 50,
        },
        {
          title: "状态",
          slot: "is_show",
          minWidth: 100,
        },
        {
          title: "操作",
          slot: "action",
          fixed: "right",
          width: 200,
        },
      ],
      tableList: [],
      timeVal: [],
      grid: {
        xl: 7,
        lg: 10,
        md: 12,
        sm: 24,
        xs: 24,
      },
      tableFrom: {
        integral_time: "",
        is_show: "",
        store_name: "",
        page: 1,
        limit: 15,
      },
      total: 0,
    };
  },
  computed: {
    ...mapState("admin/layout", ["isMobile"]),
    labelWidth() {
      return this.isMobile ? undefined : 96;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
  },
  created() {
    this.getList();
  },
  methods: {
    // 添加
    add() {
      this.$router.push({ path: "/admin/marketing/store_integral/create" });
    },
    addMore() {
      this.$router.push({
        path: "/admin/marketing/store_integral/add_store_integral",
      });
    },
    // 导出
    exports() {
      let formValidate = this.tableFrom;
      let data = {
        start_status: formValidate.start_status,
        status: formValidate.status,
        store_name: formValidate.store_name,
      };
      storeSeckillApi(data)
        .then((res) => {
          location.href = res.data[0];
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 编辑
    edit(row) {
      this.$router.push({
        path: "/admin/marketing/store_integral/create/" + row.id + "/0",
      });
    },
    // 一键复制
    copy(row) {
      this.$router.push({
        path: "/admin/marketing/store_integral/create/" + row.id + "/1",
      });
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `marketing/integral/${row.id}`,
        method: "DELETE",
        ids: "",
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.tableList.splice(num, 1);
          if (!this.tableList.length) {
            this.tableFrom.page =
                this.tableFrom.page == 1 ? 1 : this.tableFrom.page - 1;
          }
          this.getList();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 列表
    getList() {
      this.loading = true;
      this.tableFrom.start_status = this.tableFrom.start_status || "";
      this.tableFrom.is_show = this.tableFrom.is_show || "";
      integralProductListApi(this.tableFrom)
        .then(async (res) => {
          let data = res.data;
          this.tableList = data.list;
          this.total = res.data.count;
          this.loading = false;
        })
        .catch((res) => {
          this.loading = false;
          this.$Message.error(res.msg);
        });
    },
    pageChange(index) {
      this.tableFrom.page = index;
      this.getList();
    },
    // 表格搜索
    tableSearchs() {
      this.tableFrom.page = 1;
      this.getList();
    },
    // 具体日期
    onchangeTime(e) {
      this.timeVal = e;
      this.tableFrom.integral_time = this.timeVal.join("-");
    },
    // 修改是否显示
    onchangeIsShow(row) {
      let data = {
        id: row.id,
        is_show: row.is_show,
      };
      integralIsShowApi(data)
        .then(async (res) => {
          this.$Message.success(res.msg);
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
  },
};
</script>

<style scoped lang="stylus">
.tabBox_img {
  width: 36px;
  height: 36px;
  border-radius: 4px;
  cursor: pointer;

  img {
    width: 100%;
    height: 100%;
  }
}
</style>
