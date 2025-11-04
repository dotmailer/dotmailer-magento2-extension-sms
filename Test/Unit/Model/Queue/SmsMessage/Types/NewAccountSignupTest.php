<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\SmsMessage\Types;

use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewAccountSignup;
use Magento\Customer\Api\Data\CustomerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewAccountSignupTest extends TestCase
{
    /**
     * @var CustomerInterface|MockObject
     */
    private $customerMock;

    /**
     * @var NewAccountSignup
     */
    private $newAccountSignup;

    protected function setUp(): void
    {
        $this->customerMock = $this->createMock(CustomerInterface::class);
    }

    public function testNewAccountSignupExtractsDataCorrectly(): void
    {
        $storeId = 1;
        $websiteId = 1;
        $mobileNumber = '+1234567890';
        $email = 'customer@example.com';
        $firstName = 'John';
        $lastName = 'Doe';

        $this->customerMock->method('getStoreId')->willReturn($storeId);
        $this->customerMock->method('getWebsiteId')->willReturn($websiteId);
        $this->customerMock->method('getEmail')->willReturn($email);
        $this->customerMock->method('getFirstname')->willReturn($firstName);
        $this->customerMock->method('getLastname')->willReturn($lastName);

        $this->newAccountSignup = new NewAccountSignup(
            $this->customerMock,
            $mobileNumber
        );

        $this->assertEquals($storeId, $this->newAccountSignup->getStoreId());
        $this->assertEquals($websiteId, $this->newAccountSignup->getWebsiteId());
        $this->assertNull($this->newAccountSignup->getOrderId());
        $this->assertEquals($mobileNumber, $this->newAccountSignup->getPhoneNumber());
        $this->assertEquals($email, $this->newAccountSignup->getEmail());
        $this->assertEquals([
            'firstName' => $firstName,
            'lastName' => $lastName
        ], $this->newAccountSignup->getAdditionalData());
    }

    public function testNewAccountSignupHandlesEmptyMobileNumber(): void
    {
        $this->customerMock->method('getStoreId')->willReturn(1);
        $this->customerMock->method('getWebsiteId')->willReturn(1);
        $this->customerMock->method('getEmail')->willReturn('test@example.com');
        $this->customerMock->method('getFirstname')->willReturn('Test');
        $this->customerMock->method('getLastname')->willReturn('User');

        $this->newAccountSignup = new NewAccountSignup(
            $this->customerMock,
            ''
        );

        $this->assertEquals('', $this->newAccountSignup->getPhoneNumber());
    }
}
