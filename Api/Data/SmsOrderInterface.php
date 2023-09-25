<?php

namespace Dotdigitalgroup\Sms\Api\Data;

interface SmsOrderInterface
{
    public const ID = 'id';
    public const WEBSITE_ID = 'website_id';
    public const STORE_ID = 'store_id';
    public const STATUS = 'status';
    public const TYPE_ID = 'type_id';
    public const ORDER_ID = 'order_id';
    public const PHONE_NUMBER = 'phone_number';
    public const EMAIL = 'email';
    public const MESSAGE = 'message';
    public const MESSAGE_ID = 'message_id';
    public const ADDITIONAL_DATA = 'additional_data';
    public const SENT_AT = 'sent_at';
    public const CONTENT = 'content';

    /**
     * Get id.
     *
     * @return mixed
     */
    public function getId();

    /**
     * Get website id.
     *
     * @return int
     */
    public function getWebsiteId();

    /**
     * Get store id.
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus();

    /**
     * Get type id.
     *
     * @return mixed
     */
    public function getTypeId();

    /**
     * Get order id.
     *
     * @return int
     */
    public function getOrderId();

    /**
     * Get phone number.
     *
     * @return string
     */
    public function getPhoneNumber();

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail();

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage();

    /**
     * Get message id.
     *
     * @return string
     */
    public function getMessageId();

    /**
     * Get sent at.
     *
     * @return string
     */
    public function getSentAt();

    /**
     * Get additional data.
     *
     * @return string
     */
    public function getAdditionalData();

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent();

    /**
     * Set website id.
     *
     * @param string|int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId);

    /**
     * Set store id.
     *
     * @param string|int $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * Set status.
     *
     * @param string|int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Set type id.
     *
     * @param string|int $typeId
     * @return $this
     */
    public function setTypeId($typeId);

    /**
     * Set order id.
     *
     * @param string|int $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Set phone number.
     *
     * @param string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber($phoneNumber);

    /**
     * Set email.
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email);

    /**
     * Set message.
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * Set message id.
     *
     * @param string|int $messageId
     * @return $this
     */
    public function setMessageId($messageId);

    /**
     * Set sent at.
     *
     * @param string $sentAt
     * @return $this
     */
    public function setSentAt($sentAt);

    /**
     * Set additional data.
     *
     * @param mixed $data
     * @return $this
     */
    public function setAdditionalData($data);

    /**
     * Set content.
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content);
}
