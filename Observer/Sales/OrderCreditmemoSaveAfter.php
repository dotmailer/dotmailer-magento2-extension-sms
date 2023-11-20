<?php

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewCreditMemo;
use Dotdigitalgroup\Sms\Model\Sales\SmsSalesService;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;

class OrderCreditmemoSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * OrderCreditmemoSaveAfter constructor.
     *
     * @param Configuration $moduleConfig
     * @param NewCreditMemo $newCreditMemo
     * @param SmsSalesService $smsSalesService
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Configuration $moduleConfig,
        NewCreditMemo $newCreditMemo,
        SmsSalesService $smsSalesService,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->newCreditMemo = $newCreditMemo;
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
        if (!$this->moduleConfig->isSmsEnabled($storeId)) {
            return;
        }

        if ($this->smsSalesService->isOrderCreditmemoSaveAfterExecuted()) {
            return;
        }

        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();

        $this->newCreditMemo
            ->buildAdditionalData($order, $creditmemo)
            ->queue();

        $this->smsSalesService->setIsOrderCreditmemoSaveAfterExecuted();
    }
}
