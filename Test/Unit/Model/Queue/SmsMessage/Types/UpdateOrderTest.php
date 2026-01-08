<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\SmsMessage\Types;

use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\Data\OrderPhoneNumberFinder;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\UpdateOrder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateOrderTest extends TestCase
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
     * @var UpdateOrder
     */
    private $updateOrder;

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

    public function testUpdateOrderExtractsDataCorrectly(): void
    {
        $storeId = 1;
        $websiteId = 1;
        $orderId = 100;
        $telephone = '+1234567890';
        $email = 'customer@example.com';
        $orderStatus = 'processing';

        $this->storeMock->method('getWebsiteId')->willReturn($websiteId);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);
        $this->orderMock->method('getId')->willReturn($orderId);
        $this->orderMock->method('getStatus')->willReturn($orderStatus);
        $this->orderMock->method('getCustomerEmail')->willReturn($email);

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willReturn($telephone);

        $this->updateOrder = new UpdateOrder(
            $this->orderMock,
            $this->phoneNumberFinderMock
        );

        $this->assertEquals($storeId, $this->updateOrder->getStoreId());
        $this->assertEquals($websiteId, $this->updateOrder->getWebsiteId());
        $this->assertEquals($orderId, $this->updateOrder->getOrderId());
        $this->assertEquals($telephone, $this->updateOrder->getPhoneNumber());
        $this->assertEquals($email, $this->updateOrder->getEmail());
        $this->assertEquals(['orderStatus' => $orderStatus], $this->updateOrder->getAdditionalData());
    }

    public function testUpdateOrderHandlesMissingBillingAddress(): void
    {
        $telephone = '+1234567890';

        $this->storeMock->method('getWebsiteId')->willReturn(1);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn(1);
        $this->orderMock->method('getId')->willReturn(100);
        $this->orderMock->method('getStatus')->willReturn('processing');
        $this->orderMock->method('getCustomerEmail')->willReturn('test@example.com');

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willReturn($telephone);

        $this->updateOrder = new UpdateOrder(
            $this->orderMock,
            $this->phoneNumberFinderMock
        );

        $this->assertEquals($telephone, $this->updateOrder->getPhoneNumber());
    }

    public function testUpdateOrderHandlesMissingBothAddresses(): void
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

        new UpdateOrder(
            $this->orderMock,
            $this->phoneNumberFinderMock
        );
    }

    public function testUpdateOrderHandlesMissingOrder(): void
    {
        $this->expectException(\TypeError::class);

        new UpdateOrder(null, $this->phoneNumberFinderMock);
    }
}
