<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher;
use Dotdigitalgroup\Sms\Model\Sales\SmsSalesService;
use Magento\Framework\Event\Observer;

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
     * @var SmsMessagePublisher
     */
    private $smsMessagePublisher;

    /**
     * @var SmsSalesService
     */
    private $smsSalesService;

    /**
     * @param Logger $logger
     * @param Configuration $moduleConfig
     * @param SmsMessagePublisher $smsMessagePublisher
     * @param SmsSalesService $smsSalesService
     */
    public function __construct(
        Logger $logger,
        Configuration $moduleConfig,
        SmsMessagePublisher $smsMessagePublisher,
        SmsSalesService $smsSalesService
    ) {
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->smsMessagePublisher = $smsMessagePublisher;
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

            $this->smsMessagePublisher->publish(
                ConfigInterface::SMS_TYPE_NEW_CREDIT_MEMO,
                [
                    'order' => $order,
                    'creditmemo' => $creditmemo
                ]
            );

            $this->smsSalesService->setIsOrderCreditmemoSaveAfterExecuted();
        } catch (\Exception $e) {
            $this->logger->error('Error in SMS OrderCreditmemoSaveAfter observer', [(string) $e]);
        }

        return $this;
    }
}
