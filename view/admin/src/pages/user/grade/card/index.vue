<template>
<!-- 用户-付费会员-卡密会员 -->
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="new_card_pd">
        <Form
        :model="gradeFrom"
        inline
        :label-width="labelWidth"
        :label-position="labelPosition"
        @submit.native.prevent
      >
        <FormItem label="批次名称：" label-for="title">
          <Input
            v-model="gradeFrom.title"
            placeholder="请输入批次名称"
            class="input-add mr14"
          />
           <Button type="primary" @click="userSearchs()">查询</Button>
        </FormItem>
        
      </Form>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <Button type="primary" @click="addBatch" class="mr20">添加批次</Button>
      <Button @click="getMemberScan">下载二维码</Button>
      <Table
        class="mt25"
        :columns="columns"
        :data="tbody"
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
            <span slot="open">激活</span>
            <span slot="close">冻结</span>
          </i-switch>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <Dropdown transfer @on-click="changeMenu(row, $event, index)">
              <a href="javascript:void(0)">
                更多
                <Icon type="ios-arrow-down"></Icon>
              </a>
              <DropdownMenu slot="list">
                <DropdownItem name="1">编辑批次名</DropdownItem>
                <DropdownItem name="2">查看卡列表</DropdownItem>
                <DropdownItem name="3">导出</DropdownItem>
              </DropdownMenu>
            </Dropdown>
        </template>
      </Table>
      <div class="acea-row row-right page">
        <Page
          :total="total"
          :current="gradeFrom.page"
          :page-size="gradeFrom.limit"
          show-elevator
          show-total
          @on-change="pageChange"
        />
      </div>
    </Card>
    <Modal v-model="modal" title="添加批次" footer-hide class-name="vertical-center-modal">
      <form-create
        v-model="fapi"
        :rule="rule"
        @on-submit="onSubmit"
      ></form-create>
    </Modal>
    <Modal v-model="modal2" title="编辑批次名" footer-hide>
      <form-create :rule="rule2" @on-submit="onSubmit2"></form-create>
    </Modal>
    <Modal v-model="modal3" title="二维码" footer-hide>
      <div v-if="qrcode" class="acea-row row-around">
        <div
          v-if="qrcode && qrcode.wechat_img"
          class="acea-row row-column-around row-between-wrapper"
        >
          <div v-viewer class="QRpic">
            <img v-lazy="qrcode.wechat_img" />
          </div>
          <span class="mt10">公众号二维码</span>
        </div>
        <div
          v-if="qrcode && qrcode.routine"
          class="acea-row row-column-around row-between-wrapper"
        >
          <div v-viewer class="QRpic">
            <img v-lazy="qrcode.routine" />
          </div>
          <span class="mt10">小程序二维码</span>
        </div>
      </div>
      <Spin v-else></Spin>
    </Modal>
  </div>
</template>

<script>
import { mapState } from "vuex";
import {
  userMemberBatch,
  memberBatchSave,
  memberBatchSetValue,
  exportMemberCard,
  userMemberScan,
} from "@/api/user";
import exportExcel from "@/utils/newToExcel.js";

