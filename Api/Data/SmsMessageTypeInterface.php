<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Api\Data;

interface SmsMessageTypeInterface
{
    /**
     * Prepare message data.
     *
     * @return array
     */
    public function prepare(): array;

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
     * Get additional data.
     *
     * @return array
     */
    public function getAdditionalData();

    /**
     * Get order id.
     *
     * @return int
     */
    public function getOrderId();
}
