<?php

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\Data\AdditionalData;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;

class NewCreditMemo extends AbstractOrderItem
{
    /**
     * @var string
     */
    protected $smsType = ConfigInterface::SMS_TYPE_NEW_CREDIT_MEMO;

    /**
     * @var int
     */
    protected $smsConfigPath  = ConfigInterface::XML_PATH_SMS_NEW_CREDIT_MEMO_ENABLED;

    /**
     * @var PriceCurrencyInterface
     */
    private $currencyInterface;

    /**
     * NewCreditMemo constructor.
     *
     * @param OrderItemNotificationEnqueuer $orderItemNotificationEnqueuer
     * @param SerializerInterface $serializer
     * @param Logger $logger
     * @param PriceCurrencyInterface $currencyInterface
     * @param AdditionalData $additionalData
     */
    public function __construct(
        OrderItemNotificationEnqueuer $orderItemNotificationEnqueuer,
        SerializerInterface $serializer,
        Logger $logger,
        PriceCurrencyInterface $currencyInterface,
        AdditionalData $additionalData
    ) {
        $this->currencyInterface = $currencyInterface;
        parent::__construct($orderItemNotificationEnqueuer, $serializer, $logger, $additionalData);
    }

    /**
     * Build additional data for the credit memo.
     *
     * @param OrderInterface $order
     * @param mixed $creditMemo
     * @return NewCreditMemo
     */
    public function buildAdditionalData($order, $creditMemo)
    {
        $this->order = $order;
        $this->additionalData->setOrderStatus($order->getStatus());

        $this->additionalData->setCreditMemoAmount(
            $this->currencyInterface->format(
                $creditMemo->getGrandTotal(),
                false,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $creditMemo->getStoreId(),
                $creditMemo->getOrderCurrencyCode()
            )
        );

        return $this;
    }
}
