<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Plugin\Order\Shipment;

use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\UpdateShipment;
use Dotdigitalgroup\Sms\Plugin\Order\Shipment\ShipmentUpdatePlugin;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\AddTrack as UpdateShipmentAction;
use PHPUnit\Framework\TestCase;

class ShipmentUpdatePluginTest extends TestCase
{
    /**
     * @var Configuration|\PHPUnit\Framework\MockObject\MockObject
     */
    private $moduleConfigMock;

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
        $this->moduleConfigMock = $this->createMock(Configuration::class);
        $this->updateShipmentMock = $this->createMock(UpdateShipment::class);
        $this->orderRepositoryInterfaceMock = $this->createMock(OrderRepositoryInterface::class);
        $this->shipmentRepositoryInterfaceMock = $this->createMock(ShipmentRepositoryInterface::class);
        $this->updateShipmentActionMock = $this->createMock(UpdateShipmentAction::class);
        $this->requestInterfaceMock = $this->createMock(RequestInterface::class);
        $this->shipmentInterfaceMock = $this->createMock(ShipmentInterface::class);
        $this->orderInterfaceMock = $this->createMock(OrderInterface::class);
        $contextMock = $this->createMock(Context::class);

        $contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestInterfaceMock);

        $this->plugin = new ShipmentUpdatePlugin(
            $this->moduleConfigMock,
            $this->orderRepositoryInterfaceMock,
            $this->updateShipmentMock,
            $this->shipmentRepositoryInterfaceMock,
            $contextMock
        );
    }

    public function testAfterExecuteMethod()
    {
        $shipmentId = 1;
        $trackingNumber = 12345;
        $trackingCode = 'chaz';

        $this->moduleConfigMock->expects($this->once())
            ->method('isTransactionalSmsEnabled')
            ->willReturn(1);

        $this->requestInterfaceMock
            ->expects($this->exactly(3))
            ->method('getParam')
            ->with($this->logicalOr(
                $this->equalTo('shipment_id'),
                $this->equalTo('number'),
                $this->equalTo('title')
            ))
            ->willReturnCallback(function ($param) use ($shipmentId, $trackingNumber, $trackingCode) {
                return match ($param) {
                    'shipment_id' => $shipmentId,
                    'number' => $trackingNumber,
                    'title' => $trackingCode,
                };
            });

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
            ->method('buildAdditionalData')
            ->with(
                $this->orderInterfaceMock,
                $trackingNumber,
                $trackingCode
            )->willReturn($this->updateShipmentMock);

        $this->updateShipmentMock
            ->expects($this->once())
            ->method('queue');

        $this->plugin->afterExecute(
            $this->updateShipmentActionMock,
            $this->createMock(ResultInterface::class)
        );
    }
}
