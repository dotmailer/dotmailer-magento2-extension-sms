<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Model\Queue\OrderItem\UpdateShipment;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\OrderItemNotificationEnqueuer;
use Magento\Sales\Api\Data\OrderInterface;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use PHPUnit\Framework\TestCase;

class TestUpdateShipment extends TestCase
{
    /**
     * @var OrderItemNotificationEnqueuer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $smsOrderNotificationEnqueuerMock;

    /**
     * @var UpdateShipment
     */
    private $updateShipment;

    /**
     * @var OrderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderInterfaceMock;

    protected function setUp() :void
    {
        $this->smsOrderNotificationEnqueuerMock = $this->createMock(OrderItemNotificationEnqueuer::class);
        $this->orderInterfaceMock = $this->createMock(OrderInterface::class);

        $this->updateShipment = new UpdateShipment(
            $this->smsOrderNotificationEnqueuerMock
        );
    }

    public function testQueue()
    {
        $this->smsOrderNotificationEnqueuerMock
            ->expects($this->once())
            ->method('queue')
            ->with(
                $this->orderInterfaceMock,
                ConfigInterface::XML_PATH_SMS_SHIPMENT_UPDATE_ENABLED,
                ConfigInterface::SMS_TYPE_UPDATE_SHIPMENT
            );

        $this->updateShipment->queue($this->orderInterfaceMock);
    }
}
