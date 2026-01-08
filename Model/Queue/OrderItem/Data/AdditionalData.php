<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem\Data;

/**
 * Class AdditionalData
 *
 * @deprecated This class is deprecated and will be removed in a future version.
 * @see \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\AbstractMessageType
 * See the SmsMessage\Types namespace for all current message types.
 */

class AdditionalData
{
    /**
     * @var string|null
     */
    public $trackingNumber;

    /**
     * @var string|null
     */
    public $trackingCarrier;

    /**
     * @var string|null
     */
    public $creditMemoAmount;

    /**
     * @var string|null
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
     * @param string|null $creditMemoAmount
     */
    public function setCreditMemoAmount(?string $creditMemoAmount): void
    {
        $this->creditMemoAmount = $creditMemoAmount;
    }

    /**
     * Set order status.
     *
     * @param string|null $orderStatus
     */
    public function setOrderStatus(?string $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
    }

    /**
     * Set tracking carrier.
     *
     * @param string|null $trackingCarrier
     */
    public function setTrackingCarrier(?string $trackingCarrier): void
    {
        $this->trackingCarrier = $trackingCarrier;
    }

    /**
     * Set tracking number.
     *
     * @param string|null $trackingNumber
     */
    public function setTrackingNumber(?string $trackingNumber): void
    {
        $this->trackingNumber = $trackingNumber;
    }
}
