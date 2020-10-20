<?php

namespace Dotdigitalgroup\Sms\Api\Data;

interface SmsOrderInterface
{
    const WEBSITE_ID = 'website_id';
    const STORE_ID = 'store_id';
    const STATUS = 'status';
    const TYPE_ID = 'type_id';
    const ORDER_ID = 'order_id';
    const PHONE_NUMBER = 'phone_number';
    const MESSAGE = 'message';
    const MESSAGE_ID = 'message_id';
    const ADDITIONAL_DATA = 'additional_data';
    const SENT_AT = 'sent_at';

    /**
     * @return int
     */
    public function getWebsiteId();

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @return mixed
     */
    public function getTypeId();

    /**
     * @return int
     */
    public function getOrderId();

    /**
     * @return string
     */
    public function getPhoneNumber();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return string
     */
    public function getMessageId();

    /**
     * @return string
     */
    public function getAdditionalData();

    /**
     * @param $websiteId;
     * @return $this
     */
    public function setWebsiteId($websiteId);

    /**
     * @param $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @param $typeId
     * @return $this
     */
    public function setTypeId($typeId);

    /**
     * @param $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * @param $phoneNumber
     * @return $this
     */
    public function setPhoneNumber($phoneNumber);

    /**
     * @param $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * @param $messageId
     * @return $this
     */
    public function setMessageId($messageId);

    /**
     * @param $data
     * @return $this
     */
    public function setAdditionalData($data);
}
