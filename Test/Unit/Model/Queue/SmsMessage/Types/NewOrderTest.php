<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\SmsMessage\Types;

use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\Data\OrderPhoneNumberFinder;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewOrder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewOrderTest extends TestCase
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
     * @var NewOrder
     */
    private $newOrder;

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

    public function testNewOrderExtractsDataCorrectly(): void
    {
        $storeId = 1;
        $websiteId = 1;
        $orderId = 100;
        $telephone = '+1234567890';
        $email = 'customer@example.com';
        $orderStatus = 'pending';

        $this->storeMock->method('getWebsiteId')->willReturn($websiteId);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);
        $this->orderMock->method('getId')->willReturn($orderId);
        $this->orderMock->method('getStatus')->willReturn($orderStatus);
        $this->orderMock->method('getCustomerEmail')->willReturn($email);

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willReturn($telephone);

        $this->newOrder = new NewOrder(
            $this->orderMock,
            $this->phoneNumberFinderMock
        );

        $this->assertEquals($storeId, $this->newOrder->getStoreId());
        $this->assertEquals($websiteId, $this->newOrder->getWebsiteId());
        $this->assertEquals($orderId, $this->newOrder->getOrderId());
        $this->assertEquals($telephone, $this->newOrder->getPhoneNumber());
        $this->assertEquals($email, $this->newOrder->getEmail());
        $this->assertEquals(['orderStatus' => $orderStatus], $this->newOrder->getAdditionalData());
    }

    public function testNewOrderHandlesMissingBillingAddress(): void
    {
        $telephone = '+1234567890';

        $this->storeMock->method('getWebsiteId')->willReturn(1);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn(1);
        $this->orderMock->method('getId')->willReturn(100);
        $this->orderMock->method('getStatus')->willReturn('pending');
        $this->orderMock->method('getCustomerEmail')->willReturn('test@example.com');

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willReturn($telephone);

        $this->newOrder = new NewOrder(
            $this->orderMock,
            $this->phoneNumberFinderMock
        );

        $this->assertEquals($telephone, $this->newOrder->getPhoneNumber());
    }

    public function testNewOrderHandlesMissingBothAddresses(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No telephone number supplied for order');

        $this->storeMock->method('getWebsiteId')->willReturn(1);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn(1);
        $this->orderMock->method('getId')->willReturn(100);
        $this->orderMock->method('getStatus')->willReturn('pending');
        $this->orderMock->method('getCustomerEmail')->willReturn('test@example.com');
        $this->orderMock->method('getIncrementId')->willReturn('100000001');

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willThrowException(
                new \InvalidArgumentException(
                    'No telephone number supplied for order 100000001, not queueing transactional SMS.'
                )
            );

        new NewOrder(
            $this->orderMock,
            $this->phoneNumberFinderMock
        );
    }

    public function testNewOrderHandlesMissingOrder(): void
    {
        $this->expectException(\TypeError::class);

        new NewOrder(null, $this->phoneNumberFinderMock);
    }
}
