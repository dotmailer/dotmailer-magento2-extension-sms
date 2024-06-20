<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\Consumer;

use Dotdigitalgroup\Sms\Model\Queue\Consumer\MarketingSmsSubscribe;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigital\V3\Models\ContactFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory as V3ClientFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client as V3Client;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Exporter;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\ExporterFactory;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Retriever;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\RetrieverFactory;
use Dotdigitalgroup\Sms\Model\Queue\Message\MarketingSmsSubscribeData;
use Dotdigitalgroup\Sms\Test\Unit\Wrappers\V3ClientWrapper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class MarketingSmsSubscribeTest extends TestCase
{
    /**
     * @var MarketingSmsSubscribe
     */
    private $marketingSmsSubscribe;

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
     * @var MarketingSmsSubscribeData|MockObject
     */
    private $smsSubscribeDataMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->contactFactoryMock = $this->createMock(ContactFactory::class);
        $this->v3ClientFactoryMock = $this->createMock(V3ClientFactory::class);
        $this->smsConfigMock = $this->createMock(Configuration::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->retrieverFactoryMock = $this->createMock(RetrieverFactory::class);
        $this->smsSubscribeDataMock = $this->createMock(MarketingSmsSubscribeData::class);
        $this->exporterFactoryMock = $this->createMock(ExporterFactory::class);

        $this->marketingSmsSubscribe = new MarketingSmsSubscribe(
            $this->loggerMock,
            $this->contactFactoryMock,
            $this->v3ClientFactoryMock,
            $this->smsConfigMock,
            $this->exporterFactoryMock,
            $this->scopeConfigMock,
            $this->retrieverFactoryMock
        );
    }

    public function testProcessSuccessfullySubscribesContactToMarketingSms(): void
    {
        $this->smsSubscribeDataMock->expects($this->exactly(4))
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->smsSubscribeDataMock->expects($this->exactly(2))
            ->method('getContactId')
            ->willReturn(1);

        $v3ClientWrapperMock = $this->createMock(V3ClientWrapper::class);
        $v3ClientWrapperMock->expects($this->any())
            ->method('patchByIdentifier')
            ->willReturnSelf();

        $this->v3ClientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['websiteId' => 1])
            ->willReturn($v3ClientWrapperMock);

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

        $this->marketingSmsSubscribe->process($this->smsSubscribeDataMock);
    }
}
