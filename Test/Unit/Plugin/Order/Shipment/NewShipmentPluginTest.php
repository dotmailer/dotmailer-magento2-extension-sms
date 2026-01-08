<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Plugin\Order\Shipment;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher;
use Dotdigitalgroup\Sms\Plugin\Order\Shipment\NewShipmentPlugin;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save as NewShipmentAction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewShipmentPluginTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Configuration|MockObject
     */
    private $moduleConfigMock;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var SmsMessagePublisher|MockObject
     */
    private $smsMessagePublisherMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var NewShipmentPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->moduleConfigMock = $this->createMock(Configuration::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->smsMessagePublisherMock = $this->createMock(SmsMessagePublisher::class);
        $this->requestMock = $this->createMock(RequestInterface::class);

        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getRequest')->willReturn($this->requestMock);

        $this->plugin = new NewShipmentPlugin(
            $this->loggerMock,
            $this->moduleConfigMock,
            $this->orderRepositoryMock,
            $this->smsMessagePublisherMock,
            $contextMock
        );
    }

    public function testAfterExecutePublishesShipmentMessages(): void
    {
        $orderId = 123;
        $storeId = 1;
        $trackings = [
            ['number' => 'TRACK001', 'title' => 'UPS'],
            ['number' => 'TRACK002', 'title' => 'FedEx']
        ];

        $subjectMock = $this->createMock(NewShipmentAction::class);
        $resultMock = $this->createMock(ResultInterface::class);
        $orderMock = $this->createMock(OrderInterface::class);

        $this->requestMock->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnCallback(function ($param) use ($orderId, $trackings) {
                return match ($param) {
                    'order_id' => $orderId,
                    'tracking' => $trackings,
                    default => null,
                };
            });

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->moduleConfigMock->expects($this->once())
            ->method('isTransactionalSmsEnabled')
            ->with($storeId)
            ->willReturn(true);

        $publishCalls = [];
        $this->smsMessagePublisherMock->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function ($typeId, $data) use (&$publishCalls) {
                $publishCalls[] = [$typeId, $data];
                return true;
            });

        $result = $this->plugin->afterExecute($subjectMock, $resultMock);

        $this->assertSame($resultMock, $result);
        $this->assertCount(2, $publishCalls);
        $this->assertEquals(ConfigInterface::SMS_TYPE_NEW_SHIPMENT, $publishCalls[0][0]);
        $this->assertEquals('TRACK001', $publishCalls[0][1]['trackingNumber']);
        $this->assertEquals('UPS', $publishCalls[0][1]['trackingCarrier']);
        $this->assertEquals(ConfigInterface::SMS_TYPE_NEW_SHIPMENT, $publishCalls[1][0]);
        $this->assertEquals('TRACK002', $publishCalls[1][1]['trackingNumber']);
        $this->assertEquals('FedEx', $publishCalls[1][1]['trackingCarrier']);
    }

    public function testAfterExecuteReturnsEarlyWhenOrderCannotBeLoaded(): void
    {
        $orderId = 123;

        $subjectMock = $this->createMock(NewShipmentAction::class);
        $resultMock = $this->createMock(ResultInterface::class);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('order_id')
            ->willReturn($orderId);

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willThrowException(new \Exception('Order not found'));

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('Could not load order for shipment', $this->anything());

        $this->moduleConfigMock->expects($this->never())
            ->method('isTransactionalSmsEnabled');

        $this->smsMessagePublisherMock->expects($this->never())
            ->method('publish');

        $result = $this->plugin->afterExecute($subjectMock, $resultMock);

        $this->assertSame($resultMock, $result);
    }

    public function testAfterExecuteReturnsEarlyWhenTransactionalSmsDisabled(): void
    {
        $orderId = 123;
        $storeId = 1;

        $subjectMock = $this->createMock(NewShipmentAction::class);
        $resultMock = $this->createMock(ResultInterface::class);
        $orderMock = $this->createMock(OrderInterface::class);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('order_id')
            ->willReturn($orderId);

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->moduleConfigMock->expects($this->once())
            ->method('isTransactionalSmsEnabled')
            ->with($storeId)
            ->willReturn(false);

        $this->smsMessagePublisherMock->expects($this->never())
            ->method('publish');

        $result = $this->plugin->afterExecute($subjectMock, $resultMock);

        $this->assertSame($resultMock, $result);
    }

    public function testAfterExecuteHandlesNoTrackingData(): void
    {
        $orderId = 123;
        $storeId = 1;

        $subjectMock = $this->createMock(NewShipmentAction::class);
        $resultMock = $this->createMock(ResultInterface::class);
        $orderMock = $this->createMock(OrderInterface::class);

        $this->requestMock->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnCallback(function ($param) use ($orderId) {
                return match ($param) {
                    'order_id' => $orderId,
                    'tracking' => null,
                    default => null,
                };
            });

        $this->orderRepositoryMock->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->moduleConfigMock->expects($this->once())
            ->method('isTransactionalSmsEnabled')
            ->with($storeId)
            ->willReturn(true);

        $this->smsMessagePublisherMock->expects($this->never())
            ->method('publish');

        $result = $this->plugin->afterExecute($subjectMock, $resultMock);

        $this->assertSame($resultMock, $result);
    }
}
