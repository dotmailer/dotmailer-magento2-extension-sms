<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Plugin\Order\Shipment;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\OrderItem\NewShipment;
use Dotdigitalgroup\Sms\Plugin\Order\Shipment\NewShipmentPlugin;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save as NewShipmentAction;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class NewShipmentPluginTest extends TestCase
{
    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var Configuration|\PHPUnit\Framework\MockObject\MockObject
     */
    private $moduleConfigMock;

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
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeManagerMock;

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
        $this->loggerMock = $this->createMock(Logger::class);
        $this->moduleConfigMock = $this->createMock(Configuration::class);
        $this->orderRepositoryInterfaceMock = $this->createMock(OrderRepositoryInterface::class);
        $this->newShipmentActionMock = $this->createMock(NewShipmentAction::class);
        $this->newShipmentMock = $this->createMock(NewShipment::class);
        $this->requestInterfaceMock = $this->createMock(RequestInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->orderInterfaceMock = $this->createMock(OrderInterface::class);
        $contextMock = $this->createMock(Context::class);

        $contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestInterfaceMock);

        $this->plugin = new NewShipmentPlugin(
            $this->loggerMock,
            $this->moduleConfigMock,
            $this->orderRepositoryInterfaceMock,
            $this->newShipmentMock,
            $contextMock,
            $this->storeManagerMock
        );
    }

    public function testAfterExecuteMethodIfTrackingDefined()
    {
        $orderId = 1;
        $tracking = [[
            'number' => 35589,
            'carrier_code' => 'chaz',
            'title' => 'Chaz Express'
        ]];

        $this->checkEnabled();

        $this->requestInterfaceMock
            ->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(
                ['order_id'],
                ['tracking']
            )
            ->willReturnOnConsecutiveCalls(
                $orderId,
                $tracking
            );

        $this->orderRepositoryInterfaceMock
            ->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->orderInterfaceMock);

        $this->newShipmentMock
            ->expects($this->once())
            ->method('buildAdditionalData')
            ->with(
                $this->orderInterfaceMock,
                $tracking[0]['number'],
                $tracking[0]['title']
            )
            ->willReturn($this->newShipmentMock);

        $this->newShipmentMock
            ->expects($this->once())
            ->method('queue');

        $this->plugin->afterExecute($this->newShipmentActionMock, []);
    }

    public function testAfterExecuteMethodIfTrackingNotDefined()
    {
        $orderId = 1;
        $tracking = null;

        $this->checkEnabled();

        $this->requestInterfaceMock
            ->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(
                ['order_id'],
                ['tracking']
            )
            ->willReturnOnConsecutiveCalls(
                $orderId,
                $tracking
            );

        $this->orderRepositoryInterfaceMock
            ->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($this->orderInterfaceMock);

        $this->newShipmentMock
            ->expects($this->never())
            ->method('buildAdditionalData');

        $this->newShipmentMock
            ->expects($this->never())
            ->method('queue');

        $this->plugin->afterExecute($this->newShipmentActionMock, []);
    }

    private function checkEnabled()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->moduleConfigMock->expects($this->once())
            ->method('isTransactionalSmsEnabled')
            ->willReturn(1);
    }
}