export default {
  name: "index",
  data() {
    return {
      grid: {
        xl: 7,
        lg: 7,
        md: 12,
        sm: 24,
        xs: 24,
      },
      columns: [
        {
          title: "编号",
          key: "id",
        },
        {
          title: "批次名称",
          key: "title",
        },
        {
          title: "体验天数",
          key: "use_day",
        },
        {
          title: "发卡总数量",
          key: "total_num",
        },
        {
          title: "使用数量",
          key: "use_num",
        },
        {
          title: "制卡时间",
          key: "add_time",
        },
        {
          title: "是否激活",
          slot: "status",
        },
        {
          title: "备注",
          key: "remark",
        },
        {
          title: "操作",
          slot: "action",
          fixed: "right",
        },
      ],
      tbody: [],
      total: 0,
      gradeFrom: {
        title: "",
        page: 1,
        limit: 15,
      },
      loading: false,
      modal: false,
      rule: [
        {
          type: "input",
          field: "title",
          title: "批次名称",
          validate: [
            {
              required: true,
              message: "请输入批次名称",
              trigger: "blur",
            },
          ],
        },
        {
          type: "InputNumber",
          field: "total_num",
          title: "制卡数量",
          value: 1,
          props: {
            min: 1,
          },
        },
        {
          type: "InputNumber",
          field: "use_day",
          title: "体验天数",
          value: 1,
          props: {
            min: 1,
          },
        },
        {
          type: "radio",
          field: "status",
          title: "是否激活",
          value: "0",
          options: [
            {
              value: "0",
              label: "冻结",
            },
            {
              value: "1",
              label: "激活",
            },
          ],
        },
        {
          type: "input",
          field: "remark",
          title: "备注",
          props: {
            type: "textarea",
          },
        },
      ],
      modal2: false,
      rule2: [
        {
          type: "hidden",
          field: "id",
          value: "",
        },
        {
          type: "input",
          field: "title",
          title: "批次名称",
          value: "",
          validate: [
            {
              required: true,
              message: "请输入批次名称",
              trigger: "blur",
            },
          ],
        },
      ],
      modal3: false,
      qrcode: null,
      fapi: {},
    };
  },
  computed: {
		...mapState("admin/layout", ["isMobile"]),
    labelWidth() {
      return this.isMobile ? undefined : 75;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
  },
  created() {
    this.getMemberBatch(this.gradeFrom);
  },
  methods: {
    // 批次列表
    getMemberBatch() {
      this.loading = true;
      userMemberBatch(this.gradeFrom)
        .then((res) => {
          this.loading = false;
          this.tbody = res.data.list;
          this.total = res.data.count;
        })
        .catch((err) => {
          this.loading = false;
          this.$Message.error(err.msg);
        });
    },
    // 批次名称查询
    userSearchs() {
      this.gradeFrom.page = 1;
      this.getMemberBatch();
    },
    // 激活 | 冻结
    onchangeIsShow(row) {
      memberBatchSetValue(row.id, {
        field: "status",
        value: row.status,
      })
        .then((res) => {
          this.$Message.success(res.msg);
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    // 查看
    // exportExcel(row) {
    //     this.$Spin.show();
    //     exportMemberCard(row.id)
    //         .then(res => {
    //             this.$Spin.hide();
    //             location.href = res.data[0];
    //         })
    //         .catch(err => {
    //             this.$Spin.hide();
    //             this.$Message.error(err.msg);
    //         });
    // },
    // 数据导出；
    async exports(row) {
      let [th, filekey, data] = [[], [], []];
      let fileName = "";
      let excelData = JSON.parse(JSON.stringify(this.gradeFrom));
      excelData.page = 1;
      for (let i = 0; i < excelData.page + 1; i++) {
        let lebData = await this.getExcelData(row.id, excelData);
        if (!fileName) fileName = lebData.filename;
        if (!filekey.length) {
          filekey = lebData.filekey;
        }
        if (!th.length) th = lebData.header;
        if (lebData.export.length) {
          data = data.concat(lebData.export);
          excelData.page++;
        } else {
          exportExcel(th, filekey, fileName, data);
          return;
        }
      }
    },
    getExcelData(id, excelData) {
      return new Promise((resolve, reject) => {
        exportMemberCard(id, excelData).then((res) => {
          return resolve(res.data);
        });
      });
    },
    // 更多
    changeMenu(row, name) {
      switch (name) {
        case "1":
          this.rule2[0].value = row.id;
          this.rule2[1].value = row.title;
          this.modal2 = true;
          break;
        case "2":
          this.$router.push({
            path: `/admin/vipuser/grade/list/${row.id}`,
          });
          break;
        case "3":
          this.exports(row);
          break;
      }
    },
    // 分页
    pageChange(index) {
      this.gradeFrom.page = index;
      this.getMemberBatch();
    },
    // 添加批次弹窗
    addBatch() {
      this.fapi.resetFields();
      this.modal = true;
    },
    // 提交批次
    onSubmit(formData) {
      memberBatchSave(0, formData)
        .then((res) => {
          this.modal = false;
          this.$Message.success(res.msg);
          this.getMemberBatch();
          this.fapi.resetFields();
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    onSubmit2(formData) {
      memberBatchSetValue(formData.id, {
        field: "title",
        value: formData.title,
      })
        .then((res) => {
          this.modal2 = false;
          this.$Message.success(res.msg);
          this.getMemberBatch();
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    // 会员卡二维码
    getMemberScan() {
      this.$Spin.show();
      userMemberScan()
        .then((res) => {
          this.$Spin.hide();
          this.qrcode = res.data;
          this.modal3 = true;
        })
        .catch((err) => {
          this.$Spin.hide();
          this.$Message.error(err.msg);
        });
    },
  },
};
</script>

<style lang="less" scoped>
.QRpic {
  width: 180px;
  height: 180px;

  img {
    width: 100%;
    height: 100%;
  }
}
</style>
