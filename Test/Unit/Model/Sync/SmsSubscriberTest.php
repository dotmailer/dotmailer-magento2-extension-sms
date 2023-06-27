<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Sync;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Exporter;
use Dotdigital\V3\Models\ContactCollection;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Sync\Batch\SmsSubscriberBatchProcessor;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\ExporterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Dotdigitalgroup\Sms\Test\Unit\Traits\TestInteractsWithV3ApiModels;

class SmsSubscriberTest extends TestCase
{
    use TestInteractsWithV3ApiModels;

    public const LIMIT = 2000;

    public const BATCH_SIZE = 500;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var SmsSubscriber
     */
    private $smsSubscriber;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Data|MockObject
     */
    private $emailHelperMock;

    /**
     * @var Configuration|MockObject
     */
    private $smsConfigMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceMock;

    /**
     * @var ContactCollectionFactory|MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var ExporterFactory|MockObject
     */
    private $exporterFactoryMock;

    /**
     * @var SmsSubscriberBatchProcessor|MockObject
     */
    private $batchProcessorMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Exporter|MockObject
     */
    private $smsExporter;

    /**
     * @var Collection|MockObject
     */
    private $subscriberCollectionMock;

    /**
     * @var WebsiteInterface|MockObject
     */
    private $websiteInterfaceMock;

    /**
     * @var Select|(Select&MockObject)|MockObject
     */
    private $selectMock;

    /**
     * @var (ContactCollection&MockObject)|MockObject $contactCollectionMock
     */
    private $contactCollectionMock;
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->loggerMock = $this->createMock(Logger::class);
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->exporterFactoryMock = $this->createMock(ExporterFactory::class);
        $this->batchProcessorMock = $this->createMock(SmsSubscriberBatchProcessor::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->selectMock = $this->createMock(Select::class);
        $this->emailHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactCollectionMock = $this->getMockBuilder(ContactCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->smsConfigMock = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriberCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactCollectionFactoryMock = $this->createMock(CollectionFactory::class);

        $this->smsExporter = $this->getMockBuilder(Exporter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteInterfaceMock = $this->getMockBuilder(WebsiteInterface::class)
            ->onlyMethods([
                'getId',
                'setId',
                'getCode',
                'setCode',
                'getName',
                'setName',
                'getDefaultGroupId',
                'setDefaultGroupId',
                'getExtensionAttributes',
                'setExtensionAttributes'
            ])
            ->addMethods(['getStoreIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->smsSubscriber = new SmsSubscriber(
            $this->loggerMock,
            $this->emailHelperMock,
            $this->smsConfigMock,
            $this->contactCollectionFactoryMock,
            $this->exporterFactoryMock,
            $this->resourceMock,
            $this->batchProcessorMock,
            $this->scopeConfigMock
        );
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function testSync()
    {

        $total_loops = self::LIMIT / self::BATCH_SIZE;

        $batch = $this->generateBulkImportSmsContacts(self::BATCH_SIZE);

        $this->emailHelperMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([
                $this->websiteInterfaceMock
            ]);

        $this->websiteInterfaceMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);

        $this->scopeConfigMock->expects($this->atLeast(3))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls(
                self::LIMIT,
                self::BATCH_SIZE,
                [
                        "TEST_FN",
                        "TEST_LN",
                        "WEBSITE_NAME",
                        "STORE_NAME",
                        "STORE_CODE",
                        "STORE_ID",
                        "STORE_WEBSITE_ID",
                        "STORE_GROUP_ID",
                        "STORE_GROUP_NAME",
                        "0",
                    ]
            );

        $this->emailHelperMock->expects($this->once())
            ->method('isEnabled')
            ->with($this->websiteInterfaceMock->getId())
            ->willReturn(true);

        $this->smsConfigMock->expects($this->once())
            ->method('isSmsSyncEnabled')
            ->with($this->websiteInterfaceMock->getId())
            ->willReturn(true);

        $this->smsConfigMock->expects($this->once())
            ->method('getListId')
            ->with($this->websiteInterfaceMock->getId())
            ->willReturn(123);

        $this->contactCollectionFactoryMock->expects($this->atLeast(1))
            ->method('create')
            ->willReturn($this->subscriberCollectionMock);

        $this->subscriberCollectionMock->expects($this->atLeast($total_loops))
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->subscriberCollectionMock->expects($this->atLeast($total_loops))
            ->method('getSelect')
            ->willReturn($this->selectMock);

        $this->selectMock->expects($this->atLeast(3*$total_loops))
            ->method('joinLeft')
            ->willReturnSelf();

        $this->resourceMock->expects($this->atLeast(3*$total_loops))
            ->method('getTableName')
            ->willReturnOnConsecutiveCalls(
                'customer_entity',
                'store_website',
                'store',
                'store_group',
                'customer_entity',
                'store_website',
                'store',
                'store_group',
                'customer_entity',
                'store_website',
                'store',
                'store_group',
                'customer_entity',
                'store_website',
                'store',
                'store_group',
            );

        $this->subscriberCollectionMock->expects($this->atLeast($total_loops*2))
            ->method('getItems')
            ->willReturn($batch);

        $this->exporterFactoryMock->expects($this->atLeast($total_loops))
            ->method('create')
            ->willReturn($this->smsExporter);

        $this->smsExporter->expects($this->atLeast($total_loops))
            ->method('setFieldMapping')
            ->willReturnSelf();

        $this->smsExporter->expects($this->atLeast($total_loops))
            ->method('export')
            ->willReturn($batch);

        $this->batchProcessorMock->expects($this->atLeast($total_loops))
            ->method('process')
            ->willReturn(true);

        $this->smsSubscriber->sync();
    }
}
