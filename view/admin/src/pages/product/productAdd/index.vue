<template>
  <div>
    <div class="i-layout-page-header">
      <PageHeader class="product_tabs" hidden-breadcrumb>
        <div slot="title" class="acea-row row-middle">
          <router-link :to="{ path: '/admin/product/product_list' }">
            <div class="font-sm after-line">
              <span class="iconfont iconfanhui"></span>
              <span class="pl10">返回</span>
            </div>
          </router-link>
          <span
            v-text="$route.params.id ? '编辑商品' : '添加商品'"
            class="mr20 ml16"
          ></span>
        </div>
      </PageHeader>
    </div>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <div class="new_tab">
        <Tabs v-model="currentTab">
          <TabPane
            v-for="(item, index) in headTab"
            :key="index"
            :label="item.title"
            :name="item.name"
          ></TabPane>
        </Tabs>
      </div>
      <Form
        class="formData mt20"
        ref="formData"
        :model="formData"
        :label-width="170"
        label-position="right"
        @submit.native.prevent
      >
        <div v-show="currentTab === '1'">
          <!-- 商品基础信息的设置 -->
          <FormItem label="商品类型：">
            <Select
              v-model="formData.product_type"
              disabled
              v-width="'50%'"
            >
              <Option :value="0">普通商品</Option>
              <Option :value="1">卡密/网盘</Option>
              <Option :value="3">虚拟商品</Option>
              <Option :value="4">次卡商品</Option>
            </Select>
          </FormItem>
          <productBaseSet
            ref="productBaseSet"
            :successData="success"
            :baseInfo="formData"
          ></productBaseSet>
        </div>
        <div v-show="currentTab === '2'">
          <!-- 商品规格的设置 -->
          <FormItem label="商品规格：" v-if="formData.product_type !== 4">
            <div class="flex-y-center">
              <RadioGroup v-model="formData.spec_type">
                <Radio :disabled="disabledSpecType" :label="0" class="radio"
                  >单规格</Radio
                >
                <Radio :disabled="disabledSpecType" :label="1">多规格</Radio>
              </RadioGroup>
              <Dropdown v-if="formData.spec_type == 1" @on-click="confirm">
                <span class="pl-14 text-blue pointer">
                  选择规格模板
                  <Icon type="ios-arrow-down"></Icon>
                </span>
                <template #list>
                  <DropdownMenu>
                    <DropdownItem
                      v-for="(item, index) in ruleList"
                      :key="index"
                      :name="item.rule_name"
                      >{{ item.rule_name }}</DropdownItem
                    >
                  </DropdownMenu>
                </template>
              </Dropdown>
            </div>
            <div class="tips" v-show="disabledSpecType">
              商品有活动开启，无法切换商品规格
            </div>
          </FormItem>

          <!-- 多规格设置 -->
          <div v-if="formData.spec_type == 1">
            <FormItem label="商品规格：">
              <div class="specifications" v-show="attrs.length">
                <draggable
                  group="specifications"
                  :list="attrs"
                  handle=".move-icon"
                  @end="onMoveSpec"
                  animation="300"
                >
                  <div
                    class="specifications-item pointer active"
                    v-for="(item, index) in attrs"
                    :key="index"
                    @click="changeCurrentIndex(index)"
                  >
                    <div class="move-icon">
                      <span class="iconfont icondrag2"></span>
                    </div>
                    <i
                      class="del ivu-icon ivu-icon-md-close-circle"
                      @click="handleRemoveRole(index)"
                    />
                    <div class="specifications-item-box">
                      <div class="lineBox"></div>
                      <div class="specifications-item-name mb18">
                        <Input
                          v-model="item.value"
                          placeholder="规格名称"
                          maxlength="30" 
                          show-word-limit
                          @on-change="attrChangeValue(index, item.value)"
                          @on-focus="handleFocus(item.value)"
                          class="specifications-item-name-input"
                        ></Input>
                        <Checkbox
                          class="ml20"
                          v-model="item.add_pic"
                          :disabled="!item.add_pic && !canSel"
                          :true-value="1"
                          :false-value="0"
                          @on-change="(e) => addPic(e, index)"
                          >添加规格图</Checkbox
                        >
                        <el-tooltip
                          class="item"
                          effect="dark"
                          content="添加规格图片, 仅支持打开一个(建议尺寸:800*800)"
                          placement="right"
                        >
                          <Icon type="md-information-circle" />
                        </el-tooltip>
                      </div>
                      <div class="rulesBox ml30">
                        <draggable
                          class="item"
                          :list="item.detail"
                          handle=".drag"
                          @end="onMoveSpec"
                        >
                          <div
                            v-for="(j, indexn) in item.detail"
                            :key="indexn"
                            class="mr10 spec drag relative"
                          >
                            <i
                              class="del2 ivu-icon ivu-icon-md-close-circle"
                              @click="
                                handleRemove2(item.detail, indexn, j.value)
                              "
                            />
                            <Input
                              v-model="j.value"
                              placeholder="规格值"
                              maxlength="30" 
                              show-word-limit
                              @on-change="attrDetailChangeValue(j.value, index)"
                              @on-focus="handleFocus(j.value)"
                              @on-blur="handleBlur()"
                            >
                              <template slot="prefix">
                                <span class="iconfont icondrag2"></span>
                              </template>
                            </Input>
                            <div class="img-popover" v-if="item.add_pic">
                              <div class="popper-arrow"></div>
                              <div
                                class="popper"
                                @click="handleSelImg(j, indexn)"
                              >
                                <img class="img" v-if="j.pic" :src="j.pic" />
                                <i v-else class="el-icon-plus"></i>
                              </div>
                              <i
                                v-if="j.pic"
                                class="img-del el-icon-error"
                                @click="handleRemoveImg(j)"
                              ></i>
                            </div>
                          </div>
                          <el-popover
                            :ref="'popoverRef_' + index"
                            placement=""
                            width="210"
                            trigger="click"
                            @after-enter="handleShowPop(index)"
                            :style="{'min-height': item.add_pic == 1 ? '121px' : ''}"
                          >
                            <Input
                              :ref="'inputRef_' + index"
                              placeholder="请输入规格值"
                              maxlength="30" 
                              show-word-limit
                              v-model="formDynamic.attrsVal"
                              @keyup.enter.native="createAttr(formDynamic.attrsVal, index)"
                              @on-blur="createAttr(formDynamic.attrsVal, index)"
                            >
                            </Input>
                          <a class="addfont" slot="reference">添加规格值</a>
                          </el-popover>
                        </draggable>
                      </div>
                    </div>
                  </div>
                </draggable>
              </div>
              <Button v-if="attrs.length < 4" @click="handleAddRole()">添加新规格</Button>
              <Button v-if="attrs.length" type="text" @click="handleSaveAsTemplate()">另存为模板</Button>
            </FormItem>
            <FormItem
              label="商品属性："
              prop=""
              v-show="manyFormValidate.length"
            >
              <el-table
                size="small"
                :data="manyFormValidate"
                style="width: 100%"
                :cell-class-name="tableCellClassName"
                :span-method="objectSpanMethod"
                :header-row-class-name="headerRowClassName"
                border
              >
                <el-table-column
                  v-for="(item, index) in formData.header"
                  :key="index"
                  :label="item.title"
                  :min-width="item.minWidth || '100'"
                  :fixed="item.fixed"
                >
                  <template slot-scope="scope">
                    <!-- 批量设置 -->
                    <template v-if="scope.$index == 0">
                      <template v-if="item.key">
                        <div
                          v-if="
                            attrs.length &&
                              attrs[scope.column.index] &&
                              manyFormValidate.length
                          "
                        >
                          <el-select
                            size="small"
                            v-model="oneFormBatch[0][item.title]"
                            :placeholder="`请选择${item.title}`"
                            clearable
                          >
                            <el-option
                              v-for="val in attrs[scope.column.index].detail"
                              :key="val.value"
                              :label="val.value"
                              :value="val.value"
                            >
                            </el-option>
                          </el-select>
                        </div>
                      </template>
                      <template v-else-if="item.slot === 'pic'">
                        <div
                          class="pictrueBox small flex-center"
                          @click="setAllPic()"
                        >
                          <div class="pictrue" v-if="oneFormBatch[0].pic">
                            <img v-lazy="oneFormBatch[0].pic" />
                          </div>
                          <div
                            class="upLoad acea-row row-center-wrapper"
                            v-else
                          >
                            <Icon type="ios-camera-outline" size="26" />
                          </div>
                        </div>
                      </template>
                      <template v-else-if="item.slot === 'price'">
                        <InputNumber
                          :controls="false"
                          v-model="oneFormBatch[0].price"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                          clearable
                        ></InputNumber>
                      </template>
                      <template v-if="item.slot === 'settle_price'">
                        <InputNumber
                          :controls="false"
                          disabled
                          v-model="oneFormBatch[0].settle_price"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'cost'">
                        <InputNumber
                          :controls="false"
                          v-model="oneFormBatch[0].cost"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                          clearable
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'ot_price'">
                        <InputNumber
                          :controls="false"
                          v-model="oneFormBatch[0].ot_price"
                          :min="0"
                          class="priceBox"
                          clearable
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'stock'">
                        <InputNumber
                          :controls="false"
                          v-model="oneFormBatch[0].stock"
                          :disabled="formData.virtual_type == 1"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                          clearable
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'fictitious'">
                        --
                      </template>
                      <template v-else-if="item.slot === 'code'">
                        <Input v-model="oneFormBatch[0].code"></Input>
                      </template>
                      <template v-else-if="item.slot === 'bar_code'">
                        <Input
                          v-model="oneFormBatch[0].bar_code"
                        ></Input>
                      </template>
                      <template v-else-if="item.slot === 'weight'">
                        <InputNumber
                          :controls="false"
                          v-model="oneFormBatch[0].weight"
                          :step="0.1"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                          clearable
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'volume'">
                        <InputNumber
                          :controls="false"
                          v-model="oneFormBatch[0].volume"
                          :step="0.1"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                          clearable
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'selected_spec'">
                        --
                      </template>
                      <template v-else-if="item.slot === 'action'">
                        <a @click="batchAdd">批量修改</a>
                        <Divider type="vertical" />
                        <a @click="batchDel">清空</a>
                      </template>
                    </template>
                    <template v-else>
                      <template v-if="item.key">
                        <div>
                          <span>{{ scope.row.detail[item.key] }}</span>
                        </div>
                      </template>
                      <template v-if="item.slot === 'pic'">
                        <div
                          class="pictrueBox small flex-center"
                          @click="setAttrPic(scope.$index)"
                        >
                          <div
                            class="pictrue"
                            v-if="manyFormValidate[scope.$index].pic"
                          >
                            <img v-lazy="manyFormValidate[scope.$index].pic" />
                          </div>
                          <div
                            class="upLoad acea-row row-center-wrapper"
                            v-else
                          >
                            <Icon type="ios-camera-outline" size="26" />
                          </div>
                        </div>
                      </template>
                      <template v-if="item.slot === 'price'">
                        <InputNumber
                          :controls="false"
                          v-model="manyFormValidate[scope.$index].price"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                        ></InputNumber>
                      </template>
                      <template v-if="item.slot === 'settle_price'">
                        <InputNumber
                          :controls="false"
                          disabled
                          v-model="manyFormValidate[scope.$index].settle_price"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'cost'">
                        <InputNumber
                          :controls="false"
                          v-model="manyFormValidate[scope.$index].cost"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'ot_price'">
                        <InputNumber
                          :controls="false"
                          v-model="manyFormValidate[scope.$index].ot_price"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'stock'">
                        <InputNumber
                          :controls="false"
                          v-model="manyFormValidate[scope.$index].stock"
                          :disabled="formData.product_type == 1"
                          :min="0"
                          :max="9999999999"
                          :precision="0"
                          class="priceBox"
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'code'">
                        <Input
                          v-model="manyFormValidate[scope.$index].code"
                        ></Input>
                      </template>
                      <template v-else-if="item.slot === 'bar_code'">
                        <Input
                          v-model="manyFormValidate[scope.$index].bar_code"
                        ></Input>
                      </template>
                      <template v-else-if="item.slot === 'weight'">
                        <InputNumber
                          :controls="false"
                          v-model="manyFormValidate[scope.$index].weight"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'volume'">
                        <InputNumber
                          :controls="false"
                          v-model="manyFormValidate[scope.$index].volume"
                          :min="0"
                          :max="9999999999"
                          class="priceBox"
                        ></InputNumber>
                      </template>
                      <template v-else-if="item.slot === 'fictitious'">
                        <Button
                          v-if="
                            (!scope.row.virtual_list ||
                              !scope.row.virtual_list.length) &&
                              !scope.row.stock
                          "
                          @click="addVirtual(scope.$index, 'manyFormValidate')"
                          >添加卡密</Button
                        >
                        <span
                          v-else
                          class="seeCatMy"
                          @click="
                            seeVirtual(
                              manyFormValidate[scope.$index],
                              'manyFormValidate',
                              scope.$index
                            )
                          "
                          >已设置</span
                        >
                      </template>

                      <template v-else-if="item.slot === 'selected_spec'">
                        <Switch
                          v-model="
                            manyFormValidate[scope.$index].is_default_select
                          "
                          :true-value="1"
                          :false-value="0"
                          @on-change="changeDefaultSelect(scope.$index)"
                        />
                      </template>
                      <template v-else-if="item.slot === 'action'">
                        <Switch
                          size="large"
                          v-model="manyFormValidate[scope.$index].is_show"
                          :true-value="1"
                          :false-value="0"
                          @on-change="changeDefaultShow(scope.$index)"
                        >
                          <template #open>
                            <span>显示</span>
                          </template>
                          <template #close>
                            <span>隐藏</span>
                          </template>
                        </Switch>
                      </template>
                    </template>
                  </template>
                </el-table-column>
              </el-table>
            </FormItem>
          </div>
          <!-- 单规格设置 -->
          <div v-if="formData.spec_type == 0 && [0, 1, 3].includes(formData.product_type)">
            <FormItem label="图片：" required>
              <div class="pictrueBox" @click="attrPicTap()">
                <div class="pictrue" v-if="formData.attr.pic">
                  <img :src="formData.attr.pic" />
                </div>
                <div class="upLoad acea-row row-center-wrapper" v-else>
                  <Icon type="ios-camera-outline" size="26" />
                </div>
              </div>
            </FormItem>
            <FormItem label="售价：" required>
              <InputNumber
                v-model="formData.attr.price"
                :min="0"
                :max="99999999"
                class="w-260"
              ></InputNumber>
            </FormItem>
            <FormItem label="结算价：" required v-if="merchantType == 2">
              <InputNumber
                :disabled="true"
                v-model="formData.attr.settle_price"
                :min="0"
                :max="99999999"
                class="w-260"
              ></InputNumber>
            </FormItem>
            <FormItem label="成本价：">
              <InputNumber
                v-model="formData.attr.cost"
                :min="0"
                :max="99999999"
                class="w-260"
              ></InputNumber>
            </FormItem>
            <FormItem label="划线价：">
              <InputNumber
                v-model="formData.attr.ot_price"
                :min="0"
                :max="99999999"
                class="w-260"
              ></InputNumber>
            </FormItem>
            <FormItem label="库存：" required>
              <InputNumber
                v-model="formData.attr.stock"
                :min="0"
                :max="99999999"
                :disabled="formData.product_type == 1 || openErp"
                :precision="0"
                class="w-260"
              ></InputNumber>
            </FormItem>
            <FormItem label="商品编号：">
              <Input
                v-model.trim="formData.attr.code"
                class="w-260"
                placeholder="请输入商品编码"
              ></Input>
            </FormItem>
            <FormItem label="商品条形码：">
              <Input
                v-model.trim="formData.attr.bar_code"
                class="w-260"
                placeholder="请输入商品条形码"
              ></Input>
            </FormItem>
            <FormItem label="重量（KG）：" v-if="formData.product_type == 0">
              <InputNumber
                v-model="formData.attr.weight"
                :min="0"
                :max="99999999"
                class="w-260"
              ></InputNumber>
            </FormItem>
            <FormItem label="体积(m³)：" v-if="formData.product_type == 0">
              <InputNumber
                v-model="formData.attr.volume"
                :min="0"
                :max="99999999"
                class="w-260"
              ></InputNumber>
            </FormItem>
            <template v-if="formData.product_type == 1">
              <FormItem label="卡密设置：">
                <Button v-if="!formData.attr.virtual_list.length && !formData.attr.stock"
                  @click="addVirtual(0, 'attr')">添加卡密</Button>
                <span
                  v-else
                  class="seeCatMy"
                  @click="seeVirtual(formData.attr, 'attr')"
                  >已设置</span
                >
              </FormItem>
            </template>
          </div>
          <div v-if="formData.spec_type === 0 && formData.product_type == 4">
            <FormItem label="图片：" required>
              <div class="pictrueBox" @click="attrPicTap()">
                <div class="pictrue" v-if="formData.attr.pic">
                  <img :src="formData.attr.pic" />
                </div>
                <div class="upLoad acea-row row-center-wrapper" v-else>
                  <Icon type="ios-camera-outline" size="26" />
                </div>
              </div>
            </FormItem>
            <FormItem label="核销次数：">
              <InputNumber
                v-model="formData.attr.write_times"
                :min="1"
                :max="99999999"
                :precision="0"
                placeholder="请输入核销次数"
                class="w-260"
              ></InputNumber>
            </FormItem>
            <FormItem required label="核销时效：">
              <RadioGroup v-model="formData.attr.write_valid">
                <Radio :label="1">永久有效</Radio>
                <Radio :label="2">购买后几天有效</Radio>
                <Radio :label="3">固定有效期</Radio>
              </RadioGroup>
            </FormItem>
            <FormItem
              label=""
              prop="freight"
              v-if="formData.attr.write_valid == 2"
            >
              <div class="acea-row row-middle">
                <InputNumber
                  :min="1"
                  v-model="formData.attr.days"
                  placeholder="请输入有效天数"
                  class="w-260"
                />
                <span class="ml10">天</span>
              </div>
            </FormItem>
            <FormItem
              label=""
              prop="freight"
              v-if="formData.attr.write_valid == 3"
            >
              <div class="acea-row row-middle">
                <DatePicker
                  :editable="false"
                  clearable
                  type="datetimerange"
                  format="yyyy-MM-dd HH:mm:ss"
                  placement="bottom-start"
                  placeholder="请选择固定有效期"
                  @on-change="onchangeTime"
                  v-model="section_time"
                  class="w-260"
                ></DatePicker>
              </div>
            </FormItem>
            <FormItem label="售价：" required>
              <InputNumber
                v-model="formData.attr.price"
                :min="0"
                :max="99999999"
                class="w-260"
              ></InputNumber>
            </FormItem>
            <FormItem label="成本价：">
              <InputNumber
                v-model="formData.attr.cost"
                :min="0"
                :max="99999999"
                class="w-260"
              ></InputNumber>
            </FormItem>
            <FormItem label="划线价：">
              <InputNumber
                v-model="formData.attr.ot_price"
                :min="0"
                :max="99999999"
                class="w-260"
              ></InputNumber>
            </FormItem>
            <FormItem label="库存：" required>
              <InputNumber
                v-model="formData.attr.stock"
                :min="0"
                :max="99999999"
                :disabled="formData.product_type == 1 || openErp"
                :precision="0"
               class="w-260"
              ></InputNumber>
            </FormItem>
          </div>
        </div>
        <div v-show="currentTab === '3'">
          <!-- 商品详情的设置 -->
          <Row class="mb10">
            <Col span="16">
              <wangeditor
                style="width: 100%"
                :content="formData.description"
                @editorContent="getEditorContent"
              ></wangeditor>
            </Col>
            <Col span="6" style="width: 33%">
              <div class="ifam">
                <div class="content" v-html="formData.description"></div>
              </div>
            </Col>
          </Row>
        </div>
        <div v-show="currentTab === '4'">
          <!-- 物流设置 -->
          <FormItem label="配送方式：" prop="" required>
            <CheckboxGroup v-model="formData.delivery_type">
              <Checkbox label="1">快递</Checkbox>
              <Checkbox label="2">自提</Checkbox>
            </CheckboxGroup>
          </FormItem>
          <FormItem label="运费设置：">
            <RadioGroup v-model="formData.freight">
              <Radio :label="1">包邮</Radio>
              <Radio :label="2">固定邮费</Radio>
              <Radio :label="3">运费模板</Radio>
            </RadioGroup>
          </FormItem>
          <FormItem label="" prop="freight" v-if="formData.freight == 2">
            <div class="acea-row row-middle">
              <InputNumber
                :min="0"
                v-model="formData.postage"
                placeholder="请输入金额"
                class="perW20 maxW"
              />
              <span class="ml10">元</span>
            </div>
          </FormItem>
          <FormItem label="" v-if="formData.freight == 3">
            <div class="acea-row">
              <Select v-model="formData.temp_id" clearable class="perW20 maxW">
                <Option
                  v-for="(item, index) in templateList"
                  :value="item.id"
                  :key="index"
                  >{{ item.name }}</Option
                >
              </Select>
              <Button @click="editTemp" class="ml15" v-if="formData.temp_id"
                >查看运费模板</Button
              >
              <Button @click="addTemp" class="ml15" v-else>添加运费模板</Button>
            </div>
          </FormItem>
        </div>
        <div v-show="currentTab === '5'">
          <marketingSet
            ref="marketingSet"
            :successData="success"
            :baseInfo="formData"
            :product_type="formData.product_type"
          ></marketingSet>
        </div>
        <div v-show="currentTab === '6'">
          <otherSet
            ref="otherSet"
            :successData="success"
            :baseInfo="formData"
            :product_type="formData.product_type"
          ></otherSet>
        </div>
      </Form>
    </Card>
    <Card
      :bordered="false"
      dis-hover
      class="fixed-card"
      :style="{ left: `${!menuCollapse ? '200px' : isMobile ? '0' : '80px'}` }"
    >
      <div class="flex-center">
        <Button v-if="currentTab !== '1'" @click="upTab">上一步</Button>
        <Button
          type="primary"
          class="submission"
          v-if="currentTab !== '6'"
          @click="downTab('formData')"
          >下一步</Button
        >
        <Button
          type="primary"
          :disabled="openSubimit"
          class="submission"
          @click="handleSubmit()"
          v-if="currentTab != '1'"
          >保存</Button
        >
      </div>
    </Card>
    <add-attr ref="addattr" @getList="productGetRule"></add-attr>
    <Modal
      v-model="carMyShow"
      scrollable
      title="添加卡密"
      closable
      width="700"
      :footer-hide="true"
      :mask-closable="false"
    >
      <add-carMy
        ref="addCarMy"
        :virtualList="virtualList"
        @changeVirtual="changeVirtual"
        @fixdBtn="fixdBtn"
        @closeCarMy="closeCarMy"
      ></add-carMy>
    </Modal>
    <freightTemplate
      :template="template"
      :merchantType="merchantType"
      v-on:changeTemplate="changeTemplate"
      ref="templates"
    ></freightTemplate>
    <!-- 生成淘宝京东表单-->
    <Modal
      v-model="modals"
      @on-cancel="cancel"
      class="Box"
      class-name="vertical-center-modal"
      scrollable
      footer-hide
      closable
      title="复制淘宝、天猫、京东、苏宁、1688"
      :mask-closable="false"
      width="800"
      height="500"
    >
      <tao-bao ref="taobaos" v-if="modals" @on-close="onClose"></tao-bao>
    </Modal>
  </div>
