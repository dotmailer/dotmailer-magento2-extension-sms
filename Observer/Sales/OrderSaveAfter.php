<?php

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Magento\Framework\Event\Observer;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\UpdateOrder;

class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var UpdateOrder
     */
    private $updateOrder;

    /**
     * OrderSaveAfter constructor.
     * @param UpdateOrder $updateOrder
     */
    public function __construct(
        UpdateOrder $updateOrder
    ) {
        $this->updateOrder = $updateOrder;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->updateOrder
            ->queue($order);
    }
}
