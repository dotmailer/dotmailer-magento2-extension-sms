<?php

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;

class UpdateShipment extends AbstractOrderItem
{
    /**
     * @var int
     */
    protected $smsType = ConfigInterface::SMS_TYPE_UPDATE_SHIPMENT;

    /**
     * @var string
     */
    protected $smsConfigPath = ConfigInterface::XML_PATH_SMS_SHIPMENT_UPDATE_ENABLED;

    /**
     * Build additional data for the shipment.
     *
     * @param OrderInterface $order
     * @param string|int $trackingNumber
     * @param string|int $carrierCode
     * @return UpdateShipment
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
