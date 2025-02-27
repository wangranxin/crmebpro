<?php
/**
 *  +----------------------------------------------------------------------
 *  | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 *  +----------------------------------------------------------------------
 *  | Author: CRMEB Team <admin@crmeb.com>
 *  +----------------------------------------------------------------------
 */

namespace crmeb\services\wechat\message;



use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Support\XML;

/**
 * 消息
 * Class Message
 * @author 等风来
 * @email 136327134@qq.com
 * @date 2023/9/14
 * @package crmeb\services\wechat\message
 */
class Message implements MessageInterface
{
    use HasAttributes;

    public const TEXT = 2;
    public const IMAGE = 4;
    public const VOICE = 8;
    public const VIDEO = 16;
    public const SHORT_VIDEO = 32;
    public const LOCATION = 64;
    public const LINK = 128;
    public const DEVICE_EVENT = 256;
    public const DEVICE_TEXT = 512;
    public const FILE = 1024;
    public const TEXT_CARD = 2048;
    public const TRANSFER = 4096;
    public const EVENT = 1048576;
    public const MINIPROGRAM_PAGE = 2097152;
    public const MINIPROGRAM_NOTICE = 4194304;
    public const ALL = self::TEXT | self::IMAGE | self::VOICE | self::VIDEO | self::SHORT_VIDEO | self::LOCATION | self::LINK
    | self::DEVICE_EVENT | self::DEVICE_TEXT | self::FILE | self::TEXT_CARD | self::TRANSFER | self::EVENT
    | self::MINIPROGRAM_PAGE | self::MINIPROGRAM_NOTICE;

    /**
     * @var string
     */
    protected string $type;

    /**
     * @var int
     */
    protected int $id;

    /**
     * @var string
     */
    protected string $to;

    /**
     * @var string
     */
    protected string $from;

    /**
     * @var array
     */
    protected array $properties = [];

    /**
     * @var array
     */
    protected array $jsonAliases = [];

    /**
     * Message constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes($attributes);
    }

    /**
     * Return type name message.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * Magic getter.
     *
     * @param string $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }

        return $this->getAttribute($property);
    }

    /**
     * Magic setter.
     *
     * @param string $property
     * @param mixed $value
     *
     * @return Message
     */
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            $this->setAttribute($property, $value);
        }

        return $this;
    }

    /**
     * @param array $appends
     *
     * @return array
     */
    public function transformForJsonRequestWithoutType(array $appends = [])
    {
        return $this->transformForJsonRequest($appends, false);
    }

    /**
     * @param array $appends
     * @param bool $withType
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function transformForJsonRequest(array $appends = [], bool $withType = true): array
    {
        if (!$withType) {
            return $this->propertiesToArray([], $this->jsonAliases);
        }
        $messageType = $this->getType();
        $data = array_merge(['msgtype' => $messageType], $appends);

        $data[$messageType] = array_merge($data[$messageType] ?? [], $this->propertiesToArray([], $this->jsonAliases));

        return $data;
    }

    /**
     * @param array $appends
     * @param bool $returnAsArray
     *
     * @return string
     */
    public function transformToXml(array $appends = [], bool $returnAsArray = false)
    {
        $data = array_merge(['MsgType' => $this->getType()], $this->toXmlArray(), $appends);

        return $returnAsArray ? $data : XML::build($data);
    }

    /**
     * @param array $data
     * @param array $aliases
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    protected function propertiesToArray(array $data, array $aliases = []): array
    {
        $this->checkRequiredAttributes();

        foreach ($this->attributes as $property => $value) {
            if (is_null($value) && !$this->isRequired($property)) {
                continue;
            }
            $alias = array_search($property, $aliases, true);

            $data[$alias ?: $property] = $this->get($property);
        }

        return $data;
    }

    public function toXmlArray()
    {
        throw new \RuntimeException(sprintf('Class "%s" cannot support transform to XML message.', __CLASS__));
    }
}
