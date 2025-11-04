<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types;

use Dotdigitalgroup\Sms\Api\Data\SmsMessageTypeInterface;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\Data\OrderPhoneNumberFinder;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;

class NewCreditMemo extends DataObject implements SmsMessageTypeInterface
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
     * @var CreditmemoInterface
     */
    private $creditMemo;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @param OrderInterface $order
     * @param OrderPhoneNumberFinder $phoneNumberFinder
     * @param CreditmemoInterface $creditMemo
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        OrderInterface $order,
        OrderPhoneNumberFinder $phoneNumberFinder,
        CreditmemoInterface $creditMemo,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->order = $order;
        $this->phoneNumberFinder = $phoneNumberFinder;
        $this->creditMemo = $creditMemo;
        $this->priceCurrency = $priceCurrency;
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

        $creditMemoAmount = $this->priceCurrency->format(
            $this->creditMemo->getGrandTotal(),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->creditMemo->getStoreId(),
            $this->creditMemo->getOrderCurrencyCode()
        );
        return [
            'website_id' => (int) $order->getStore()->getWebsiteId(),
            'store_id' => (int) $order->getStoreId(),
            'order_id' => (int) $order->getId(),
            'phone_number' => (string) $phoneNumber,
            'email' => $order->getCustomerEmail(),
            'additional_data' => [
                'orderStatus' => $order->getStatus(),
                'creditMemoAmount' => $creditMemoAmount
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
