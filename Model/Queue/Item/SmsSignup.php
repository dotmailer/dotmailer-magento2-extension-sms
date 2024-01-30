<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Item;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Magento\Framework\DataObject;

class SmsSignup extends DataObject implements QueueItemInterface
{
    /**
     * @var string
     */
    private $smsType = ConfigInterface::SMS_TYPE_SIGN_UP;

    /**
     * @var int
     */
    private $smsConfigPath  = ConfigInterface::XML_PATH_SMS_SIGNUP_ENABLED;

    /**
     * Prepare object properties.
     *
     * @param string $mobileNumber
     * @param string $email
     * @param int $websiteId
     * @param int $storeId
     * @param string $firstName
     * @param string $lastName
     *
     * @return $this
     */
    public function prepare(
        string $mobileNumber,
        string $email,
        int $websiteId,
        int $storeId,
        string $firstName,
        string $lastName
    ) {
        $this
            ->setWebsiteId($websiteId)
            ->setStoreId($storeId)
            ->setTypeId($this->smsType)
            ->setPhoneNumber($mobileNumber)
            ->setEmail($email)
            ->setAdditionalData([
                "firstName" => $firstName,
                "lastName" => $lastName
            ]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSmsType()
    {
        return $this->smsType;
    }

    /**
     * @inheritDoc
     */
    public function getSmsConfigPath()
    {
        return $this->smsConfigPath;
    }

    /**
     * @inheritDoc
     */
    public function getWebsiteId()
    {
        return $this->getData('website_id');
    }

    /**
     * @inheritDoc
     */
    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    /**
     * @inheritDoc
     */
    public function getPhoneNumber()
    {
        return $this->getData('phone_number');
    }

    /**
     * @inheritDoc
     */
    public function getEmail()
    {
        return $this->getData('email');
    }

    /**
     * @inheritDoc
     */
    public function getTypeId()
    {
        return $this->getData('type_id');
    }

    /**
     * @inheritDoc
     */
    public function getAdditionalData()
    {
        return $this->getData('additional_data');
    }
}
