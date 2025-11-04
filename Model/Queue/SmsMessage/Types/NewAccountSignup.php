<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types;

use Dotdigitalgroup\Sms\Api\Data\SmsMessageTypeInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DataObject;

class NewAccountSignup extends DataObject implements SmsMessageTypeInterface
{

    /**
     * @var CustomerInterface
     */
    private $customer;

    /**
     * @var string
     */
    private $mobileNumber;

    /**
     * @param CustomerInterface $customer Passed by factory
     * @param string $mobileNumber Passed by factory
     */
    public function __construct(
        CustomerInterface $customer,
        string $mobileNumber
    ) {
        $this->customer = $customer;
        $this->mobileNumber = $mobileNumber;
        parent::__construct($this->prepare());
    }

    /**
     * @inheritDoc
     */
    public function prepare(): array
    {
        return [
            'website_id' => (int) $this->customer->getWebsiteId(),
            'store_id' => (int) $this->customer->getStoreId(),
            'phone_number' => $this->mobileNumber,
            'email' => $this->customer->getEmail(),
            'additional_data' => [
                'firstName' => $this->customer->getFirstname(),
                'lastName' => $this->customer->getLastname()
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWebsiteId()
    {
        return $this->getData('website_id');
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    /**
     * @inheritdoc
     */
    public function getPhoneNumber()
    {
        return $this->getData('phone_number');
    }

    /**
     * @inheritdoc
     */
    public function getEmail()
    {
        return $this->getData('email');
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalData()
    {
        return $this->getData('additional_data');
    }

    /**
     * @inheritdoc
     */
    public function getOrderId()
    {
        return $this->getData('order_id');
    }
}
