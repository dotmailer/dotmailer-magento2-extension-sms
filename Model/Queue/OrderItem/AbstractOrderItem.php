<?php

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\Data\AdditionalData;
use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class AbstractOrderItem
 *
 * @deprecated This class is deprecated and will be removed in a future version.
 */

abstract class AbstractOrderItem
{
    /**
     * @var string
     */
    protected $smsConfigPath;

    /**
     * @var int
     */
    protected $smsType;

    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * @var AdditionalData
     */
    protected $additionalData;

    /**
     * @var OrderItemNotificationEnqueuer
     */
    private $orderItemNotificationEnqueuer;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * AbstractOrderItem constructor.
     *
     * @param OrderItemNotificationEnqueuer $orderItemNotificationEnqueuer
     * @param SerializerInterface $serializer
     * @param Logger $logger
     * @param AdditionalData $additionalData
     */
    public function __construct(
        OrderItemNotificationEnqueuer $orderItemNotificationEnqueuer,
        SerializerInterface $serializer,
        Logger $logger,
        AdditionalData $additionalData
    ) {
        $this->orderItemNotificationEnqueuer = $orderItemNotificationEnqueuer;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->additionalData = $additionalData;
    }

    /**
     * Queue order item notification.
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function queue()
    {
        $this->orderItemNotificationEnqueuer
            ->queue(
                $this->order,
                $this->serialiseData(),
                $this->smsConfigPath,
                $this->smsType
            );
    }

    /**
     * Serialize the data.
     *
     * @return string
     */
    private function serialiseData()
    {
        try {
            return $this->serializer->serialize($this->additionalData->getAdditionalData());
        } catch (InvalidArgumentException $e) {
            $this->logger->debug((string) $e);
            return '';
        }
    }
}
