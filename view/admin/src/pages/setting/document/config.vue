<template>
  <!-- 商品-商品参数 -->
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="new_card_pd">
        <!-- 筛选条件 -->
        <Form
          ref="specsFrom"
          inline
          :model="specsFrom"
          :label-width="labelWidth"
          :label-position="labelPosition"
          @submit.native.prevent
        >
          <Row :gutter="24" type="flex" justify="end">
            <Col span="24">
              <FormItem label="搜索：">
                <Input
                  v-model="specsFrom.name"
                  placeholder="请输入单据名称/ID"
                  class="input-add"
                ></Input>
                <Button type="primary" @click="specsSearchs">查询</Button>
              </FormItem>
            </Col>
          </Row>
        </Form>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <!-- 相关操作 -->
      <Row type="flex">
        <Col v-bind="grid">
          <Button type="primary" @click="add">添加打印机</Button>
        </Col>
      </Row>
      <!-- 商品参数表格 -->
      <Table
        :columns="columns"
        :data="list"
        ref="table"
        class="mt25"
        :loading="loading"
        highlight-row
        no-userFrom-text="暂无数据"
        no-filtered-userFrom-text="暂无筛选结果"
      >
        <template slot-scope="{ row }" slot="status">
          <i-switch
            v-model="row.status"
            :value="row.status"
            :true-value="1"
            :false-value="0"
            @on-change="onchangeIsShow(row)"
            size="large"
          >
            <span slot="open">开启</span>
            <span slot="close">关闭</span>
          </i-switch>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <a @click="edit(row.id)">编辑</a>
          <Divider type="vertical" />
          <a @click="del(row, '删除打印机', index)">删除</a>
        </template>
      </Table>
      <div class="acea-row row-right page">
        <Page
          :total="total"
          show-elevator
          show-total
          @on-change="pageChange"
          :page-size="specsFrom.limit"
        />
      </div>
    </Card>
  </div>
</template>

<script>
import { mapState } from "vuex";
import { productSpecsList } from "@/api/product";

export default {
  name: "specs",
  data() {
    return {
      grid: {
        xl: 7,
        lg: 7,
        md: 12,
        sm: 24,
        xs: 24,
      },
      loading: false,
      columns: [
        {
          title: "ID",
          key: "id",
          width: 80,
        },
        {
          title: "打印机名称",
          key: "name",
          minWidth: 100,
        },
        {
          title: "平台",
          key: "sort",
          width: 100,
        },
        {
          title: "应用账号",
          key: "sort",
          width: 100,
        },
        {
          title: "打印联数",
          key: "sort",
          width: 100,
        },
        {
          title: "创建时间",
          key: "add_time",
          minWidth: 100,
        },
        {
          title: "打印开关",
          key: "status",
          minWidth: 100,
        },
        {
          title: "操作",
          slot: "action",
          fixed: "right",
          minWidth: 120,
        },
      ],
      specsFrom: {
        page: 1,
        limit: 15,
        name: "",
      },
      list: [],
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
    specsSearchs() {
      this.specsFrom.page = 1;
      this.list = [];
      this.getList();
    },

    // 单位列表
    getList() {
      this.loading = true;
      productSpecsList(this.specsFrom)
        .then((res) => {
          let data = res.data;
          this.list = data.list;
          this.total = data.count;
          this.loading = false;
        })
        .catch((err) => {
          this.loading = false;
          this.$Message.error(err.msg);
        });
    },
    pageChange(index) {
      this.specsFrom.page = index;
      this.getList();
    },
    // 修改是否显示
    onchangeIsShow(row) {
      let data = {
        id: row.uid,
        status: row.status,
      };
      isShowApi(data)
        .then(async (res) => {
          this.$Message.success(res.msg);
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 添加
    add() {
      this.$modalForm(userLabelAddApi(0)).then(() => {});
    },
    //修改
    edit(id) {
      this.$modalForm(userLabelAddApi(id)).then(() => {
        this.getList();
      });
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `product/specs/${row.id}`,
        method: "DELETE",
        ids: "",
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.list.splice(num, 1);
          if (!this.list.length) {
            this.specsFrom.page =
              this.specsFrom.page == 1 ? 1 : this.specsFrom.page - 1;
          }
          this.getList();
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
  },
};
</script>

<style scoped lang="stylus">
.input-add {
  width: 250px;
  margin-right: 14px;
}
</style>
