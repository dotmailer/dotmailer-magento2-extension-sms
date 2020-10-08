<?php

namespace Dotdigitalgroup\Sms\Model\Queue\OrderItem;

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
     * @var OrderItemNotificationEnqueuer
     */
    private $orderItemNotificationEnqueuer;

    /**
     * AbstractOrderItem constructor.
     * @param OrderItemNotificationEnqueuer $orderItemNotificationEnqueuer
     */
    public function __construct(
        OrderItemNotificationEnqueuer $orderItemNotificationEnqueuer
    ) {
        $this->orderItemNotificationEnqueuer = $orderItemNotificationEnqueuer;
    }

    /**
     * @param $order
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function queue($order)
    {
        $this->orderItemNotificationEnqueuer
            ->queue(
                $order,
                $this->smsConfigPath,
                $this->smsType
            );
    }
}
