<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterfaceFactory;
use Dotdigitalgroup\Sms\Api\SmsOrderRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

class OrderItemNotificationEnqueuer
{
    /**
     * @var SmsOrderInterfaceFactory
     */
    private $smsOrderInterfaceFactory;

    /**
     * @var SmsOrderRepositoryInterface
     */
    private $smsOrderRepositoryInterface;

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
     * @param SmsOrderInterfaceFactory $smsOrderInterfaceFactory
     * @param SmsOrderRepositoryInterface $smsOrderRepositoryInterface
     * @param Configuration $moduleConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SmsOrderInterfaceFactory $smsOrderInterfaceFactory,
        SmsOrderRepositoryInterface $smsOrderRepositoryInterface,
        Configuration $moduleConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->smsOrderInterfaceFactory = $smsOrderInterfaceFactory;
        $this->smsOrderRepositoryInterface = $smsOrderRepositoryInterface;
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
        $orderId = (int) $order->getId();
        $smsOrder = $this->smsOrderInterfaceFactory
            ->create()
            ->setOrderId($orderId)
            ->setStoreId($storeId)
            ->setWebsiteId($this->storeManager->getStore($storeId)->getWebsiteId())
            ->setTypeId($smsType)
            ->setStatus(0)
            ->setPhoneNumber($address->getTelephone())
            ->setEmail($order->getCustomerEmail())
            ->setAdditionalData($additionalData);

        $this->smsOrderRepositoryInterface
            ->save($smsOrder);
    }
}
