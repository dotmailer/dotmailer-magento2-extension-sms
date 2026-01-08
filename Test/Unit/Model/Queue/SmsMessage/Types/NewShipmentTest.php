<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\SmsMessage\Types;

use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\Data\OrderPhoneNumberFinder;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewShipment;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewShipmentTest extends TestCase
{
    /**
     * @var OrderInterface|MockObject
     */
    private $orderMock;

    /**
     * @var OrderAddressInterface|MockObject
     */
    private $billingAddressMock;

    /**
     * @var OrderAddressInterface|MockObject
     */
    private $shippingAddressMock;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMock;

    /**
     * @var OrderPhoneNumberFinder|MockObject
     */
    private $phoneNumberFinderMock;

    /**
     * @var NewShipment
     */
    private $newShipment;

    protected function setUp(): void
    {
        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
            ->addMethods(['getShippingAddress', 'getStore', 'getId'])
            ->getMockForAbstractClass();
        $this->billingAddressMock = $this->createMock(OrderAddressInterface::class);
        $this->shippingAddressMock = $this->createMock(OrderAddressInterface::class);
        $this->storeMock = $this->createMock(StoreInterface::class);
        $this->phoneNumberFinderMock = $this->createMock(OrderPhoneNumberFinder::class);
    }

    public function testNewShipmentExtractsDataCorrectly(): void
    {
        $storeId = 1;
        $websiteId = 1;
        $orderId = 100;
        $telephone = '+1234567890';
        $email = 'customer@example.com';
        $orderStatus = 'processing';
        $trackingNumber = 'TRACK123';
        $trackingCarrier = 'UPS';

        $this->storeMock->method('getWebsiteId')->willReturn($websiteId);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);
        $this->orderMock->method('getId')->willReturn($orderId);
        $this->orderMock->method('getStatus')->willReturn($orderStatus);
        $this->orderMock->method('getCustomerEmail')->willReturn($email);

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willReturn($telephone);

        $this->newShipment = new NewShipment(
            $this->orderMock,
            $this->phoneNumberFinderMock,
            $trackingNumber,
            $trackingCarrier
        );

        $this->assertEquals($storeId, $this->newShipment->getStoreId());
        $this->assertEquals($websiteId, $this->newShipment->getWebsiteId());
        $this->assertEquals($orderId, $this->newShipment->getOrderId());
        $this->assertEquals($telephone, $this->newShipment->getPhoneNumber());
        $this->assertEquals($email, $this->newShipment->getEmail());
        $this->assertEquals([
            'orderStatus' => $orderStatus,
            'trackingNumber' => $trackingNumber,
            'trackingCarrier' => $trackingCarrier
        ], $this->newShipment->getAdditionalData());
    }

    public function testNewShipmentHandlesMissingBillingAddress(): void
    {
        $telephone = '+1234567890';
        $trackingNumber = 'TRACK123';
        $trackingCarrier = 'UPS';

        $this->storeMock->method('getWebsiteId')->willReturn(1);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn(1);
        $this->orderMock->method('getId')->willReturn(100);
        $this->orderMock->method('getStatus')->willReturn('processing');
        $this->orderMock->method('getCustomerEmail')->willReturn('test@example.com');

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willReturn($telephone);

        $this->newShipment = new NewShipment(
            $this->orderMock,
            $this->phoneNumberFinderMock,
            $trackingNumber,
            $trackingCarrier
        );

        $this->assertEquals($telephone, $this->newShipment->getPhoneNumber());
    }

    public function testNewShipmentHandlesMissingBothAddresses(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No telephone number supplied for order');

        $this->storeMock->method('getWebsiteId')->willReturn(1);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn(1);
        $this->orderMock->method('getId')->willReturn(100);
        $this->orderMock->method('getStatus')->willReturn('processing');
        $this->orderMock->method('getCustomerEmail')->willReturn('test@example.com');
        $this->orderMock->method('getIncrementId')->willReturn('100000001');

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willThrowException(
                new \InvalidArgumentException(
                    'No telephone number supplied for order 100000001, not queueing transactional SMS.'
                )
            );

        new NewShipment(
            $this->orderMock,
            $this->phoneNumberFinderMock,
            'TRACK123',
            'UPS'
        );
    }

    public function testNewShipmentWithEmptyTrackingData(): void
    {
        $storeId = 1;
        $websiteId = 1;
        $orderId = 100;
        $telephone = '+1234567890';
        $orderStatus = 'processing';

        $this->storeMock->method('getWebsiteId')->willReturn($websiteId);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);
        $this->orderMock->method('getId')->willReturn($orderId);
        $this->orderMock->method('getStatus')->willReturn($orderStatus);
        $this->orderMock->method('getCustomerEmail')->willReturn('test@example.com');

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willReturn($telephone);

        $this->newShipment = new NewShipment(
            $this->orderMock,
            $this->phoneNumberFinderMock,
            '',
            ''
        );

        $additionalData = $this->newShipment->getAdditionalData();
        $this->assertEquals($orderStatus, $additionalData['orderStatus']);
        $this->assertEquals('', $additionalData['trackingNumber']);
        $this->assertEquals('', $additionalData['trackingCarrier']);
    }
}
