<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types;

use Dotdigitalgroup\Sms\Api\Data\SmsMessageTypeInterface;
use Magento\Framework\DataObject;

class SmsSignup extends DataObject implements SmsMessageTypeInterface
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
     * @var string
     */
    private $mobileNumber;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string|null
     */
    private $firstName;

    /**
     * @var string|null
     */
    private $lastName;

    /**
     * @param int $websiteId
     * @param int $storeId
     * @param string $mobileNumber
     * @param string $email
     * @param string|null $firstName
     * @param string|null $lastName
     */
    public function __construct(
        int $websiteId,
        int $storeId,
        string $mobileNumber,
        string $email,
        ?string $firstName = null,
        ?string $lastName = null
    ) {
        $this->websiteId = $websiteId;
        $this->storeId = $storeId;
        $this->mobileNumber = $mobileNumber;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        parent::__construct($this->prepare());
    }

    /**
     * @inheritDoc
     */
    public function prepare(): array
    {
        return [
            'website_id' => $this->websiteId,
            'store_id' => $this->storeId,
            'phone_number' => $this->mobileNumber,
            'email' => $this->email,
            'additional_data' => [
                'firstName' => $this->firstName,
                'lastName' => $this->lastName
            ]
        ];
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
    public function getAdditionalData()
    {
        return $this->getData('additional_data');
    }

    /**
     * @inheritDoc
     */
    public function getOrderId(): ?int
    {
        return $this->getData('order_id');
    }
}
