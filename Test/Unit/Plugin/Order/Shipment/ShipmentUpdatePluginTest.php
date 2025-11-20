<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Plugin\Order\Shipment;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher;
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
     * @var SmsMessagePublisher|\PHPUnit\Framework\MockObject\MockObject
     */
    private $smsMessagePublisherMock;

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
        $this->smsMessagePublisherMock = $this->createMock(SmsMessagePublisher::class);
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
            $this->shipmentRepositoryInterfaceMock,
            $this->smsMessagePublisherMock,
            $contextMock
        );
    }

    public function testAfterExecuteMethod()
    {
        $shipmentId = 1;
        $orderId = 1;
        $trackingNumber = 12345;
        $trackingCode = 'chaz';

        $this->requestInterfaceMock
            ->expects($this->exactly(3))
            ->method('getParam')
            ->willReturnCallback(function ($param) use ($shipmentId, $trackingNumber, $trackingCode) {
                if ($param === 'shipment_id') {
                    return $shipmentId;
                }
                if ($param === 'number') {
                    return $trackingNumber;
                }
                if ($param === 'title') {
                    return $trackingCode;
                }
                return null;
            });

        $this->shipmentRepositoryInterfaceMock
            ->expects($this->once())
            ->method('get')
            ->with($shipmentId)
            ->willReturn($this->shipmentInterfaceMock);

        $this->shipmentInterfaceMock
            ->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);

        $this->moduleConfigMock->expects($this->once())
            ->method('isTransactionalSmsEnabled')
            ->with(1)
            ->willReturn(true);

        $this->shipmentInterfaceMock
            ->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId);

        $this->orderRepositoryInterfaceMock
            ->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->orderInterfaceMock);

        $this->smsMessagePublisherMock
            ->expects($this->once())
            ->method('publish')
            ->with(
                ConfigInterface::SMS_TYPE_UPDATE_SHIPMENT,
                [
                    'order' => $this->orderInterfaceMock,
                    'trackingNumber' => $trackingNumber,
                    'trackingCarrier' => $trackingCode
                ]
            );

        $this->plugin->afterExecute(
            $this->updateShipmentActionMock,
            $this->createMock(ResultInterface::class)
        );
    }
}
