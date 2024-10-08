<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewCreditMemo;
use Dotdigitalgroup\Sms\Model\Sales\SmsSalesService;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;

class OrderCreditmemoSaveAfter implements \Magento\Framework\Event\ObserverInterface
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
     * @var NewCreditMemo
     */
    private $newCreditMemo;

    /**
     * @var SmsSalesService
     */
    private $smsSalesService;

    /**
     * OrderCreditmemoSaveAfter constructor.
     *
     * @param Logger $logger
     * @param Configuration $moduleConfig
     * @param NewCreditMemo $newCreditMemo
     * @param SmsSalesService $smsSalesService
     */
    public function __construct(
        Logger $logger,
        Configuration $moduleConfig,
        NewCreditMemo $newCreditMemo,
        SmsSalesService $smsSalesService
    ) {
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->newCreditMemo = $newCreditMemo;
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
        if ($this->smsSalesService->isOrderCreditmemoSaveAfterExecuted()) {
            return $this;
        }

        try {
            $creditmemo = $observer->getEvent()->getCreditmemo();
            $order = $creditmemo->getOrder();
            $storeId = $order->getStoreId();

            if (!$this->moduleConfig->isTransactionalSmsEnabled($storeId)) {
                return $this;
            }

            $this->newCreditMemo
                ->buildAdditionalData($order, $creditmemo)
                ->queue();

            $this->smsSalesService->setIsOrderCreditmemoSaveAfterExecuted();
        } catch (\Exception $e) {
            $this->logger->error('Error in SMS OrderCreditmemoSaveAfter observer', [(string) $e]);
        }

        return $this;
    }
}
