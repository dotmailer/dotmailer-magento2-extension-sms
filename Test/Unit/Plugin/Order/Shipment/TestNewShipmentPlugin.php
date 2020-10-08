<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Plugin\Order\Shipment;

use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewShipment;
use Dotdigitalgroup\Sms\Plugin\Order\Shipment\NewShipmentPlugin;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save as NewShipmentAction;
use PHPUnit\Framework\TestCase;

class TestNewShipmentPlugin extends TestCase
{
    /**
     * @var OrderRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepositoryInterfaceMock;

    /**
     * @var NewShipmentAction|\PHPUnit\Framework\MockObject\MockObject
     */
    private $newShipmentActionMock;

    /**
     * @var NewShipment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $newShipmentMock;

    /**
     * @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestInterfaceMock;

    /**
     * @var NewShipmentPlugin
     */
    private $plugin;

    /**
     * @var OrderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderInterfaceMock;

    protected function setUp(): void
    {
        $this->orderRepositoryInterfaceMock = $this->createMock(OrderRepositoryInterface::class);
        $this->newShipmentActionMock = $this->createMock(NewShipmentAction::class);
        $this->newShipmentMock = $this->createMock(NewShipment::class);
        $this->requestInterfaceMock = $this->createMock(RequestInterface::class);
        $this->orderInterfaceMock = $this->createMock(OrderInterface::class);

        $this->plugin = new NewShipmentPlugin(
            $this->orderRepositoryInterfaceMock,
            $this->newShipmentMock
        );
    }

    public function testAfterExecuteMethod()
    {
        $this->newShipmentActionMock
           ->expects($this->once())
           ->method('getRequest')
           ->willReturn($this->requestInterfaceMock);

        $this->requestInterfaceMock
           ->expects($this->once())
           ->method('getParam')
           ->with('order_id')
           ->willReturn($orderId = 1);

        $this->orderRepositoryInterfaceMock
           ->expects($this->once())
           ->method('get')
           ->with($orderId)
           ->willReturn($this->orderInterfaceMock);

        $this->newShipmentMock
           ->expects($this->once())
           ->method('queue')
           ->with($this->orderInterfaceMock);

        $this->plugin->afterExecute($this->newShipmentActionMock, []);
    }
}
