<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\Consumer;

use Dotdigital\V3\Models\Contact as ContactModel;
use Dotdigital\V3\Models\ContactFactory;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client as V2Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory as V3ClientFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client as V3Client;
use Dotdigitalgroup\Email\Test\Unit\Traits\MockBuilderCompatibilityTrait;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Consumer\SmsSubscriptionConsumer;
use Dotdigitalgroup\Sms\Model\SmsContact;
use Dotdigitalgroup\Sms\Model\SmsSubscriber\StoreResolver;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Exporter;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\ExporterFactory;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\Retriever;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriber\RetrieverFactory;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsSubscriptionData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
class SmsSubscriptionConsumerTest extends TestCase
{
    use MockBuilderCompatibilityTrait;

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

    /**
     * @var SmsContact|MockObject
     */
    private $smsSubscriberMock;

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
        $this->smsSubscriberMock = $this->getMockBuilder(
            $this->classWithAddedMethods(SmsContact::class, ['getEmail'])
        )
            ->disableOriginalConstructor()
            ->onlyMethods(['getEmail'])
            ->getMock();
        $this->smsSubscriberMock->setData('sms_sync_store_id', 2);
        $registrationStoreResolver = $this->createStub(RegistrationStoreResolver::class);
        $registrationStoreResolver->method('getWebsiteStoreIds')->willReturn([1, 2, 3]);

