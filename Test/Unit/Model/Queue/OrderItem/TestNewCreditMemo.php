<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\OrderItem;

use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewCreditMemo;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\OrderItemNotificationEnqueuer;
use Magento\Sales\Api\Data\OrderInterface;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use PHPUnit\Framework\TestCase;

class TestNewCreditMemo extends TestCase
{
    /**
     * @var OrderItemNotificationEnqueuer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $smsOrderNotificationEnqueuerMock;

    /**
     * @var NewCreditMemo
     */
    private $newCreditMemo;

    /**
     * @var OrderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderInterfaceMock;

    protected function setUp() :void
    {
        $this->smsOrderNotificationEnqueuerMock = $this->createMock(OrderItemNotificationEnqueuer::class);
        $this->orderInterfaceMock = $this->createMock(OrderInterface::class);

        $this->newCreditMemo = new NewCreditMemo(
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
                ConfigInterface::XML_PATH_SMS_NEW_CREDIT_MEMO_ENABLED,
                ConfigInterface::SMS_TYPE_NEW_CREDIT_MEMO
            );

        $this->newCreditMemo->queue($this->orderInterfaceMock);
    }
}
