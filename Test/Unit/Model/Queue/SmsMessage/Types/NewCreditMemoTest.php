<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\SmsMessage\Types;

use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\Data\OrderPhoneNumberFinder;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewCreditMemo;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewCreditMemoTest extends TestCase
{
    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrencyMock;

    /**
     * @var OrderInterface|MockObject
     */
    private $orderMock;

    /**
     * @var CreditmemoInterface|MockObject
     */
    private $creditMemoMock;

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
     * @var NewCreditMemo
     */
    private $newCreditMemo;

    protected function setUp(): void
    {
        $this->priceCurrencyMock = $this->createMock(PriceCurrencyInterface::class);
        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
            ->addMethods(['getShippingAddress', 'getStore', 'getId'])
            ->getMockForAbstractClass();
        $this->creditMemoMock = $this->createMock(CreditmemoInterface::class);
        $this->billingAddressMock = $this->createMock(OrderAddressInterface::class);
        $this->shippingAddressMock = $this->createMock(OrderAddressInterface::class);
        $this->storeMock = $this->createMock(StoreInterface::class);
        $this->phoneNumberFinderMock = $this->createMock(OrderPhoneNumberFinder::class);
    }

    public function testNewCreditMemoExtractsDataCorrectly(): void
    {
        $storeId = 1;
        $websiteId = 1;
        $orderId = 100;
        $telephone = '+1234567890';
        $email = 'customer@example.com';
        $orderStatus = 'complete';
        $creditMemoTotal = 50.00;
        $formattedTotal = '$50.00';

        $this->storeMock->method('getWebsiteId')->willReturn($websiteId);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn($storeId);
        $this->orderMock->method('getId')->willReturn($orderId);
        $this->orderMock->method('getStatus')->willReturn($orderStatus);
        $this->orderMock->method('getCustomerEmail')->willReturn($email);

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willReturn($telephone);

        $this->creditMemoMock->method('getGrandTotal')->willReturn($creditMemoTotal);
        $this->creditMemoMock->method('getStoreId')->willReturn($storeId);
        $this->creditMemoMock->method('getOrderCurrencyCode')->willReturn('USD');

        $this->priceCurrencyMock->expects($this->once())
            ->method('format')
            ->with(
                $creditMemoTotal,
                false,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $storeId,
                'USD'
            )
            ->willReturn($formattedTotal);

        $this->newCreditMemo = new NewCreditMemo(
            $this->orderMock,
            $this->phoneNumberFinderMock,
            $this->creditMemoMock,
            $this->priceCurrencyMock
        );

        $this->assertEquals($storeId, $this->newCreditMemo->getStoreId());
        $this->assertEquals($websiteId, $this->newCreditMemo->getWebsiteId());
        $this->assertEquals($orderId, $this->newCreditMemo->getOrderId());
        $this->assertEquals($telephone, $this->newCreditMemo->getPhoneNumber());
        $this->assertEquals($email, $this->newCreditMemo->getEmail());
        $this->assertEquals([
            'orderStatus' => $orderStatus,
            'creditMemoAmount' => $formattedTotal
        ], $this->newCreditMemo->getAdditionalData());
    }

    public function testNewCreditMemoHandlesMissingBillingAddress(): void
    {
        $telephone = '+1234567890';

        $this->storeMock->method('getWebsiteId')->willReturn(1);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn(1);
        $this->orderMock->method('getId')->willReturn(100);
        $this->orderMock->method('getStatus')->willReturn('complete');
        $this->orderMock->method('getCustomerEmail')->willReturn('test@example.com');

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willReturn($telephone);

        $this->creditMemoMock->method('getGrandTotal')->willReturn(50.00);
        $this->creditMemoMock->method('getStoreId')->willReturn(1);
        $this->creditMemoMock->method('getOrderCurrencyCode')->willReturn('USD');

        $this->priceCurrencyMock->method('format')->willReturn('$50.00');

        $this->newCreditMemo = new NewCreditMemo(
            $this->orderMock,
            $this->phoneNumberFinderMock,
            $this->creditMemoMock,
            $this->priceCurrencyMock
        );

        $this->assertEquals($telephone, $this->newCreditMemo->getPhoneNumber());
    }

    public function testNewCreditMemoHandlesMissingBothAddresses(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No telephone number supplied for order');

        $this->storeMock->method('getWebsiteId')->willReturn(1);

        $this->orderMock->method('getStore')->willReturn($this->storeMock);
        $this->orderMock->method('getStoreId')->willReturn(1);
        $this->orderMock->method('getId')->willReturn(100);
        $this->orderMock->method('getStatus')->willReturn('complete');
        $this->orderMock->method('getCustomerEmail')->willReturn('test@example.com');
        $this->orderMock->method('getIncrementId')->willReturn('100000001');

        $this->phoneNumberFinderMock->method('getPhoneNumber')
            ->with($this->orderMock)
            ->willThrowException(
                new \InvalidArgumentException(
                    'No telephone number supplied for order 100000001, not queueing transactional SMS.'
                )
            );

        $this->creditMemoMock->method('getGrandTotal')->willReturn(50.00);
        $this->creditMemoMock->method('getStoreId')->willReturn(1);
        $this->creditMemoMock->method('getOrderCurrencyCode')->willReturn('USD');

        new NewCreditMemo(
            $this->orderMock,
            $this->phoneNumberFinderMock,
            $this->creditMemoMock,
            $this->priceCurrencyMock
        );
    }
}
