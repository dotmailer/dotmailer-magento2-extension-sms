<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher;
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
     * @var SmsSalesService
     */
    private $smsSalesService;

    /**
     * @var SmsMessagePublisher
     */
    private $smsMessagePublisher;

    /**
     * @param Logger $logger
     * @param Configuration $moduleConfig
     * @param SmsSalesService $smsSalesService
     * @param SmsMessagePublisher $smsMessagePublisher
     */
    public function __construct(
        Logger $logger,
        Configuration $moduleConfig,
        SmsSalesService $smsSalesService,
        SmsMessagePublisher $smsMessagePublisher
    ) {
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->smsSalesService = $smsSalesService;
        $this->smsMessagePublisher = $smsMessagePublisher;
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
                $this->smsMessagePublisher->publish(
                    ConfigInterface::SMS_TYPE_UPDATE_ORDER,
                    ['order' => $order]
                );
            }

            if ($this->isNewOrder($order)) {
                $this->smsMessagePublisher->publish(
                    ConfigInterface::SMS_TYPE_NEW_ORDER,
                    ['order' => $order]
                );
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
