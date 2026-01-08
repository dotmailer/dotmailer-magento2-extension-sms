<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\SmsMessage\Types;

use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\SmsSignup;
use PHPUnit\Framework\TestCase;

class SmsSignupTest extends TestCase
{
    /**
     * @var SmsSignup
     */
    private $smsSignup;

    public function testSmsSignupExtractsDataCorrectly(): void
    {
        $websiteId = 1;
        $storeId = 1;
        $mobileNumber = '+1234567890';
        $email = 'customer@example.com';
        $firstName = 'John';
        $lastName = 'Doe';

        $this->smsSignup = new SmsSignup(
            $websiteId,
            $storeId,
            $mobileNumber,
            $email,
            $firstName,
            $lastName
        );

        $this->assertEquals($storeId, $this->smsSignup->getStoreId());
        $this->assertEquals($websiteId, $this->smsSignup->getWebsiteId());
        $this->assertNull($this->smsSignup->getOrderId());
        $this->assertEquals($mobileNumber, $this->smsSignup->getPhoneNumber());
        $this->assertEquals($email, $this->smsSignup->getEmail());
        $this->assertEquals([
            'firstName' => $firstName,
            'lastName' => $lastName
        ], $this->smsSignup->getAdditionalData());
    }

    public function testSmsSignupWithoutOptionalNames(): void
    {
        $websiteId = 1;
        $storeId = 1;
        $mobileNumber = '+1234567890';
        $email = 'customer@example.com';

        $this->smsSignup = new SmsSignup(
            $websiteId,
            $storeId,
            $mobileNumber,
            $email
        );

        $this->assertEquals($storeId, $this->smsSignup->getStoreId());
        $this->assertEquals($websiteId, $this->smsSignup->getWebsiteId());
        $this->assertEquals($mobileNumber, $this->smsSignup->getPhoneNumber());
        $this->assertEquals($email, $this->smsSignup->getEmail());
        $this->assertEquals([
            'firstName' => null,
            'lastName' => null
        ], $this->smsSignup->getAdditionalData());
    }

    public function testSmsSignupHandlesEmptyEmail(): void
    {
        $websiteId = 1;
        $storeId = 1;
        $mobileNumber = '+1234567890';

        $this->smsSignup = new SmsSignup(
            $websiteId,
            $storeId,
            $mobileNumber,
            ''
        );

        $this->assertEquals('', $this->smsSignup->getEmail());
        $this->assertEquals($mobileNumber, $this->smsSignup->getPhoneNumber());
    }
}
