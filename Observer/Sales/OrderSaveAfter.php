<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\UpdateOrder;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewOrder;
use Dotdigitalgroup\Sms\Model\Sales\SmsSalesService;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;

class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

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
     * OrderSaveAfter constructor.
     *
     * @param Logger $logger
     * @param Configuration $moduleConfig
     * @param UpdateOrder $updateOrder
     * @param NewOrder $newOrder
     * @param SmsSalesService $smsSalesService
     */
    public function __construct(
        Logger $logger,
        Configuration $moduleConfig,
        UpdateOrder $updateOrder,
        NewOrder $newOrder,
        SmsSalesService $smsSalesService
    ) {
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->updateOrder = $updateOrder;
        $this->newOrder = $newOrder;
        $this->smsSalesService = $smsSalesService;
    }

    /**
     * Execute observer.
     *
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $storeId = $order->getStoreId();

            if (!$this->moduleConfig->isTransactionalSmsEnabled($storeId)) {
                return $this;
            }

            if ($this->smsSalesService->isOrderSaveAfterExecuted()) {
                return $this;
            }

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
        } catch (\Exception $e) {
            $this->logger->error('Error in SMS OrderSaveAfter observer', [(string) $e]);
        }

        return $this;
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
