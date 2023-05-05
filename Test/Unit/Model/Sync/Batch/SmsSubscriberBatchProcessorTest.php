<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Sync\Batch;

use Dotdigital\V3\Resources\Contacts;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact;
use Dotdigitalgroup\Sms\Model\Sync\Batch\SmsSubscriberBatchProcessor;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContactFactory;
use Dotdigital\V3\Models\ContactCollection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Sms\Test\Unit\Traits\TestInteractsWithV3ApiModels;

class SmsSubscriberBatchProcessorTest extends TestCase
{
    use TestInteractsWithV3ApiModels;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var SmsContactFactory|MockObject
     */
    private $smsContactResourceFactoryMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Client|(Client&MockObject)|MockObject
     */
    private $clientMock;

    /**
     * @var ImporterFactory|MockObject
     */
    private $importerFactoryMock;

    /**
     * @var Contacts|(Contacts&MockObject)|MockObject
     */
    private $contactMock;

    /**
     * @var SmsContact|(SmsContact&MockObject)|MockObject
     */
    private $smsContactResourceMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var SmsSubscriberBatchProcessor
     */
    private $smsSubscriberBatchProcessor;

    protected function setUp(): void
    {
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->importerFactoryMock = $this->createMock(ImporterFactory::class);
        $this->smsContactResourceFactoryMock = $this->createMock(SmsContactFactory::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->smsContactResourceMock = $this->createMock(SmsContact::class);
        $this->clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();
        $this->contactMock = $this->createMock(Contacts::class);

        $this->smsSubscriberBatchProcessor = new SmsSubscriberBatchProcessor(
            $this->clientFactoryMock,
            $this->importerFactoryMock,
            $this->smsContactResourceFactoryMock,
            $this->loggerMock,
            $this->serializerMock
        );
    }

    public function testPushBatch(): void
    {
        $batch = $this->generateBulkImportSmsContacts(500);
        $batchEntityIdentifiers = array_keys($batch);
        $contactCollection = new ContactCollection($batch);
        $importId = 123;

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->clientMock);

        $this->clientMock->expects($this->once())
            ->method('__get')
            ->with($this->equalTo('contacts'))
            ->willReturn($this->contactMock);

        $this->contactMock->expects($this->once())
            ->method('import')
            ->with($this->equalTo($contactCollection))
            ->willReturn($importId);

        $this->smsContactResourceFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->smsContactResourceMock);

        $this->smsContactResourceMock->expects($this->once())
            ->method('setSmsContactsImportedByIds')
            ->with($this->equalTo($batchEntityIdentifiers));

        $this->smsSubscriberBatchProcessor->process(
            $batch,
            $this->createMock(WebsiteInterface::class)
        );
    }
}
