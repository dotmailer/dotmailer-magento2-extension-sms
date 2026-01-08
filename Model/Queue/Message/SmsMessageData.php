<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Message;

class SmsMessageData
{
    /**
     * @var int
     */
    private $websiteId;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var int
     */
    private $typeId;

    /**
     * @var int
     */
    private $orderId;

    /**
     * @var string
     */
    private $phoneNumber;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $additionalData;

    /**
     * Set website ID.
     *
     * @param int $websiteId
     * @return SmsMessageData
     */
    public function setWebsiteId(int $websiteId): SmsMessageData
    {
        $this->websiteId = $websiteId;
        return $this;
    }

    /**
     * Get website ID.
     *
     * @return int
     */
    public function getWebsiteId(): int
    {
        return $this->websiteId ?? 0;
    }

    /**
     * Set store ID.
     *
     * @param int $storeId
     * @return SmsMessageData
     */
    public function setStoreId(int $storeId): SmsMessageData
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * Get store ID.
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return $this->storeId ?? 0;
    }

    /**
     * Set type ID.
     *
     * @param int $typeId
     * @return SmsMessageData
     */
    public function setTypeId(int $typeId): SmsMessageData
    {
        $this->typeId = $typeId;
        return $this;
    }

    /**
     * Get type ID.
     *
     * @return int
     */
    public function getTypeId(): int
    {
        return $this->typeId ?? 0;
    }

    /**
     * Set order ID.
     *
     * @param int|null $orderId
     * @return SmsMessageData
     */
    public function setOrderId(?int $orderId): SmsMessageData
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * Get order ID.
     *
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId ?? 0;
    }

    /**
     * Set phone number.
     *
     * @param string $phoneNumber
     * @return SmsMessageData
     */
    public function setPhoneNumber(string $phoneNumber): SmsMessageData
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * Get phone number.
     *
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber ?? '';
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return SmsMessageData
     */
    public function setEmail(string $email): SmsMessageData
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email ?? '';
    }

    /**
     * Set additional data.
     *
     * @param string $additionalData
     * @return SmsMessageData
     */
    public function setAdditionalData(string $additionalData): SmsMessageData
    {
        $this->additionalData = $additionalData;
        return $this;
    }

    /**
     * Get additional data.
     *
     * @return string
     */
    public function getAdditionalData(): string
    {
        return $this->additionalData ?? '';
    }
}
