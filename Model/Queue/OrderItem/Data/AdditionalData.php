<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem\Data;

class AdditionalData
{
    /**
     * @var string
     */
    public $trackingNumber;

    /**
     * @var string
     */
    public $trackingCarrier;

    /**
     * @var string
     */
    public $creditMemoAmount;

    /**
     * @var string
     */
    public $orderStatus;

    /**
     * Get additional data.
     *
     * @return array
     */
    public function getAdditionalData(): array
    {
        return array_filter((array) $this);
    }

    /**
     * Set credit memo amount.
     *
     * @param string $creditMemoAmount
     */
    public function setCreditMemoAmount(string $creditMemoAmount): void
    {
        $this->creditMemoAmount = $creditMemoAmount;
    }

    /**
     * Set order status.
     *
     * @param string $orderStatus
     */
    public function setOrderStatus(string $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
    }

    /**
     * Set tracking carrier.
     *
     * @param string $trackingCarrier
     */
    public function setTrackingCarrier(string $trackingCarrier): void
    {
        $this->trackingCarrier = $trackingCarrier;
    }

    /**
     * Set tracking number.
     *
     * @param string $trackingNumber
     */
    public function setTrackingNumber(string $trackingNumber): void
    {
        $this->trackingNumber = $trackingNumber;
    }
}
