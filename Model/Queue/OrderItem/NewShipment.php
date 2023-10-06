<?php

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;

class NewShipment extends AbstractOrderItem
{
    /**
     * @var int
     */
    protected $smsConfigPath = ConfigInterface::XML_PATH_SMS_NEW_SHIPMENT_ENABLED;

    /**
     * @var int
     */
    protected $smsType = ConfigInterface::SMS_TYPE_NEW_SHIPMENT;

    /**
     * Build additional data for the New Shipment.
     *
     * @param mixed $order
     * @param string|int $trackingNumber
     * @param string|int $carrierCode
     * @return NewShipment
     */
    public function buildAdditionalData($order, $trackingNumber, $carrierCode)
    {
        $this->order = $order;

        $this->additionalData->setOrderStatus($order->getStatus());
        $this->additionalData->setTrackingNumber($trackingNumber);
        $this->additionalData->setTrackingCarrier($carrierCode);

        return $this;
    }
}
