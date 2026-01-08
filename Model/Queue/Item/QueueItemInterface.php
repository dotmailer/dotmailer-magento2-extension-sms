<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Item;

/**
 * Class QueueItemInterface
 *
 * @deprecated This class is deprecated and will be removed in a future version.
 * @see \Dotdigitalgroup\Sms\Api\Data\SmsMessageTypeInterface
 */

interface QueueItemInterface
{
    /**
     * Get SMS type id.
     *
     * Any new implementations of this interface should set a resolver in DI, using this type id as the key.
     *
     * @return int
     */
    public function getSmsType();

    /**
     * Get SMS config path.
     *
     * @return string
     */
    public function getSmsConfigPath();

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
     * Get type id.
     *
     * @return int
     */
    public function getTypeId();

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
}
