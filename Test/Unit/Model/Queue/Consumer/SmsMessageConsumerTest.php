<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\Consumer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection;
use Dotdigitalgroup\Email\Model\Order;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterfaceFactory;
use Dotdigitalgroup\Sms\Api\SmsMessageRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Apiconnector\SmsClientFactory;
use Dotdigitalgroup\Sms\Model\Message\MessageBuilder;
use Dotdigitalgroup\Sms\Model\Queue\Consumer\SmsMessageConsumer;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsMessageData;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessageQueueManager;
use Magento\Framework\Stdlib\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SmsMessageConsumerTest extends TestCase
{
    /**
     * @var SmsClientFactory|MockObject
     */
    private $smsClientFactoryMock;

    /**
     * @var MessageBuilder|MockObject
     */
    private $messageBuilderMock;

    /**
     * @var SmsMessageInterfaceFactory|MockObject
     */
    private $smsMessageInterfaceFactoryMock;

    /**
     * @var SmsMessageRepositoryInterface|MockObject
     */
    private $smsMessageRepositoryMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var DateTime|MockObject
     */
    private $dateTimeMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var SmsMessageConsumer
     */
    private $smsMessageConsumer;

    protected function setUp(): void
    {
        $this->smsClientFactoryMock = $this->createMock(SmsClientFactory::class);
        $this->messageBuilderMock = $this->createMock(MessageBuilder::class);
        $this->smsMessageInterfaceFactoryMock = $this->createMock(SmsMessageInterfaceFactory::class);
        $this->smsMessageRepositoryMock = $this->createMock(SmsMessageRepositoryInterface::class);
        $this->orderCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->smsMessageConsumer = new SmsMessageConsumer(
            $this->smsClientFactoryMock,
            $this->messageBuilderMock,
            $this->smsMessageInterfaceFactoryMock,
            $this->smsMessageRepositoryMock,
            $this->orderCollectionFactoryMock,
            $this->dateTimeMock,
            $this->loggerMock
        );
    }

    public function testProcessSuccessfullyHandlesDeliveredMessage(): void
    {
        $messageData = $this->createMessageData();
        $message = $this->createMessageMock();
        $client = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['sendSmsSingle'])
            ->getMock();
        $messagePayload = ['body' => 'Test SMS message'];

        $response = (object) [
            'messageId' => 'msg-123',
            'status' => 'delivered',
            'sentOn' => '2024-01-01 12:00:00',
            'statusDetails' => (object) [
                'channelStatus' => (object) [
                    'statusdescription' => 'Message delivered'
                ]
            ]
        ];

        $this->setupMessageCreation($messageData, $message);
        $this->setupClientCreation($message, $client);
        $this->setupOrderCollection($messageData->getOrderId(), true, true);
        $this->setupMessageBuilding($message, $messagePayload, true);

        $client->expects($this->once())
            ->method('sendSmsSingle')
            ->with($messagePayload)
            ->willReturn($response);

        $this->dateTimeMock->expects($this->once())
            ->method('formatDate')
            ->with('2024-01-01 12:00:00')
            ->willReturn('2024-01-01 12:00:00');

        $message->expects($this->once())
            ->method('setMessageId')
            ->with('msg-123')
            ->willReturnSelf();

        $message->expects($this->once())
            ->method('setContent')
            ->with('Test SMS message')
            ->willReturnSelf();

        $message->expects($this->once())
            ->method('setStatus')
            ->with(SmsMessageQueueManager::SMS_STATUS_DELIVERED)
            ->willReturnSelf();

        $message->expects($this->once())
            ->method('setMessage')
            ->with('Message delivered')
            ->willReturnSelf();

        $message->expects($this->once())
            ->method('setSentAt')
            ->with('2024-01-01 12:00:00')
            ->willReturnSelf();

        $this->smsMessageRepositoryMock->expects($this->once())
            ->method('save')
            ->with($message);

        $this->smsMessageConsumer->process($messageData);
    }

    public function testProcessHandlesFailedMessage(): void
    {
        $messageData = $this->createMessageData();
        $message = $this->createMessageMock();
        $client = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['sendSmsSingle'])
            ->getMock();
        $messagePayload = ['body' => 'Test SMS message'];

        $response = (object) [
            'messageId' => 'msg-456',
            'status' => 'failed',
            'statusDetails' => (object) [
                'reason' => 'Invalid phone number'
            ]
        ];

        $this->setupMessageCreation($messageData, $message);
        $this->setupClientCreation($message, $client);
        $this->setupOrderCollection($messageData->getOrderId(), true, true);
        $this->setupMessageBuilding($message, $messagePayload, true);

        $client->expects($this->once())
            ->method('sendSmsSingle')
            ->with($messagePayload)
            ->willReturn($response);

        $message->expects($this->once())
            ->method('setStatus')
            ->with(SmsMessageQueueManager::SMS_STATUS_FAILED)
            ->willReturnSelf();

        $message->expects($this->once())
            ->method('setMessage')
            ->with('Invalid phone number')
            ->willReturnSelf();

        $this->smsMessageRepositoryMock->expects($this->once())
            ->method('save')
            ->with($message);

        $this->smsMessageConsumer->process($messageData);
    }

    public function testProcessHandlesInProgressStatusWhenNoStatusInResponse(): void
    {
        $messageData = $this->createMessageData();
        $message = $this->createMessageMock();
        $client = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['sendSmsSingle'])
            ->getMock();
        $messagePayload = ['body' => 'Test SMS message'];

        $response = (object) [
            'messageId' => 'msg-789'
        ];

        $this->setupMessageCreation($messageData, $message);
        $this->setupClientCreation($message, $client);
        $this->setupOrderCollection($messageData->getOrderId(), true, true);
        $this->setupMessageBuilding($message, $messagePayload, true);

        $client->expects($this->once())
            ->method('sendSmsSingle')
            ->with($messagePayload)
            ->willReturn($response);

        $message->expects($this->once())
            ->method('setStatus')
            ->with(SmsMessageQueueManager::SMS_STATUS_IN_PROGRESS)
            ->willReturnSelf();

        $this->smsMessageRepositoryMock->expects($this->once())
            ->method('save')
            ->with($message);

        $this->smsMessageConsumer->process($messageData);
    }

    public function testProcessHandlesUnknownStatusWhenNoMessageId(): void
    {
        $messageData = $this->createMessageData();
        $message = $this->createMessageMock();
        $client = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['sendSmsSingle'])
            ->getMock();
        $messagePayload = ['body' => 'Test SMS message'];

        $response = (object) [];

        $this->setupMessageCreation($messageData, $message);
        $this->setupClientCreation($message, $client);
        $this->setupOrderCollection($messageData->getOrderId(), true, true);
        $this->setupMessageBuilding($message, $messagePayload, true);

        $client->expects($this->once())
            ->method('sendSmsSingle')
            ->with($messagePayload)
            ->willReturn($response);

        $message->expects($this->once())
            ->method('setStatus')
            ->with(SmsMessageQueueManager::SMS_STATUS_UNKNOWN)
            ->willReturnSelf();

        $this->smsMessageRepositoryMock->expects($this->once())
            ->method('save')
            ->with($message);

        $this->smsMessageConsumer->process($messageData);
    }

    public function testProcessLogsErrorWhenClientCreationFails(): void
    {
        $messageData = $this->createMessageData();
        $message = $this->createMessageMock();

        $this->setupMessageCreation($messageData, $message);

        $message->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->smsClientFactoryMock->expects($this->once())
            ->method('create')
            ->with(1)
            ->willReturn(null);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Failed to create SMS client',
                ['website_id' => 1]
            );

        $this->smsMessageRepositoryMock->expects($this->never())
            ->method('save');

        $this->smsMessageConsumer->process($messageData);
    }

    public function testProcessSkipsWhenOptInRequiredButNotProvided(): void
    {
        $messageData = $this->createMessageData();
        $message = $this->createMessageMock();
        $client = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['sendSmsSingle'])
            ->getMock();

        $this->setupMessageCreation($messageData, $message);
        $this->setupClientCreation($message, $client);
        $this->setupOrderCollection($messageData->getOrderId(), true, false);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'Transactional SMS send skipped - opt-in was required but was not provided',
                $this->callback(function ($context) {
                    return isset($context['website_id']) && isset($context['order_id']);
                })
            );

        $this->messageBuilderMock->expects($this->never())
            ->method('buildMessage');

        $client->expects($this->never())
            ->method('sendSmsSingle');

        $this->smsMessageRepositoryMock->expects($this->never())
            ->method('save');

        $this->smsMessageConsumer->process($messageData);
    }

    public function testProcessThrowsAndLogsErrorOnException(): void
    {
        $messageData = $this->createMessageData();
        $exception = new \Exception('Test exception');

        $this->smsMessageInterfaceFactoryMock->expects($this->once())
            ->method('create')
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Failed to send SMS',
                $this->callback(function ($context) {
                    return isset($context['message'])
                        && $context['message'] === 'Test exception'
                        && isset($context['file'])
                        && isset($context['line'])
                        && isset($context['trace']);
                })
            );

        $this->smsMessageRepositoryMock->expects($this->never())
            ->method('save');

        $this->smsMessageConsumer->process($messageData);
    }

    /**
     * Create test message data.
     *
     * @return SmsMessageData
     */
    private function createMessageData(): SmsMessageData
    {
        $messageData = new SmsMessageData();
        $messageData->setWebsiteId(1)
            ->setStoreId(1)
            ->setTypeId(1)
            ->setOrderId(100)
            ->setPhoneNumber('+1234567890')
            ->setEmail('test@example.com')
            ->setAdditionalData('{"order_status":"pending"}');

        return $messageData;
    }

    /**
     * Create message mock.
     *
     * @return SmsMessageInterface|MockObject
     */
    private function createMessageMock(): MockObject
    {
        $message = $this->createMock(SmsMessageInterface::class);
        $message->method('setWebsiteId')->willReturnSelf();
        $message->method('setStoreId')->willReturnSelf();
        $message->method('setTypeId')->willReturnSelf();
        $message->method('setOrderId')->willReturnSelf();
        $message->method('setPhoneNumber')->willReturnSelf();
        $message->method('setEmail')->willReturnSelf();
        $message->method('setAdditionalData')->willReturnSelf();
        $message->method('setMessageId')->willReturnSelf();
        $message->method('setContent')->willReturnSelf();
        $message->method('setStatus')->willReturnSelf();
        $message->method('setMessage')->willReturnSelf();
        $message->method('setSentAt')->willReturnSelf();

        return $message;
    }

    /**
     * Setup message creation.
     *
     * @param SmsMessageData $messageData
     * @param MockObject $message
     * @return void
     */
    private function setupMessageCreation(SmsMessageData $messageData, MockObject $message): void
    {
        $this->smsMessageInterfaceFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($message);

        $message->expects($this->once())
            ->method('setWebsiteId')
            ->with($messageData->getWebsiteId())
            ->willReturnSelf();

        $message->expects($this->once())
            ->method('setStoreId')
            ->with($messageData->getStoreId())
            ->willReturnSelf();

        $message->expects($this->any())
            ->method('getOrderId')
            ->willReturn($messageData->getOrderId());
    }

    /**
     * Setup client creation.
     *
     * @param MockObject $message
     * @param MockObject $client
     * @return void
     */
    private function setupClientCreation(MockObject $message, MockObject $client): void
    {
        $message->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->smsClientFactoryMock->expects($this->once())
            ->method('create')
            ->with(1)
            ->willReturn($client);
    }

    /**
     * Setup order collection with opt-in status.
     *
     * @param int $orderId
     * @param bool $requiresOptIn Whether the order requires opt-in
     * @param bool $hasOptIn Whether the customer has opted in for SMS
     * @return void
     */
    private function setupOrderCollection(int $orderId, bool $requiresOptIn, bool $hasOptIn): void
    {
        $orderCollectionMock = $this->createMock(Collection::class);
        $orderItem = $this->createMock(Order::class);

        $orderItem->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);

        $orderItem->expects($this->any())
            ->method('getData')
            ->willReturnCallback(function ($key) use ($requiresOptIn, $hasOptIn) {
                if ($key === 'sms_transactional_requires_opt_in') {
                    return $requiresOptIn;
                }
                if ($key === 'sms_transactional_opt_in') {
                    return $hasOptIn;
                }
                return null;
            });

        $this->orderCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($orderCollectionMock);

        $orderCollectionMock->expects($this->once())
            ->method('getOrdersFromIds')
            ->with([$orderId])
            ->willReturnSelf();

        $orderCollectionMock->expects($this->once())
            ->method('setPageSize')
            ->with(1)
            ->willReturnSelf();

        $orderCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($orderItem);
    }

    /**
     * Setup message building with opt-in flag.
     *
     * @param MockObject $message
     * @param array $messagePayload
     * @param bool $optIn Whether opt-in is required and customer has opted in
     * @return void
     */
    private function setupMessageBuilding(MockObject $message, array $messagePayload, bool $optIn = false): void
    {
        $this->messageBuilderMock->expects($this->once())
            ->method('buildMessage')
            ->with($message, $optIn)
            ->willReturn($messagePayload);
    }
}
