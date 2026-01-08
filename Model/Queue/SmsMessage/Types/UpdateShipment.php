<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types;

use Dotdigitalgroup\Sms\Api\Data\SmsMessageTypeInterface;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\Data\OrderPhoneNumberFinder;
use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\OrderInterface;

class UpdateShipment extends DataObject implements SmsMessageTypeInterface
{
    /**
     * @var OrderInterface
     */
    private $order;

    /**
     * @var OrderPhoneNumberFinder
     */
    private $phoneNumberFinder;

    /**
     * @var string
     */
    private $trackingNumber;

    /**
     * @var string
     */
    private $trackingCarrier;

    /**
     * @param OrderInterface $order
     * @param OrderPhoneNumberFinder $phoneNumberFinder
     * @param string $trackingNumber
     * @param string $trackingCarrier
     */
    public function __construct(
        OrderInterface $order,
        OrderPhoneNumberFinder $phoneNumberFinder,
        string $trackingNumber,
        string $trackingCarrier
    ) {
        $this->order = $order;
        $this->phoneNumberFinder = $phoneNumberFinder;
        $this->trackingNumber = $trackingNumber;
        $this->trackingCarrier = $trackingCarrier;
        parent::__construct($this->prepare());
    }

    /**
     * @inheritDoc
     */
    public function prepare(): array
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->order;
        $phoneNumber = $this->phoneNumberFinder->getPhoneNumber($order);

        return [
            'website_id' => (int) $order->getStore()->getWebsiteId(),
            'store_id' => (int) $order->getStoreId(),
            'order_id' => (int) $order->getId(),
            'phone_number' => (string) $phoneNumber,
            'email' => $order->getCustomerEmail(),
            'additional_data' => [
                'orderStatus' => $order->getStatus(),
                'trackingNumber' => $this->trackingNumber,
                'trackingCarrier' => $this->trackingCarrier
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
