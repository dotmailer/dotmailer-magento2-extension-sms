<?php

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Magento\Sales\Api\Data\OrderInterface;

class NewOrder extends AbstractOrderItem
{
    /**
     * @var int
     */
    protected $smsType = ConfigInterface::SMS_TYPE_NEW_ORDER;

    /**
     * @var string
     */
    protected $smsConfigPath  = ConfigInterface::XML_PATH_SMS_NEW_ORDER_ENABLED;

    /**
     * Build additional data for the order.
     *
     * @param OrderInterface $order
     * @return NewOrder
     */
    public function buildAdditionalData($order)
    {
        $this->order = $order;
        $this->additionalData->setOrderStatus($order->getStatus());
        return $this;
    }
}
