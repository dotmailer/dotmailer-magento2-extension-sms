<?php

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;

class UpdateOrder extends AbstractOrderItem
{
    /**
     * @var string
     */
    protected $smsConfigPath = ConfigInterface::XML_PATH_SMS_ORDER_UPDATE_ENABLED;

    /**
     * @var int
     */
    protected $smsType = ConfigInterface::SMS_TYPE_UPDATE_ORDER;

    /**
     * @param $order
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function queue($order)
    {
        if ($this->isCanceledOrHolded($order)) {
            parent::queue($order);
        }
    }

    /**
     * @param $order
     * @return bool
     */
    private function isCanceledOrHolded($order)
    {
        return $order->getStatus() === 'canceled' || $order->getStatus() === 'holded';
    }
}
