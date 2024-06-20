<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\Message;

use Dotdigitalgroup\Sms\Model\Queue\Message\MarketingSmsSubscribeData;
use PHPUnit\Framework\TestCase;

class MarketingSmsSubscribeDataTest extends TestCase
{
    /**
     * @var MarketingSmsSubscribeData
     *
     * Instance of the class that is being tested.
     */
    private $marketingSmsSubscribeData;

    /**
     * This method is called before each test.
     *
     * It initializes the instance of the class that is being tested.
     */
    protected function setUp(): void
    {
        $this->marketingSmsSubscribeData = new MarketingSmsSubscribeData();
    }

    /**
     * This method tests the setWebsiteId and getWebsiteId methods of the MarketingSmsSubscribeData class.
     *
     * It sets a website ID, retrieves it and then checks if the retrieved value is the same as the set value.
     */
    public function testCanSetAndGetWebsiteId(): void
    {
        $websiteId = 1;
        $this->marketingSmsSubscribeData->setWebsiteId($websiteId);
        $this->assertEquals($websiteId, $this->marketingSmsSubscribeData->getWebsiteId());
    }

    /**
     * This method tests the setContactId and getContactId methods of the MarketingSmsSubscribeData class.
     *
     * It sets a contact ID, retrieves it and then checks if the retrieved value is the same as the set value.
     */
    public function testCanSetAndGetContactId(): void
    {
        $contactId = 1;
        $this->marketingSmsSubscribeData->setContactId($contactId);
        $this->assertEquals($contactId, $this->marketingSmsSubscribeData->getContactId());
    }
}
