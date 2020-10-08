<?php

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class SmsOrder extends AbstractExtensibleModel implements SmsOrderInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Dotdigitalgroup\Sms\Model\ResourceModel\SmsOrder::class);
        parent::_construct();
    }

    /**
     * @return int|mixed|null
     */
    public function getWebsiteId()
    {
        return $this->getData(self::WEBSITE_ID);
    }

    /**
     * @return int|mixed|null
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @return int|mixed|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @return int/mixed|null
     */
    public function getTypeId()
    {
        return $this->getData(self::TYPE_ID);
    }

    /**
     * @return int|mixed|null
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @return mixed|string|null
     */
    public function getPhoneNumber()
    {
        return $this->getData(self::PHONE_NUMBER);
    }

    /**
     * @return string|void
     */
    public function getMessage()
    {
        $this->getData(self::MESSAGE);
    }

    /**
     * @return string|void
     */
    public function getMessageId()
    {
        $this->getData(self::MESSAGE_ID);
    }

    /**
     * @param $websiteId
     * @return SmsOrder
     */
    public function setWebsiteId($websiteId)
    {
        $this->setData(self::WEBSITE_ID, $websiteId);
        return $this;
    }

    /**
     * @param $storeId
     * @return SmsOrder
     */
    public function setStoreId($storeId)
    {
        $this->setData(self::STORE_ID, $storeId);
        return $this;
    }

    /**
     * @param $status
     * @return SmsOrder
     */
    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);
        return $this;
    }

    /**
     * @param $typeId
     * @return SmsOrder
     */
    public function setTypeId($typeId)
    {
        $this->setData(self::TYPE_ID, $typeId);
        return $this;
    }

    /**
     * @param $orderId
     * @return SmsOrder
     */
    public function setOrderId($orderId)
    {
        $this->setData(self::ORDER_ID, $orderId);
        return $this;
    }

    /**
     * @param $phoneNumber
     * @return SmsOrder
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->setData(self::PHONE_NUMBER, $phoneNumber);
        return $this;
    }

    /**
     * @param $message
     * @return SmsOrder
     */
    public function setMessage($message)
    {
        $this->setData(self::MESSAGE, $message);
        return $this;
    }

    /**
     * @param $messageId
     * @return SmsOrder
     */
    public function setMessageId($messageId)
    {
        $this->setData(self::MESSAGE_ID, $messageId);
        return $this;
    }
}
