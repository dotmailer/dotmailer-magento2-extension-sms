<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\Item;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DataObject;

class NewAccountSignup extends DataObject implements QueueItemInterface
{
    /**
     * @var string
     */
    private $smsType = ConfigInterface::SMS_TYPE_NEW_ACCOUNT_SIGN_UP;

    /**
     * @var int
     */
    private $smsConfigPath  = ConfigInterface::XML_PATH_SMS_NEW_ACCOUNT_SIGNUP_ENABLED;

    /**
     * Prepare object properties.
     *
     * @param CustomerInterface $customer
     * @param string $mobileNumber
     *
     * @return $this
     */
    public function prepare(CustomerInterface $customer, string $mobileNumber)
    {
        $this
            ->setWebsiteId($customer->getWebsiteId())
            ->setStoreId($customer->getStoreId())
            ->setTypeId($this->smsType)
            ->setPhoneNumber($mobileNumber)
            ->setEmail($customer->getEmail())
            ->setAdditionalData([
                "firstName" => $customer->getFirstName(),
                "lastName" => $customer->getLastName()
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
