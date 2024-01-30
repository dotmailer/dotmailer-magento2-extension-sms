<?php

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\UpdateOrder;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewOrder;
use Dotdigitalgroup\Sms\Model\Sales\SmsSalesService;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var UpdateOrder
     */
    private $updateOrder;

    /**
     * @var NewOrder
     */
    private $newOrder;

    /**
     * @var SmsSalesService
     */
    private $smsSalesService;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * OrderSaveAfter constructor.
     *
     * @param Configuration $moduleConfig
     * @param UpdateOrder $updateOrder
     * @param NewOrder $newOrder
     * @param SmsSalesService $smsSalesService
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Configuration $moduleConfig,
        UpdateOrder $updateOrder,
        NewOrder $newOrder,
        SmsSalesService $smsSalesService,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->updateOrder = $updateOrder;
        $this->newOrder = $newOrder;
        $this->smsSalesService = $smsSalesService;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute observer.
     *
     * @param Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $storeId = $this->storeManager->getStore()->getId();
        if (!$this->moduleConfig->isTransactionalSmsEnabled($storeId)) {
            return;
        }

        if ($this->smsSalesService->isOrderSaveAfterExecuted()) {
            return;
        }

        $order = $observer->getEvent()->getOrder();

        if ($this->isCanceledOrHolded($order)) {
            $this->updateOrder
                ->buildAdditionalData($order)
                ->queue();
        }

        if ($this->isNewOrder($order)) {
            $this->newOrder
                ->buildAdditionalData($order)
                ->queue();
        }

        $this->smsSalesService->setIsOrderSaveAfterExecuted();
    }

    /**
     * Check if order is canceled or holded.
     *
     * @param OrderInterface $order
     * @return bool
     */
    private function isCanceledOrHolded($order)
    {
        return $order->getStatus() === 'canceled' || $order->getStatus() === 'holded';
    }

    /**
     * Check if order is new.
     *
     * @param OrderInterface $order
     * @return bool
     */
    private function isNewOrder($order)
    {
        return $order->getCreatedAt() === $order->getUpdatedAt();
    }
}
