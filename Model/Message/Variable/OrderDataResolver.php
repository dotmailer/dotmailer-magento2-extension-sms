<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Message\Variable;

use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class OrderDataResolver implements ResolverInterface
{
    /**
     * @var Utility
     */
    private $variableUtility;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string[]
     */
    private $templateVariables = [
        'first_name',
        'last_name',
        'email',
        'store_name',
        'entity_id',
        'order_id',
        'order_status',
        'tracking_number',
        'tracking_carrier',
        'refund_amount'
    ];

    /**
     *  OrderDataResolver constructor.
     *
     * @param Utility $variableUtility
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Utility $variableUtility,
        OrderRepositoryInterface $orderRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->variableUtility = $variableUtility;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $variable, SmsMessageInterface $sms)
    {
        if (!in_array($variable, $this->templateVariables)) {
            return '';
        }

        $method = $this->variableUtility->getMethodFromVariable($variable);
        return (string) $this->$method($sms);
    }

    /**
     * Get first name.
     *
     * @param SmsMessageInterface $sms
     * @return string|null
     */
    private function getFirstName($sms)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($sms->getOrderId());

        if ($order->getCustomerFirstname() === null) {
            return $order->getShippingAddress()->getFirstname();
        }

        return $order->getCustomerFirstname();
    }

    /**
     * Get last name.
     *
     * @param SmsMessageInterface $sms
     * @return string|null
     */
    private function getLastName($sms)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($sms->getOrderId());

        if ($order->getCustomerLastname() === null) {
            return $order->getShippingAddress()->getLastname();
        }

        return $order->getCustomerLastname();
    }

    /**
     * Get email.
     *
     * @param SmsMessageInterface $sms
     */
    private function getEmail($sms)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($sms->getOrderId());
        return $order->getCustomerEmail();
    }

    /**
     * Get store name.
     *
     * @param SmsMessageInterface $sms
     * @return string
     * @throws NoSuchEntityException
     */
    private function getStoreName($sms)
    {
        $groupId = $this->storeManager->getStore($sms->getStoreId())->getStoreGroupId();
        return $this->storeManager->getGroup($groupId)->getName();
    }

    /**
     * Get true order id.
     *
     * @param SmsMessageInterface $sms
     * @return int|null
     */
    private function getEntityId($sms)
    {
        $order = $this->orderRepository->get($sms->getOrderId());
        return $order->getEntityId();
    }

    /**
     * Get order id.
     *
     * @param SmsMessageInterface $sms
     * @return float|string|null
     */
    private function getOrderId($sms)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($sms->getOrderId());
        return $order->getRealOrderId();
    }

    /**
     * Get order status.
     *
     * @param SmsMessageInterface $sms
     * @return string
     */
    private function getOrderStatus($sms)
    {
        return $this->variableUtility->getAdditionalDataByKey($sms, 'orderStatus');
    }

    /**
     * Get tracking number.
     *
     * @param SmsMessageInterface $sms
     * @return string
     */
    private function getTrackingNumber($sms)
    {
        return $this->variableUtility->getAdditionalDataByKey($sms, 'trackingNumber');
    }

    /**
     * Get tracking carrier.
     *
     * @param SmsMessageInterface $sms
     * @return string
     */
    private function getTrackingCarrier($sms)
    {
        return $this->variableUtility->getAdditionalDataByKey($sms, 'trackingCarrier');
    }

    /**
     * Get refund amount.
     *
     * @param SmsMessageInterface $sms
     * @return string
     */
    private function getRefundAmount($sms)
    {
        return $this->variableUtility->getAdditionalDataByKey($sms, 'creditMemoAmount');
    }
}
