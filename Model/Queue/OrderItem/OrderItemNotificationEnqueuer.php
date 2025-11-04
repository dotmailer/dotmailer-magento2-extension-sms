<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterfaceFactory;
use Dotdigitalgroup\Sms\Api\SmsMessageRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class AfterSendProcessor
 *
 * @deprecated This class is deprecated and will be removed in a future version.
 * The queue system now uses a publisher and consumer.
 * @see \Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher
 */

class OrderItemNotificationEnqueuer
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SmsMessageInterfaceFactory
     */
    private $smsMessageInterfaceFactory;

    /**
     * @var SmsMessageRepositoryInterface
     */
    private $smsMessageRepositoryInterface;

    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * OrderItemNotificationEnqueuer constructor.
     *
     * @param Logger $logger
     * @param SmsMessageInterfaceFactory $smsMessageInterfaceFactory
     * @param SmsMessageRepositoryInterface $smsMessageRepositoryInterface
     * @param Configuration $moduleConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Logger $logger,
        SmsMessageInterfaceFactory $smsMessageInterfaceFactory,
        SmsMessageRepositoryInterface $smsMessageRepositoryInterface,
        Configuration $moduleConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->smsMessageInterfaceFactory = $smsMessageInterfaceFactory;
        $this->smsMessageRepositoryInterface = $smsMessageRepositoryInterface;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Queue the SMS Order.
     *
     * @param OrderInterface $order
     * @param string $additionalData
     * @param string $smsConfigPath
     * @param string|int $smsType
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function queue($order, $additionalData, $smsConfigPath, $smsType)
    {
        $storeId = $order->getStoreId();

        if (!$this->moduleConfig->isTransactionalSmsEnabled($storeId) ||
            !$this->moduleConfig->isSmsTypeEnabled($storeId, $smsConfigPath)) {
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $address = $order->getShippingAddress() ?? $order->getBillingAddress();
        if (!$address->getTelephone()) {
            $this->logger->debug(sprintf(
                'No telephone number supplied for order %s, not queueing transactional SMS.',
                $order->getIncrementId()
            ));
            return;
        }

        $orderId = (int) $order->getId();
        $smsMessage = $this->smsMessageInterfaceFactory
            ->create()
            ->setOrderId($orderId)
            ->setStoreId($storeId)
            ->setWebsiteId($this->storeManager->getStore($storeId)->getWebsiteId())
            ->setTypeId($smsType)
            ->setStatus(0)
            ->setPhoneNumber($address->getTelephone())
            ->setEmail($order->getCustomerEmail())
            ->setAdditionalData($additionalData);

        $this->smsMessageRepositoryInterface
            ->save($smsMessage);
    }
}
