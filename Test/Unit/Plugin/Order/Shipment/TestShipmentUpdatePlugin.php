<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Plugin\Order\Shipment;

use Dotdigitalgroup\Sms\Model\Queue\OrderItem\UpdateShipment;
use Dotdigitalgroup\Sms\Plugin\Order\Shipment\ShipmentUpdatePlugin;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\AddTrack as UpdateShipmentAction;
use PHPUnit\Framework\TestCase;

class TestShipmentUpdatePlugin extends TestCase
{
    /**
     * @var UpdateShipment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $updateShipmentMock;

    /**
     * @var OrderRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepositoryInterfaceMock;

    /**
     * @var ShipmentRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shipmentRepositoryInterfaceMock;

    /**
     * @var UpdateShipmentAction|\PHPUnit\Framework\MockObject\MockObject
     */
    private $updateShipmentActionMock;

    /**
     * @var ShipmentUpdatePlugin
     */
    private $plugin;

    /**
     * @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestInterfaceMock;

    /**
     * @var ShipmentInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shipmentInterfaceMock;

    /**
     * @var OrderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderInterfaceMock;

    protected function setUp(): void
    {
        $this->updateShipmentMock = $this->createMock(UpdateShipment::class);
        $this->orderRepositoryInterfaceMock = $this->createMock(OrderRepositoryInterface::class);
        $this->shipmentRepositoryInterfaceMock = $this->createMock(ShipmentRepositoryInterface::class);
        $this->updateShipmentActionMock = $this->createMock(UpdateShipmentAction::class);
        $this->requestInterfaceMock = $this->createMock(RequestInterface::class);
        $this->shipmentInterfaceMock = $this->createMock(ShipmentInterface::class);
        $this->orderInterfaceMock = $this->createMock(OrderInterface::class);

        $this->plugin = new ShipmentUpdatePlugin(
            $this->orderRepositoryInterfaceMock,
            $this->updateShipmentMock,
            $this->shipmentRepositoryInterfaceMock
        );
    }

    public function testAfterExecuteMethod()
    {
        $this->updateShipmentActionMock
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestInterfaceMock);

        $this->requestInterfaceMock
            ->expects($this->once())
            ->method('getParam')
            ->with('shipment_id')
            ->willReturn($shipmentId = 1);

        $this->shipmentRepositoryInterfaceMock
            ->expects($this->once())
            ->method('get')
            ->with($shipmentId)
            ->willReturn($this->shipmentInterfaceMock);

        $this->shipmentInterfaceMock
            ->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId = 1);

        $this->orderRepositoryInterfaceMock
            ->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->orderInterfaceMock);

        $this->updateShipmentMock
            ->expects($this->once())
            ->method('queue')
            ->with($this->orderInterfaceMock);

        $this->plugin->afterExecute(
            $this->updateShipmentActionMock,
            []
        );
    }
}
