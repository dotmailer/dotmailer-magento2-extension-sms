<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\Consumer;

use Dotdigital\V3\Models\Contact as ContactModel;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigital\V3\Models\ContactFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\Client as V2Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory as V3ClientFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client as V3Client;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Consumer\SmsSubscriptionConsumer;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Exporter;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\ExporterFactory;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Retriever;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\RetrieverFactory;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsSubscriptionData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SmsSubscriptionConsumerTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var SmsSubscriptionConsumer
     */
    private $smsSubscriptionConsumer;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ContactFactory|MockObject
     */
    private $contactFactoryMock;

    /**
     * @var V3ClientFactory|MockObject
     */
    private $v3ClientFactoryMock;

    /**
     * @var Configuration|MockObject
     */
    private $smsConfigMock;

    /**
     * @var Exporter|MockObject
     */
    private $exporterFactoryMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var RetrieverFactory|MockObject
     */
    private $retrieverFactoryMock;

    /**
     * @var SmsSubscriptionData|MockObject
     */
    private $smsSubscribeDataMock;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->contactFactoryMock = $this->createMock(ContactFactory::class);
        $this->v3ClientFactoryMock = $this->createMock(V3ClientFactory::class);
        $this->smsConfigMock = $this->createMock(Configuration::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->retrieverFactoryMock = $this->createMock(RetrieverFactory::class);
        $this->smsSubscribeDataMock = $this->createMock(SmsSubscriptionData::class);
        $this->exporterFactoryMock = $this->createMock(ExporterFactory::class);

        $this->smsSubscriptionConsumer = new SmsSubscriptionConsumer(
            $this->helperMock,
            $this->loggerMock,
            $this->v3ClientFactoryMock,
            $this->smsConfigMock,
            $this->exporterFactoryMock,
            $this->scopeConfigMock,
            $this->retrieverFactoryMock
        );
    }

    public function testProcessSuccessfullySubscribesContactToMarketingSms(): void
    {
        $this->smsSubscribeDataMock->method('getType')->willReturn('subscribe');

        $v3ClientMock = $this->createMock(V3Client::class);

        $this->v3ClientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['websiteId' => 1])
            ->willReturn($v3ClientMock);

        $this->smsSubscribeDataMock->expects($this->exactly(4))
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->smsSubscribeDataMock->expects($this->exactly(3))
            ->method('getContactId')
            ->willReturn(1);

        $contactsResourceMock = $this->createMock(\Dotdigital\V3\Resources\Contacts::class);
        $v3ClientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier');

        $retrieverMock = $this->createMock(Retriever::class);
        $retrieverMock->expects($this->once())
            ->method('setWebsite')
            ->with($this->equalTo(1))
            ->willReturnSelf();

        $this->retrieverFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($retrieverMock);

        $exporterMock = $this->createMock(Exporter::class);
        $exporterMock->expects($this->once())
            ->method('setFieldMapping')
            ->willReturnSelf();

        $this->exporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($exporterMock);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('connector_data_mapping/customer_data', ScopeInterface::SCOPE_WEBSITES, 1)
            ->willReturn(['key1' => 'value1', 'key2' => 'value2']);

        $this->smsSubscriptionConsumer->process($this->smsSubscribeDataMock);
    }

    public function testProcessUnsubscribesContactFromMarketingSms(): void
    {
        $this->smsSubscribeDataMock->method('getType')->willReturn('unsubscribe');

        $v3ClientMock = $this->createMock(V3Client::class);

        $this->v3ClientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['websiteId' => 1])
            ->willReturn($v3ClientMock);

        $v2ClientMock = $this->createMock(V2Client::class);

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($v2ClientMock);

        $this->smsSubscribeDataMock->expects($this->exactly(3))
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->smsSubscribeDataMock->expects($this->never())
            ->method('getContactId');

        $contactsResourceMock = $this->createMock(\Dotdigital\V3\Resources\Contacts::class);
        $v3ClientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('getByIdentifier')
            ->willReturn($this->createMock(ContactModel::class));

        $v2ClientMock->expects($this->once())
            ->method('deleteAddressBookContact');

        $this->smsConfigMock->expects($this->once())
            ->method('getListId')
            ->with(1)
            ->willReturn(1);

        $this->smsSubscriptionConsumer->process($this->smsSubscribeDataMock);
    }
}
