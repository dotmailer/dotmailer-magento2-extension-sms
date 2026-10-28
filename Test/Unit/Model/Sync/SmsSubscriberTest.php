<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Sync;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Exporter;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\SmsContact;
use Dotdigitalgroup\Sms\Model\Sync\Batch\SmsSubscriberBatchProcessor;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\ExporterFactory;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Retriever;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\RetrieverFactory;
use Dotdigitalgroup\Email\Test\Unit\Traits\MockBuilderCompatibilityTrait;
use Dotdigitalgroup\Sms\Test\Unit\Traits\TestInteractsWithV3ApiModels;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SmsSubscriberTest extends TestCase
{
    use MockBuilderCompatibilityTrait;
    use TestInteractsWithV3ApiModels;

    public const LIMIT = 2000;

    public const BATCH_SIZE = 500;

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
     * @var (RetrieverFactory&MockObject)|MockObject
     */
    private $retrieverFactoryMock;

    /**
     * @var (Retriever&MockObject)|MockObject
     */
    private $retrieverMock;

    /**
     * @var RegistrationStoreResolver|MockObject
     */
    private $registrationStoreResolver;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->exporterFactoryMock = $this->createMock(ExporterFactory::class);
        $this->batchProcessorMock = $this->createMock(SmsSubscriberBatchProcessor::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->emailHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->retrieverFactoryMock = $this->getMockBuilder(RetrieverFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->smsConfigMock = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registrationStoreResolver = $this->createMock(RegistrationStoreResolver::class);
        $this->registrationStoreResolver->method('getWebsiteStoreIds')->willReturn([1, 2, 3]);

        $this->subscriberCollectionMock = $this->createMock(Collection::class);

        $this->smsExporter = $this->getMockBuilder(Exporter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteInterfaceMock = $this->getMockBuilder(
            $this->classWithAddedMethods(WebsiteInterface::class, ['getStoreIds'])
        )
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
                'setExtensionAttributes',
                'getStoreIds'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->smsSubscriber = new SmsSubscriber(
            $this->loggerMock,
            $this->emailHelperMock,
            $this->smsConfigMock,
            $this->exporterFactoryMock,
            $this->batchProcessorMock,
            $this->scopeConfigMock,
            $this->retrieverFactoryMock,
            $this->registrationStoreResolver
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

        $this->smsConfigMock->expects($this->exactly(3))
            ->method('getListId')
            ->with(1, $this->anything())
            ->willReturn(123);

        $retrieverMock = $this->createMock(Retriever::class);
        $this->retrieverFactoryMock->expects($this->atLeast(1))
            ->method('create')
            ->willReturn($retrieverMock);

        $retrieverMock->expects($this->atLeast($total_loops))
            ->method('getSmsSubscribers')
            ->with($this->anything(), $this->anything(), [1, 2, 3])
            ->willReturn($this->subscriberCollectionMock);

        $retrieverMock->method('setWebsite')
            ->willReturnSelf();

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

    #[DataProvider('websiteBatchScenarios')]
    public function testWebsiteSharesBatchesAcrossListCohorts(
        int $megaBatchSize,
        int $breakValue,
        array $expectedBatches,
        array $expectedCalls,
        int $expectedCount
    ): void {
        $this->websiteInterfaceMock->method('getId')->willReturn(1);
        $this->emailHelperMock->method('getWebsites')->willReturn([$this->websiteInterfaceMock]);
        $this->emailHelperMock->method('isEnabled')->willReturn(true);
        $this->smsConfigMock->method('isSmsSyncEnabled')->willReturn(true);
        $this->smsConfigMock->method('getLimit')->willReturn(100);
        $this->smsConfigMock->method('getListId')->willReturnMap([
            [1, 1, 100],
            [1, 2, 200],
            [1, 3, 100]
        ]);
        $this->scopeConfigMock->method('getValue')->willReturnMap([
            [\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE, 'default', null, $breakValue],
            [
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CONTACT,
                'default',
                null,
                $megaBatchSize
            ],
            ['connector_data_mapping/customer_data', 'websites', 1, []]
        ]);

        $retriever = $this->createMock(Retriever::class);
        $retriever->method('setWebsite')->with(1)->willReturnSelf();
        $this->retrieverFactoryMock->method('create')->willReturn($retriever);
        $calls = [];
        $retriever->expects($this->exactly(array_sum($expectedCalls)))->method('getSmsSubscribers')
            ->willReturnCallback(function ($limit, $offset, $storeIds) use (&$calls) {
                $key = implode(',', $storeIds);
                $calls[$key] = ($calls[$key] ?? 0) + 1;
                $items = [];
                if ($calls[$key] === 1) {
                    foreach ($storeIds as $storeId) {
                        $contact = $this->getMockBuilder(
                            $this->classWithAddedMethods(SmsContact::class, ['getEmail', 'getEmailContactId'])
                        )->disableOriginalConstructor()
                            ->onlyMethods(['getEmail', 'getEmailContactId', 'getMobileNumber'])
                            ->getMock();
                        $contact->setData('sms_sync_store_id', $storeId);
                        $contact->method('getEmail')->willReturn("store$storeId@example.com");
                        $contact->method('getEmailContactId')->willReturn($storeId);
                        $contact->method('getMobileNumber')->willReturn('447123456789');
                        $items[] = $contact;
                    }
                }
                $collection = $this->createMock(Collection::class);
                $collection->method('getItems')->willReturn($items);
                $collection->method('getIterator')->willReturn(new \ArrayIterator($items));
                return $collection;
            });
        $this->exporterFactoryMock->method('create')->willReturn(new Exporter($this->smsConfigMock));
        $batches = [];
        $this->batchProcessorMock->expects($this->exactly(count($expectedBatches)))->method('process')
            ->willReturnCallback(function ($batch, $websiteId) use (&$batches) {
                $this->assertSame(1, $websiteId);
                $batches[] = array_map(function ($contact) {
                    return $contact->getLists();
                }, $batch);
            });

        $result = $this->smsSubscriber->sync();

        $this->assertSame($expectedCalls, $calls);
        $this->assertSame($expectedBatches, $batches);
        $this->assertSame($expectedCount, $result['syncedSubscribers']);
    }

    public static function websiteBatchScenarios(): array
    {
        $mixedListBatch = [1 => [100], 3 => [100], 2 => [200]];
        return [
            'different lists share the final website batch' => [
                1000, 0, [$mixedListBatch], ['1,3' => 2, '2' => 2], 3
            ],
            'size threshold flush includes different lists' => [
                3, 0, [$mixedListBatch], ['1,3' => 2, '2' => 2], 3
            ],
            'threshold flush followed by website remainder' => [
                2, 0, [[1 => [100], 3 => [100]], [2 => [200]]], ['1,3' => 2, '2' => 2], 3
            ],
            'break limit flushes pending contacts without querying next cohort' => [
                1000, 2, [[1 => [100], 3 => [100]]], ['1,3' => 1], 2
            ]
        ];
    }
}
