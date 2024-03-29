<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Model\Queue\OrderItem\Data\AdditionalData;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewOrder;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\OrderItemNotificationEnqueuer;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\TestCase;

class NewOrderTest extends TestCase
{
    /**
     * @var OrderItemNotificationEnqueuer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $smsOrderNotificationEnqueuerMock;

    /**
     * @var NewOrder
     */
    private $newOrder;

    /**
     * @var OrderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderInterfaceMock;

    /**
     * @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializerMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var AdditionalData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $additionalDataMock;

    protected function setUp() :void
    {
        $this->smsOrderNotificationEnqueuerMock = $this->createMock(OrderItemNotificationEnqueuer::class);
        $this->orderInterfaceMock = $this->createMock(OrderInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->additionalDataMock = $this->createMock(AdditionalData::class);

        $this->newOrder = new NewOrder(
            $this->smsOrderNotificationEnqueuerMock,
            $this->serializerMock,
            $this->loggerMock,
            $this->additionalDataMock
        );
    }

    public function testQueue()
    {
        $this->orderInterfaceMock
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn('pending');

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->willReturn($jsonData = '{"order_status": "pending"}');

        $this->smsOrderNotificationEnqueuerMock
            ->expects($this->once())
            ->method('queue')
            ->with(
                $this->orderInterfaceMock,
                $jsonData,
                ConfigInterface::XML_PATH_SMS_NEW_ORDER_ENABLED,
                ConfigInterface::SMS_TYPE_NEW_ORDER
            );

        $this->newOrder
            ->buildAdditionalData(
                $this->orderInterfaceMock
            )->queue();
    }
}
