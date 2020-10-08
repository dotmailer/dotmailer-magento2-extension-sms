<?php

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Magento\Framework\Event\Observer;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewOrder;

class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var NewOrder
     */
    private $newOrder;

    /**
     * OrderPlaceAfter constructor.
     * @param NewOrder $newOrder
     */
    public function __construct(
        NewOrder $newOrder
    ) {
        $this->newOrder = $newOrder;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->newOrder
            ->queue($order);
    }
}
