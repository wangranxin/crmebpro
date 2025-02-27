<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace crmeb\form\components;


use crmeb\form\BaseComponent;
use crmeb\form\BuildInterface;

/**
 * 多选组件
 * Class CheckBox
 * @package crmeb\form\components
 */
class CheckBox extends BaseComponent implements BuildInterface
{
    /**
     * 组件名
     */
    const NAME = 'checkbox';

    /**
     * 组件规则
     * @var array
     */
    protected $rule = [
        'title' => '',
        'field' => '',
        'value' => [],
        'info' => '',
        'options' => [],
    ];

    /**
     * Radio constructor.
     * @param string $title
     * @param string $field
     * @param null $value
     */
    public function __construct(string $field, string $title, array $value = null)
    {
        $this->rule['title'] = $title;
        $this->rule['field'] = $field;
        $this->rule['value'] = !is_null($value) ? $value : null;
    }


    /**
     * options数据 ['label'=>'确定','value'=>1]
     * @param array $options
     * @return $this
     */
    public function options(array $options = [])
    {
        $this->rule['options'] = $options;
        return $this;
    }


    /**
     * 设置提示语
     * @param string $info
     * @return $this
     */
    public function info(string $info)
    {
        $this->rule['info'] = $info;
        return $this;
    }


    /**
     * 数据转换
     * @return array
     */
    public function toArray(): array
    {
        $this->rule['name'] = self::NAME;
        $this->before();
        return $this->rule;
    }
}