        $this->smsSubscriptionConsumer = new SmsSubscriptionConsumer(
            $this->helperMock,
            $this->loggerMock,
            $this->v3ClientFactoryMock,
            $this->smsConfigMock,
            $this->exporterFactoryMock,
            $this->scopeConfigMock,
            $this->retrieverFactoryMock,
            new StoreResolver($registrationStoreResolver)
        );
    }

    #[DataProvider('messageStores')]
    public function testProcessSuccessfullySubscribesContactToMarketingSms(?int $storeId): void
    {
        $this->smsSubscribeDataMock->method('getType')->willReturn('subscribe');
        $this->smsSubscribeDataMock->method('getStoreId')->willReturn($storeId);

        $v3ClientMock = $this->createMock(V3Client::class);

        $this->v3ClientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => 1]])
            ->willReturn($v3ClientMock);

        $this->smsSubscribeDataMock->expects($this->atLeastOnce())
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
            ->method('patchByIdentifier')
            ->with('chaz@emailsim.io', $this->callback(function (ContactModel $contact) {
                return $contact->getLists() === [200];
            }));

        $retrieverMock = $this->createMock(Retriever::class);
        $retrieverMock->expects($this->once())
            ->method('setWebsite')
            ->with($this->equalTo(1))
            ->willReturnSelf();

        $retrieverMock->expects($this->once())
            ->method('getSmsSubscriber')
            ->willReturn($this->smsSubscriberMock);

        $this->smsSubscriberMock->expects($this->once())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $this->retrieverFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($retrieverMock);

        $exporterMock = $this->createMock(Exporter::class);
        $exporterMock->expects($this->once())
            ->method('setFieldMapping')
            ->willReturnSelf();
        $exporterMock->method('prepareContact')->willReturn(
            new ContactModel(['identifiers' => ['email' => 'chaz@emailsim.io']])
        );
        $this->smsConfigMock->expects($this->once())->method('getListId')
            ->with(1, $storeId ?? 2)->willReturn(200);

        $this->exporterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($exporterMock);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('connector_data_mapping/customer_data', ScopeInterface::SCOPE_WEBSITES, 1)
            ->willReturn(['key1' => 'value1', 'key2' => 'value2']);

        $this->smsSubscriptionConsumer->process($this->smsSubscribeDataMock);
    }

    #[DataProvider('messageStores')]
    public function testProcessUnsubscribesContactFromMarketingSms(?int $storeId): void
    {
        $this->smsSubscribeDataMock->method('getType')->willReturn('unsubscribe');
        $this->smsSubscribeDataMock->method('getStoreId')->willReturn($storeId);

        $v3ClientMock = $this->createMock(V3Client::class);

        $this->v3ClientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => 1]])
            ->willReturn($v3ClientMock);

        $v2ClientMock = $this->createMock(V2Client::class);

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($v2ClientMock);

        $this->smsSubscribeDataMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->smsSubscribeDataMock->expects($this->never())->method('getContactId');
        $this->smsSubscribeDataMock->method('getEmail')->willReturn('contact@example.com');
        $retriever = $this->createMock(Retriever::class);
        $retriever->method('setWebsite')->with(1)->willReturnSelf();
        $retriever->expects($storeId === null ? $this->once() : $this->never())
            ->method('getSmsSubscriberByEmail')->with('contact@example.com')
            ->willReturn($this->smsSubscriberMock);
        $this->retrieverFactoryMock->expects($storeId === null ? $this->once() : $this->never())
            ->method('create')->willReturn($retriever);

        $contactsResourceMock = $this->createMock(\Dotdigital\V3\Resources\Contacts::class);
        $v3ClientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('getByIdentifier')
            ->willReturn(new ContactModel(['contactId' => 42, 'identifiers' => ['email' => 'chaz@emailsim.io']]));

        $v2ClientMock->expects($this->once())
            ->method('deleteAddressBookContact')->with(200, 42);

        $this->smsConfigMock->expects($this->once())
            ->method('getListId')
            ->with(1, $storeId ?? 2)
            ->willReturn(200);

        $this->smsSubscriptionConsumer->process($this->smsSubscribeDataMock);
    }

    #[DataProvider('unroutableSubscriptions')]
    public function testUnroutableSubscriptionsAreLoggedWithoutMutatingRemoteContact(
        string $type,
        bool $hasStore,
        ?int $publishedStoreId = null
    ): void {
        $data = (new SmsSubscriptionData())->setType($type)->setWebsiteId(1)
            ->setContactId(15)->setEmail('contact@example.com')->setStoreId($publishedStoreId);
        if (!$hasStore) {
            $this->smsSubscriberMock->unsetData('sms_sync_store_id');
        }
        $retriever = $this->createMock(Retriever::class);
        $retriever->method('setWebsite')->with(1)->willReturnSelf();
        $retriever->method('getSmsSubscriber')->with(15)->willReturn($this->smsSubscriberMock);
        $retriever->method('getSmsSubscriberByEmail')->with('contact@example.com')
            ->willReturn($this->smsSubscriberMock);
        $this->retrieverFactoryMock->method('create')->willReturn($retriever);
        $this->smsConfigMock->expects($hasStore && $publishedStoreId === null ? $this->once() : $this->never())
            ->method('getListId')->willReturn(0);

        $contacts = $this->createMock(\Dotdigital\V3\Resources\Contacts::class);
        $contacts->method('getByIdentifier')->willReturn(
            new ContactModel(['contactId' => 42, 'identifiers' => ['email' => 'contact@example.com']])
        );
        $contacts->expects($this->never())->method('patchByIdentifier');
        $client = $this->createMock(V3Client::class);
        $client->method('__get')->with('contacts')->willReturn($contacts);
        $this->v3ClientFactoryMock->method('create')->willReturn($client);
        $v2Client = $this->createMock(V2Client::class);
        $v2Client->expects($this->never())->method('deleteAddressBookContact');
        $this->helperMock->method('getWebsiteApiClient')->willReturn($v2Client);
        $this->loggerMock->expects($this->once())->method('error')
            ->with("Contact marketing SMS $type error:", $this->callback(function ($context) {
                return $context['exception'] instanceof \Magento\Framework\Exception\LocalizedException;
            }));

        $this->smsSubscriptionConsumer->process($data);
    }

    public static function unroutableSubscriptions(): array
    {
        return [
            'subscribe without local store' => ['subscribe', false],
            'unsubscribe without local store' => ['unsubscribe', false],
            'subscribe without list' => ['subscribe', true],
            'unsubscribe without list' => ['unsubscribe', true],
            'subscribe with cross-website store' => ['subscribe', true, 9],
            'unsubscribe with cross-website store' => ['unsubscribe', true, 9],
            'subscribe with explicit invalid store' => ['subscribe', true, 0],
            'unsubscribe with explicit invalid store' => ['unsubscribe', true, 0]
        ];
    }

    public static function messageStores(): array
    {
        return [
            'legacy message derives current store' => [null],
            'published store takes priority over current contact store' => [3]
        ];
    }
}