</template>
<script>
import { mapState, mapMutations } from "vuex";
import {
  productInfoApi,
  checkActivityApi,
  productGetRuleApi,
  productAddApi,
  productGetTemplateApi,
  ruleAddApi
} from "@/api/product";
import { arraysEqual, Debounce } from "@/utils";
import { erpConfig } from "@/api/erp";
import {
  defaultObj,
  GoodsTableHead,
  VirtualTableHead,
  VirtualTableHead2,
} from "./formModel.js";
import freightTemplate from "@/components/freightTemplate";
import productBaseSet from "./components/productBaseSet.vue";
import marketingSet from "./components/marketingSet.vue";
import otherSet from "./components/otherSet.vue";
import addAttr from "../productAttr/addAttr";
import wangeditor from "@/components/wangEditor/index.vue";
import addCarMy from "../components/addCarMy";
import taoBao from "./taoBao";
import vuedraggable from "vuedraggable";
export default {
  name: "productAdd",
  data() {
    return {
      headTab: [
        { title: "基础信息", name: "1" },
        { title: "规格库存", name: "2" },
        { title: "商品详情", name: "3" },
        { title: "物流设置", name: "4" },
        { title: "营销设置", name: "5" },
        { title: "其他设置", name: "6" },
      ],
      currentTab: "1",
      spinShow: false,
      openSubimit: false,
      ruleList: [],
      attrs: [],
      formData: Object.assign({}, defaultObj),
      oneFormBatch: [
        {
          bar_code: "",
          code: "",
          cost: null,
          detail: {},
          // settle_price: null,
          ot_price: null,
          pic: "",
          price: null,
          stock: null,
          weight: null,
          volume: null,
          virtual_list: [],
        },
      ],
      openErp: false,
      currentIndex: 0,
      merchantType: 0, //0:平台商品；1:门店商品；2:供应商商品
      columnsInstalM: [],
      manyFormValidate: [],
      oldVal: [],
      disabledSpecType: false,
      createBnt: true,
      // 规格数据
      formDynamic: {
        attrsName: "",
        attrsVal: "",
      },
      carMyShow: false, //是否开启卡密弹窗
      virtualList: [],
      tabIndex: 0,
      tabName: "",
      success: false,
      changeAttrValue: "",
      canSel: true, // 规格图片添加判断
      templateList: [],
      template: false,
      templateName: "",
      modals: false,
      type: 0,
      section_time: [],
      product_type: 0,
    };
  },
  components: {
    freightTemplate,
    productBaseSet,
    marketingSet,
    otherSet,
    addAttr,
    wangeditor,
    addCarMy,
    taoBao,
    draggable: vuedraggable,
  },
  computed: {
    ...mapState("admin/layout", ["isMobile", "menuCollapse"]),
  },
  destroyed() {
    this.setCopyrightShow({ value: true });
  },
  mounted() {
    this.productGetRule();
    this.productGetTemplate();
    this.getErpConfig();
    if (this.$route.params.id || this.$route.query.copy) {
      this.changeSpec();
      this.getInfo();
    }
    if (this.$route.query.productType) {
      setTimeout(() => {
        this.formData.product_type = Number(this.$route.query.productType);
      }, 100);
      if (this.$route.query.productType != 0) {
        this.headTab.splice(3, 1);
      }
    }
    if (this.$route.query.type && this.$route.query.type == -1) {
      this.modals = true;
      this.type = -1;
    }
  },
  methods: {
    ...mapMutations("admin/layout", ["setCopyrightShow"]),
    // 改变规格
    changeSpec() {
      // this.formData.is_sub = [];
      let id = this.$route.params.id;
      if (id) {
        checkActivityApi(id).catch((res) => {
          this.disabledSpecType = true;
        });
      }
    },
    // 规格图片添加开关
    addPic(e, i) {
      if (e) {
        this.attrs.map((item, ii) => {
          if (ii !== i) {
            this.$set(item, "add_pic", 0);
          }
        });
        this.canSel = false;
      } else {
        this.canSel = true;
      }
    },
    // 规格拖拽排序后
    onMoveSpec() {
      this.generateAttr(this.attrs);
    },
    changeCurrentIndex(i) {
      this.currentIndex = i;
    },
    generateAttr(data) {
      this.generateHeader(data);
      const combinations = this.generateCombinations(data);
      let rows = combinations.map((combination) => {
        const row = {
          attr_arr: combination,
          detail: {},
          title: "",
          key: "",
          price: 0,
          pic: "",
          ot_price: 0,
          cost: 0,
          stock: 0,
          is_show: 1,
          is_default_select: 0,
          unique: "",
          weight: 0,
          volume: 0,
          brokerage: 0,
          brokerage_two: 0,
          vip_price: 0,
          vip_proportion: 0,
        };
        // 判断商品类型是卡密
        if (this.formData.product_type == 1) {
          this.$set(row, "virtual_list", []);
          this.$set(row, "disk_info", "");
        }
        for (let i = 0; i < combination.length; i++) {
          const value = combination[i];
          this.$set(row, data[i].value, value);
          this.$set(row, "title", data[i].value);
          this.$set(row, "key", data[i].value);
          this.$set(row.detail, data[i].value, value);
          // 如果manyFormValidate中存在该属性值，则赋值
          for (let k = 0; k < this.manyFormValidate.length; k++) {
            const manyItem = this.manyFormValidate[k];
            // 对比两个数组是否完全相等
            let attrDetail = Object.values(manyItem.detail);
            if (k > 0 && attrDetail && arraysEqual(attrDetail, combination)) {
              Object.assign(row, {
                price: manyItem.price,
                cost: manyItem.cost,
                ot_price: manyItem.ot_price,
                stock: manyItem.stock,
                pic: manyItem.pic,
                unique: manyItem.unique || "",
                weight: manyItem.weight || 0,
                is_show: manyItem.is_show || 1,
                is_default_select: manyItem.is_default_select || 0, 
                volume: manyItem.volume || 0,
                code: manyItem.code || 0,
                bar_code: manyItem.bar_code || 0,
                is_virtual: manyItem.is_virtual,
                brokerage: manyItem.brokerage,
                brokerage_two: manyItem.brokerage_two,
                vip_price: manyItem.vip_price,
                vip_proportion: manyItem.vip_proportion,
              });
              if (this.formData.product_type == 1) {
                row.virtual_list = manyItem.virtual_list;
                row.disk_info = manyItem.disk_info;
              }
            }
          }
        }
        return row;
      });
      this.$nextTick(() => {
        // rows数组第一项 新增默认数据 oneFormBatch
        this.manyFormValidate = [...this.oneFormBatch, ...rows];
      });
    },
    handleRemoveRole(index) {
      this.attrs.splice(index, 1);
      this.attrs.map((item, ii) => {
        this.$set(item, "add_pic", 0);
      });
      this.canSel = true;
      this.manyFormValidate.splice(index, 1);
      if (!this.attrs.length) {
        this.formData.header = [];
        this.manyFormValidate = [];
      } else {
        this.generateAttr(this.attrs);
      }
    },
    // 删除表格中 对应属性
    delAttrTable(val) {
      for (let i = 0; i < this.manyFormValidate.length; i++) {
        let item = this.manyFormValidate[i];
        // console.log(item.detail);
        // console.log(Object.values(item.detail));
        // console.log(val);
        if ( Object.values(item.detail) && Object.values(item.detail).includes(val) ) {
          console.log(i);
          this.manyFormValidate.splice(i, 1);
          i--;
        }
      }
    },
    handleRemove2(item, index, val) {
      item.splice(index, 1);
      this.delAttrTable(val);
    },
    // 规格名称改变
    attrChangeValue(i, val) {
      if (val.trim().length && this.attrs[i].detail.length) {
        this.generateHeader(this.attrs);
        if (this.manyFormValidate.length) {
          this.manyFormValidate.map((item, i) => {
            if (i > 0) {
              if (Object.keys(item.detail).includes(this.changeAttrValue)) {
                item.detail[val] = item.detail[this.changeAttrValue];
                item[val] = item[this.changeAttrValue];
                delete item.detail[this.changeAttrValue];
                delete item[this.changeAttrValue];
              }
            }
          });
          this.changeAttrValue = val;
        }
      } else {
        this.generateAttr(this.attrs);
      }
    },
    attrDetailChangeValue: Debounce(function(val, i) {
      if (this.manyFormValidate.length) {
        let key = this.attrs[i].value;
        this.manyFormValidate.map((item, i) => {
          if (i > 0) {
            if (Object.keys(item.detail).includes(key) && item.detail[key] === this.changeAttrValue) {
              item.detail[key] = val;
            }
          }
        });
        this.changeAttrValue = val;
      } else {
        this.generateAttr(this.attrs, 1);
      }
    }),
    handleShowPop(index) {
      this.$refs["inputRef_" + index][0].focus();
    },
    // 新增规格
    handleAddRole() {
      let data = {
        value: this.formDynamic.attrsName,
        add_pic: 0,
        detail: [],
      };
      this.attrs.push(data);
    },
    // 新增一条属性
    addOneAttr() {
      this.generateAttr(this.attrs);
    },
    changeSpecImg(arr, img) {
      this.$Modal.confirm({
        title: "提示",
        content: "可以同步修改下方该规格图片，确定要替换吗？",
        onOk: () => {
          for (let val of this.manyFormValidate) {
            if (this.isSubset(Object.values(val.detail), arr)) {
              this.$set(val, "pic", img);
            }
          }
        },
      });
    },
    handleFocus(val) {
      this.changeAttrValue = val;
    },
    handleBlur() {
      this.changeAttrValue = "";
    },
    handleSelImg(item, i) {
      this.$imgModal((e) => {
        let that = this;
        let imgUrl = e[0].att_dir;
        if (imgUrl.includes("mp4")) {
          this.$Message.error("请选择正确的图片文件");
        } else {
          that.$set(item, "pic", imgUrl);
          this.changeSpecImg([item.value], imgUrl);
        }
      });
    },
    handleRemoveImg(item) {
      item.pic = "";
    },
    // 生成规格组合
    generateCombinations(arr, prefix = []) {
      if (arr.length === 0) {
        return [prefix];
      }
      const [first, ...rest] = arr;
      return first.detail.flatMap((detail) =>
        this.generateCombinations(rest, [...prefix, detail.value])
      );
    },
    createAttr(num, idx) {
      if (num) {
        var isExist = this.attrs[idx].detail.some((item) => item.value === num);
        if (isExist) {
          this.$Message.error("规格值已存在");
          return;
        }
        this.attrs[idx].detail.push({ value: num, pic: '' });
        if (this.manyFormValidate.length) {
          this.addOneAttr(this.attrs[idx].value, num);
        } else {
          this.generateAttr(this.attrs);
        }
        this.$refs["popoverRef_" + idx][0].doClose(); //关闭的
        //清除刚才输入的内容
        this.formDynamic.attrsName = '';
        this.formDynamic.attrsVal = '';
        setTimeout(() => {
          if (this.$refs["popoverRef_" + idx]) {
            //重点是以下两句
            this.$refs["popoverRef_" + idx][0].doShow(); //打开的
            //重点是以上两句
          }
        }, 20);
      } else {
        // this.$Message.warning("请添加属性");
        this.$refs['popoverRef_' + idx][0].doClose(); //关闭的
      }
    },
    // 获取商品属性模板；
    productGetRule() {
      productGetRuleApi().then((res) => {
        this.ruleList = res.data;
      });
    },
    // 切换默认选中规格
    changeDefaultSelect(index) {
      // 一个开启 其他关闭
      this.manyFormValidate.map((item, i) => {
        if (i !== index) {
          item.is_default_select = 0;
        }
      });
    },
    // 改变是否显示
    changeDefaultShow(index) {
      // 如果默认选中开启 则不可隐藏
      if (this.manyFormValidate[index].is_default_select === 1) {
        this.manyFormValidate[index].is_show = 1;
        this.$message.error("默认规格不可隐藏");
      }
    },
    // 生成列表 行 列 数据
    tableCellClassName({ row, column, rowIndex, columnIndex }) {
      //注意这里是解构
      //利用单元格的 className 的回调方法，给行列索引赋值
      row.index = rowIndex || "";
      column.index = columnIndex;
    },
    // 合并单元格
    objectSpanMethod({ row, column, rowIndex, columnIndex }) {
      if (columnIndex === 0 && rowIndex > 0) {
        let lable = column.label;
        //这里判断第几列需要合并
        const tagFamily = this.manyFormValidate[rowIndex].detail[lable];
        const index = this.manyFormValidate.findIndex((item, index) => {
          if (index > 0) return item.detail[lable] == tagFamily;
        });
        if (rowIndex == index) {
          let len = 1;
          for (let i = index + 1; i < this.manyFormValidate.length; i++) {
            if (this.manyFormValidate[i].detail[lable] !== tagFamily) {
              break;
            }
            len++;
          }
          return {
            rowspan: len,
            colspan: 1,
          };
        } else {
          return {
            rowspan: 0,
            colspan: 0,
          };
        }
      }
    },
    headerRowClassName() {
      return "custom-header-class"; // 返回自定义的 CSS 类名
    },
    // 清空批量规格信息
    batchDel() {
      this.oneFormBatch = [
        {
          bar_code: "",
          code: "",
          cost: null,
          detail: {},
          // settle_price: null,
          ot_price: null,
          pic: "",
          price: null,
          stock: null,
          weight: null,
          volume: null,
          virtual_list: [],
        },
      ];
    },
    isSubset(arr1, arr2) {
      // 将数组转换为 Set，以便进行高效的包含检查
      const set1 = new Set(arr1);
      const set2 = new Set(arr2);

      // 检查 set2 中的每个元素是否都在 set1 中
      for (let elem of set2) {
        if (!set1.has(elem)) {
          return false;
        }
      }
      return true;
    },
    // 批量添加
    batchAdd() {
      let arr = [];
      for (let val of this.attrs) {
        if (this.oneFormBatch[0][val.value]) {
          arr.push(this.oneFormBatch[0][val.value]);
        }
      }
      for (let val of this.manyFormValidate) {
        if (arr.length) {
          if (this.isSubset(val.attr_arr, arr)) {
            if (this.oneFormBatch[0].pic) {
              this.$set(val, "pic", this.oneFormBatch[0].pic);
            }
            this.$set(val, "price", this.oneFormBatch[0].price);
            if (this.oneFormBatch[0].price !== null) {
            }
            if (this.oneFormBatch[0].cost !== null) {
              this.$set(val, "cost", this.oneFormBatch[0].cost);
            }
            if (this.oneFormBatch[0].ot_price !== null) {
              this.$set(val, "ot_price", this.oneFormBatch[0].ot_price);
            }
            if (this.oneFormBatch[0].stock !== null) {
              this.$set(val, "stock", this.oneFormBatch[0].stock);
            }
            if (this.oneFormBatch[0].code !== "") {
              this.$set(val, "code", this.oneFormBatch[0].code);
            }
            if (this.oneFormBatch[0].bar_code !== "") {
              this.$set(val, "bar_code",this.oneFormBatch[0].bar_code);
            }
            if (this.oneFormBatch[0].weight !== null) {
              this.$set(val, "weight", this.oneFormBatch[0].weight);
            }
            if (this.oneFormBatch[0].volume !== null) {
              this.$set(val, "volume", this.oneFormBatch[0].volume);
            }
          }
        } else {
          if (this.oneFormBatch[0].pic) {
            this.$set(val, "pic", this.oneFormBatch[0].pic);
          }
          if (this.oneFormBatch[0].price !== null) {
            this.$set(val, "price", this.oneFormBatch[0].price);
          }
          if (this.oneFormBatch[0].cost !== null) {
            this.$set(val, "cost", this.oneFormBatch[0].cost);
          }
          if (this.oneFormBatch[0].ot_price !== null) {
            this.$set(val, "ot_price", this.oneFormBatch[0].ot_price);
          }
          if (this.oneFormBatch[0].stock !== null) {
            this.$set(val, "stock", this.oneFormBatch[0].stock);
          }

          if (this.oneFormBatch[0].weight !== null) {
            this.$set(val, "weight", this.oneFormBatch[0].weight);
          }
          if (this.oneFormBatch[0].volume !== null) {
            this.$set(val, "volume", this.oneFormBatch[0].volume);
          }
          if (this.oneFormBatch[0].code) {
            this.$set(val, "code", this.oneFormBatch[0].code);
          }
          if (this.oneFormBatch[0].bar_code) {
            this.$set(val, "bar_code",this.oneFormBatch[0].bar_code);
          }
        }
      }
    },
    confirm(name) {
      this.createBnt = true;
      this.formData.selectRule = name;
      if (this.formData.selectRule.trim().length <= 0) {
        return this.$Message.error("请选择属性");
      }
      this.ruleList.forEach((item, index) => {
        if (item.rule_name === this.formData.selectRule) {
          this.attrs = item.rule_value;
        }
      });
      this.canSel = true;
      this.generateAttr(this.attrs);
    },
    // 添加规则；
    addRule() {
      this.$refs.addattr.modal = true;
    },
    getEditorContent(data) {
      this.formData.description = data;
    },
    //添加卡密
    addVirtual(index, name) {
      this.tabIndex = index;
      this.tabName = name;
      this.virtualListClear();
      this.$refs.addCarMy.fixedCar = {
        disk_info: "",
        stock: 0,
      };
      this.$refs.addCarMy.cartMyType = 1;
      this.carMyShow = true;
    },
    seeVirtual(data, name, index) {
      this.tabName = name;
      this.tabIndex = index;
      this.virtualListClear();
      this.$refs.addCarMy.fixedCar = {
        disk_info: "",
        stock: 0,
      };
      if (data.virtual_list && data.virtual_list.length) {
        this.$refs.addCarMy.cartMyType = 2;
        this.virtualList = data.virtual_list;
      } else if (data.disk_info) {
        this.$refs.addCarMy.cartMyType = 1;
        this.$refs.addCarMy.fixedCar.disk_info = data.disk_info;
        this.$refs.addCarMy.fixedCar.stock = data.stock;
      }
      this.carMyShow = true;
    },
    closeCarMy() {
      this.carMyShow = false;
    },
    //确认提交卡密
    fixdBtn(e) {
      if (e.cartMyType == 1) {
        // 单规格
        if(this.tabName == 'attr'){
          this.formData.attr.disk_info = e.disk_info;
          this.formData.attr.stock = e.stock;
          this.formData.attr.virtual_list = [];
        }else{
          //多规格
          this.$set(this[this.tabName][this.tabIndex], "disk_info", e.disk_info);
          this.$set(this[this.tabName][this.tabIndex], "stock", Number(e.stock));
          this[this.tabName][this.tabIndex].virtual_list = [];
        }
      } else {
        // 单规格
        if(this.tabName == 'attr'){
          this.formData.attr.virtual_list = e.virtualList;
          this.formData.attr.stock =  e.virtualList.length;
          this.formData.attr.disk_info = "";
        }else{
          this.$set( this[this.tabName][this.tabIndex], "virtual_list",e.virtualList);
          this.$set( this[this.tabName][this.tabIndex],"stock", e.virtualList.length);
          this[this.tabName][this.tabIndex].disk_info = "";
        }
      }
      this.carMyShow = false;
    },
    //添加倒入卡密的值
    changeVirtual(e) {
      this.virtualList = e;
    },
    //清空卡密
    virtualListClear() {
      this.virtualList = [
        {
          key: "",
          value: "",
        },
      ];
    },
    getInfo() {
      let that = this;
      that.spinShow = true;
      productInfoApi(that.$route.params.id || this.$route.query.copy)
        .then(async (res) => {
          let data = res.data.productInfo;
          this.infoData(data);
          // 生成规格
          this.spinShow = false;
          this.success = true;
        })
        .catch((res) => {
          this.spinShow = false;
          this.$Message.error(res.msg);
        });
    },
    infoData(data){
      this.merchantType = parseInt(data.type);
      let keys = Object.keys(this.formData);
      keys.map((i) => {
        this.formData[i] = data[i];
      });
      //不是普通商品不显示物流选项卡
      if(data.product_type != 0){
        this.headTab.splice(3, 1);
      }
      //次卡商品将核销日期时间段回显
      if(data.product_type == 4){
        this.section_time = data.attr.section_time;
      }
      
      //截取轮播图
      // this.formData.slider_image = this.formData.slider_image.splice(0, 10);
      //多规格 SKU 赋值
      this.attrs = data.items || [];
      this.attrs.map((item) => {
        if(item.add_pic) this.canSel = false;
      });
      this.formData.off_show = data.auto_off_time ? 1 : 0;
      this.formData.coupon_ids = this.formData.coupons.map(
        (item) => item.id
      ); //提取优惠券id
      this.formData.couponName = data.coupons;
      this.formData.brand_id = this.formData.brand_id.map(String); //提取品牌 id
      this.formData.is_limit = this.formData.is_limit ? 1 : 0;
      this.formData.limit_type = parseInt(data.limit_type);
      // 生成规格表头
      this.generateHeader(this.attrs);
      if (data.spec_type == 1) {
        data.attrs.map((item) => {
          this.$set(item, "price", Number(item.price));
          if(this.merchantType == 2){
            this.$set(item, "cost", Number(item.settle_price));
          }else{
            this.$set(item, "cost", Number(item.cost));
          }
          this.$set(item, "ot_price",  Number(item.ot_price));
          this.$set(item, "weight", Number(item.weight));
          this.$set(item, "volume", Number(item.volume));
          this.$set(item, "stock", Number(item.stock));
        });
      }
      this.manyFormValidate = [...this.oneFormBatch, ...data.attrs];
    },
    generateHeader(data) {
      let specificationsColumns = data.map((item) => ({
        title: item.value,
        key: item.value,
        minWidth: 140,
        fixed: "left",
      }));
      if (this.formData.product_type == 0) {
        let headerList = [...specificationsColumns, ...GoodsTableHead];
        // 找到售价的索引
        const priceIndex = headerList.findIndex(
          (item) => item.title === "售价"
        );
        // 在售价后面插入结算价对象
        if (priceIndex !== -1 && this.merchantType == 2) {
          headerList.splice(priceIndex + 1, 0, {
            title: "结算价",
            slot: "settle_price",
            align: "center",
            minWidth: "120px",
          });
        }
        this.formData.header = headerList;
      } else if (this.formData.product_type == 3) {
        this.formData.header = [...specificationsColumns, ...VirtualTableHead];
      } else if (this.formData.product_type == 1) {
        this.formData.header = [...specificationsColumns, ...VirtualTableHead2];
      }
      this.columnsInstalM = this.formData.header;
    },
    // 上一页；
    upTab() {
      if (this.currentTab == 5 && this.formData.product_type != 0) {
        this.currentTab = (Number(this.currentTab) - 2).toString();
      } else {
        this.currentTab = (Number(this.currentTab) - 1).toString();
      }
    },
    // 下一页；
    downTab(name) {
      this.$refs[name].validate((valid) => {
        if (valid) {
          if (this.formData.is_show == 2 && !this.formData.auto_on_time) {
            return this.$Message.warning("请填写定时上架时间");
          }
          if (this.off_show == 1 && !this.formData.auto_off_time) {
            return this.$Message.warning("请填写定时下架时间");
          }
          if (this.currentTab == 4 && !this.formData.delivery_type.length) {
            return this.$Message.warning("请选择配送方式");
          }
          if (this.currentTab == 3 && this.formData.product_type != 0) {
            this.currentTab = (Number(this.currentTab) + 2).toString();
          } else {
            this.currentTab = (Number(this.currentTab) + 1).toString();
          }
        } else {
          this.$Message.warning("请完善数据");
        }
      });
    },
    attrPicTap() {
      this.$imgModal((e) => {
        let imgUrl = e[0].att_dir;
        if (imgUrl.includes("mp4")) {
          this.$Message.error("请选择正确的图片文件");
        } else {
          this.formData.attr.pic = imgUrl;
        }
      });
    },
    setAllPic() {
      this.$imgModal((e) => {
        let imgUrl = e[0].att_dir;
        if (imgUrl.includes("mp4")) {
          this.$Message.error("请选择正确的图片文件");
        } else {
          this.oneFormBatch[0].pic = imgUrl;
        }
      });
    },
    setAttrPic(index) {
      this.$imgModal((e) => {
        let imgUrl = e[0].att_dir;
        if (imgUrl.includes("mp4")) {
          this.$Message.error("请选择正确的图片文件");
        } else {
          this.manyFormValidate[index].pic = imgUrl;
        }
      });
    },
    //erp配置
    getErpConfig() {
      erpConfig()
        .then((res) => {
          this.openErp = res.data.open_erp;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    // 获取运费模板；
    productGetTemplate() {
      productGetTemplateApi({ id: this.formData.id }).then((res) => {
        this.templateList = res.data;
      });
    },
    // 添加运费模板
    addTemp() {
      this.$refs.templates.isTemplate = true;
    },
    //查看、编辑运费模板
    editTemp() {
      this.$refs.templates.isTemplate = true;
      this.$refs.templates.editFrom(this.formData.temp_id);
    },
    changeTemplate(msg) {
      this.template = msg;
    },
    onchangeTime(e){
      this.formData.attr.section_time = e;
    },
    handleSubmit() {
      let formData = this.summarizeData();
      if(formData.store_name == '') {
        this.currentTab = '1';
        this.$Message.error("请添加商品名称");
        return
      }
      if(!formData.cate_id.length) {
        this.currentTab = '1';
        this.$Message.error("请选择商品分类");
        return
      }
      if(formData.unit_name == '') {
        this.currentTab = '1';
        this.$Message.error("请添加商品单位");
        return
      }
      if(formData.is_limit && formData.min_qty > formData.limit_num){
        this.currentTab = '5';
        this.$Message.error("起购数量不能大于限购数量");
        return
      }
      let isPic = true;
      if(formData.spec_type == 1){
        this.attrs.forEach(item=>{
          if(item.add_pic == 1){
            item.detail.forEach(itemn=>{
              if(!itemn.pic){
                isPic = false;
              }
            })
          }
        })
      }
      if(!isPic) {
        this.currentTab = '2';
        this.$Message.error("请完整添加规格图片");
        return
      }
      productAddApi(formData)
        .then(async (res) => {
          this.openSubimit = true;
          this.$Message.success(res.msg);
          if (this.$route.params.id === "0") {
            cacheDelete().catch((err) => {
              this.$Message.error(err.msg);
            });
          }
          setTimeout(() => {
            this.formData.attr.pic = "";
            this.$router.push({ path: "/admin/product/product_list" });
          }, 500);
        })
        .catch((res) => {
          this.openSubimit = false;
          this.$Message.error(res.msg);
        });
    },
    summarizeData() {
      let baseSetData = this.$refs.productBaseSet.formValidate;
      let marketingSetData = this.$refs.marketingSet.formValidate;
      let otherSetData = this.$refs.otherSet.formValidate;
      let formData = { ...baseSetData, ...marketingSetData, ...otherSetData };
      if (this.$route.query.copy) {
        this.$set(formData, "id", 0);
      }
      this.$set(formData, "type", this.type);
      this.$set(formData, "product_type", this.formData.product_type);
      this.$set(formData, "spec_type", this.formData.spec_type);
      this.$set(formData, "items", this.attrs);
      this.$set(formData, "attr", this.formData.attr);
      this.$set(formData, "attrs", this.manyFormValidate.slice(1));
      this.$set(formData, "description", this.formData.description);
      this.$set(formData, "delivery_type", this.formData.delivery_type);
      this.$set(formData, "freight", this.formData.freight);
      this.$set(formData, "postage", this.formData.postage);
      this.$set(formData, "temp_id", this.formData.temp_id);
      this.$set(formData,"label_id", marketingSetData.label_id.map((item) => item.id));
      this.$set(formData, "store_label_id", baseSetData.store_label_id.map((item) => item.id));
      this.$set(formData,"recommend_list", marketingSetData.recommend_list.map((item) => item.product_id));
      return formData;
    },
    handleSaveAsTemplate(){
      let that = this;
      that.$Modal.confirm({
            title: '另存为模板',
            render(h) {
              return h('div', [
                h('Input', {
                  props: {
                    placeholder: '请输入模板名称',
                    value: ''
                  },
                  style:{
                    marginTop: '20px'
                  },
                  on: {
                    input: (val) => {
                      that.templateName = val
                    }
                  }
                })
              ]);
            },
            onOk: () => {
              let spec = this.attrs.map((item) => {
                return {
                  value: item.value,
                  detail: item.detail.map((e) => e.value)
                }
              })
              let formDynamic = {
                rule_name: that.templateName,
                spec: spec,
              };
              ruleAddApi(formDynamic, 0).then((res) => {
                that.$Message.success(res.msg);
                that.productGetRule();
              }).catch((res) => {
                this.$message.error(res.msg);
              });
            }
        });
    },
    cancel() {
      this.$router.push({ path: "/admin/product/product_list" });
    },
    //关闭淘宝弹窗并生成数据；
    onClose(data) {
      console.log(data);
      this.modals = false;
      this.infoData(data);
      this.success = true;
    },
  },
};
</script>
<style scoped lang="less">
.new_tab {
  /deep/.ivu-tabs-nav .ivu-tabs-tab {
    padding: 4px 16px 20px !important;
    font-weight: 500;
  }
}
.fixed-card {
  position: fixed;
  right: 0;
  bottom: 0;
  left: 200px;
  z-index: 20;
  box-shadow: 0 -1px 2px rgb(240, 240, 240);

  /deep/ .ivu-card-body {
    padding: 15px 16px 14px;
  }

  .ivu-form-item {
    margin-bottom: 0;
  }

  /deep/ .ivu-form-item-content {
    margin-right: 124px;
    text-align: center;
  }

  .ivu-btn {
    height: 36px;
    padding: 0 20px;
  }
}
.text-blue {
  color: #2d8cf0;
}
.submission {
  margin-left: 10px;
}
.ifam {
  width: 344px;
  height: 644px;
  background: url("../../../assets/images/phonebg.png") no-repeat center top;
  background-size: 344px 644px;
  padding: 40px 20px;
  padding-top: 50px;
  margin: 0 auto 0 20px;

  .content {
    height: 560px;
    overflow: hidden;
    scrollbar-width: none; /* firefox */
    -ms-overflow-style: none; /* IE 10+ */
    overflow-x: hidden;
    overflow-y: auto;
  }

  .content::-webkit-scrollbar {
    display: none; /* Chrome Safari */
  }
}
.move-icon {
  width: 30px;
  cursor: move;
  margin-right: 10px;
}

.move-icon .icondrag2 {
  font-size: 26px;
  color: #bbb;
}
.drag {
  cursor: move;
  margin: 5px 0;
}
.spec {
  display: block;
  margin: 5px 0;
  position: relative;
  .img-popover {
    cursor: pointer;
    width: 76px;
    height: 76px;
    padding: 6px;
    margin-top: 12px;
    background-color: #fff;
    position: relative;
    border: 1px solid #dcdfe6;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    &:hover .img-del {
      display: block;
    }
    .img-del {
      display: none;
      position: absolute;
      right: 3px;
      top: 3px;
      font-size: 16px;
      color: #2d8cf0;
      cursor: pointer;
      z-index: 9;
    }
    .popper {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 4px;
    }
    .popper-arrow,
    .popper-arrow:after {
      position: absolute;
      display: block;
      width: 0;
      height: 0;
      border-color: transparent;
      border-style: solid;
    }
    .popper-arrow {
      top: -13px;
      border-top-width: 0;
      border-bottom-color: #dcdfe6;
      border-width: 6px;
      filter: drop-shadow(0 2px 12px rgba(0, 0, 0, 0.03));
      &::after {
        top: -5px;
        margin-left: -6px;
        border-top-width: 0;
        border-bottom-color: #fff;
        content: " ";
        border-width: 6px;
      }
    }
  }
  .del {
    position: absolute;
    display: none;
    right: -3px;
    top: -3px;
    z-index: 9;
  }
}
.spec:hover {
  .del {
    display: block;
    z-index: 999;
    cursor: pointer;
  }
}
/deep/.ivu-input-prefix,
.ivu-input-suffix {
  transform: translateY(7px);
}
.del2 {
  position: absolute;
  right: -4px;
  top: -4px;
  font-size: 14px;
  display: none;
}
.spec:hover {
  .del2 {
    display: block;
    z-index: 999;
    cursor: pointer;
  }
}
.rulesBox {
  display: flex;
  flex-wrap: wrap;
  align-items: center;

  .item {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
  }
  .addfont {
    margin-top: 5px;
  }
  /deep/ .el-popover {
    border: none;
    box-shadow: none;
    padding: 0;
    line-height: 1.5;
  }
}
// 多规格设置
.specifications {
  .specifications-item:hover {
    background-color: #e5eeff;
  }
  .specifications-item:hover .del {
    display: block;
  }
  .specifications-item {
    position: relative;
    display: flex;
    align-items: center;
    padding: 20px 15px;
    transition: all 0.1s;
    background-color: #fafafa;
    margin-bottom: 10px;
    border-radius: 4px;

    .del {
      display: none;
      position: absolute;
      right: 15px;
      top: 15px;
      font-size: 22px;
      color: #2d8cf0;
      cursor: pointer;
    }
    .specifications-item-box {
      position: relative;
      .lineBox {
        position: absolute;
        left: 13px;
        top: 24px;
        width: 30px;
        height: 45px;
        border-radius: 6px;
        border-left: 1px solid #dcdfe6;
        border-bottom: 1px solid #dcdfe6;
      }
      .specifications-item-name {
        .ivu-icon {
          color: #2d8cf0;
          font-size: 16px;
        }
      }
      .mb18 {
        margin-bottom: 18px !important;
      }
      .specifications-item-name-input {
        width: 200px;
      }
    }
  }
}
.priceBox {
  width: 100%;
}
.custom-header-class tr {
  background: #f3f8fe !important;
}
.tips {
  display: inline-bolck;
  font-size: 12px;
  color: #999999;
}
.seeCatMy {
  color: #2d8cf0;
  cursor: pointer;
}
.w-260{
  width: 260px;
}
</style>
