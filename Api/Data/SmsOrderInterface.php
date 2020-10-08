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
     * @param $websiteId;
     */
    public function setWebsiteId($websiteId);

    /**
     * @param $storeId
     */
    public function setStoreId($storeId);

    /**
     * @param $status
     */
    public function setStatus($status);

    /**
     * @param $typeId
     */
    public function setTypeId($typeId);

    /**
     * @param $orderId
     */
    public function setOrderId($orderId);

    /**
     * @param $phoneNumber
     */
    public function setPhoneNumber($phoneNumber);

    /**
     * @param $message
     */
    public function setMessage($message);

    /**
     * @param $messageId
     */
    public function setMessageId($messageId);
}
