<?php

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewCreditMemo;
use Dotdigitalgroup\Sms\Model\Sales\SmsSalesService;
use Magento\Framework\Event\Observer;

class OrderCreditmemoSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
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
     * @param NewCreditMemo $newCreditMemo
     * @param SmsSalesService $smsSalesService
     */
    public function __construct(
        NewCreditMemo $newCreditMemo,
        SmsSalesService $smsSalesService
    ) {
        $this->newCreditMemo = $newCreditMemo;
        $this->smsSalesService = $smsSalesService;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
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
