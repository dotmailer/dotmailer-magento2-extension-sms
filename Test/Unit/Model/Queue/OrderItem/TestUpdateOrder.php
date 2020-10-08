<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\UpdateOrder;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\OrderItemNotificationEnqueuer;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\TestCase;

class TestUpdateOrder extends TestCase
{
    /**
     * @var OrderItemNotificationEnqueuer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $smsOrderNotificationEnqueuerMock;

    /**
     * @var UpdateOrder
     */
    private $updateOrder;

    /**
     * @var OrderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderInterfaceMock;

    protected function setUp() :void
    {
        $this->smsOrderNotificationEnqueuerMock = $this->createMock(OrderItemNotificationEnqueuer::class);
        $this->orderInterfaceMock = $this->createMock(OrderInterface::class);

        $this->updateOrder = new UpdateOrder(
            $this->smsOrderNotificationEnqueuerMock
        );
    }

    public function testQueueIfOrderIsCanceled()
    {
        $this->orderInterfaceMock
            ->expects($this->atLeastOnce())
            ->method('getStatus')
            ->willReturn('canceled');

        $this->smsOrderNotificationEnqueuerMock
            ->expects($this->once())
            ->method('queue')
            ->with(
                $this->orderInterfaceMock,
                ConfigInterface::XML_PATH_SMS_ORDER_UPDATE_ENABLED,
                ConfigInterface::SMS_TYPE_UPDATE_ORDER
            );

        $this->updateOrder->queue($this->orderInterfaceMock);
    }

    public function testQueueIfOrderIsHolded()
    {
        $this->orderInterfaceMock
            ->expects($this->atLeastOnce())
            ->method('getStatus')
            ->willReturn('holded');

        $this->smsOrderNotificationEnqueuerMock
            ->expects($this->once())
            ->method('queue')
            ->with(
                $this->orderInterfaceMock,
                ConfigInterface::XML_PATH_SMS_ORDER_UPDATE_ENABLED,
                ConfigInterface::SMS_TYPE_UPDATE_ORDER
            );

        $this->updateOrder->queue($this->orderInterfaceMock);
    }

    public function testQueueIfOrderIsNotCanceledOrHolded()
    {
        $this->orderInterfaceMock
            ->expects($this->atLeastOnce())
            ->method('getStatus')
            ->willReturn('processing');

        $this->smsOrderNotificationEnqueuerMock
            ->expects($this->never())
            ->method('queue');

        $this->updateOrder->queue($this->orderInterfaceMock);
    }
}
