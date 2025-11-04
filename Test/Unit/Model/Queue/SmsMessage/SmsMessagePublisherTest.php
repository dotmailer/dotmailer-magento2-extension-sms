<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\SmsMessage;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageTypeInterface;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsMessageData;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\MessageTypeFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SmsMessagePublisherTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Configuration|MockObject
     */
    private $moduleConfigMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var MessageTypeFactory|MockObject
     */
    private $messageTypeFactoryMock;

    /**
     * @var PublisherInterface|MockObject
     */
    private $publisherMock;

    /**
     * @var SmsMessagePublisher
     */
    private $smsMessagePublisher;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->moduleConfigMock = $this->createMock(Configuration::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->messageTypeFactoryMock = $this->createMock(MessageTypeFactory::class);
        $this->publisherMock = $this->createMock(PublisherInterface::class);

        $this->smsMessagePublisher = new SmsMessagePublisher(
            $this->loggerMock,
            $this->moduleConfigMock,
            $this->serializerMock,
            $this->messageTypeFactoryMock,
            $this->publisherMock
        );
    }

    public function testPublishSuccessfullyQueuesMessage(): void
    {
        $typeId = ConfigInterface::SMS_TYPE_NEW_ORDER;
        $storeId = 1;
        $websiteId = 1;
        $orderId = 100;
        $phoneNumber = '+1234567890';
        $email = 'test@example.com';
        $additionalData = ['order_status' => 'pending'];
        $serializedData = '{"order_status":"pending"}';
        $enabledPath = ConfigInterface::XML_PATH_SMS_NEW_ORDER_ENABLED;

        $messageTypeMock = $this->createMock(SmsMessageTypeInterface::class);
        $messageTypeMock->method('getStoreId')->willReturn($storeId);
        $messageTypeMock->method('getWebsiteId')->willReturn($websiteId);
        $messageTypeMock->method('getOrderId')->willReturn($orderId);
        $messageTypeMock->method('getPhoneNumber')->willReturn($phoneNumber);
        $messageTypeMock->method('getEmail')->willReturn($email);
        $messageTypeMock->method('getAdditionalData')->willReturn($additionalData);

        $this->messageTypeFactoryMock->expects($this->once())
            ->method('create')
            ->with($typeId, [])
            ->willReturn($messageTypeMock);

        $this->messageTypeFactoryMock->expects($this->once())
            ->method('getEnabledConfigPath')
            ->with($typeId)
            ->willReturn($enabledPath);

        $this->moduleConfigMock->expects($this->once())
            ->method('isTransactionalSmsEnabled')
            ->with($storeId)
            ->willReturn(true);

        $this->moduleConfigMock->expects($this->once())
            ->method('isSmsTypeEnabled')
            ->with($storeId, $enabledPath)
            ->willReturn(true);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($additionalData)
            ->willReturn($serializedData);

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with(
                SmsMessagePublisher::TOPIC_SMS_MESSAGE,
                $this->callback(function (SmsMessageData $messageData) use (
                    $websiteId,
                    $storeId,
                    $typeId,
                    $orderId,
                    $phoneNumber,
                    $email,
                    $serializedData
                ) {
                    return $messageData->getWebsiteId() === $websiteId
                        && $messageData->getStoreId() === $storeId
                        && $messageData->getTypeId() === $typeId
                        && $messageData->getOrderId() === $orderId
                        && $messageData->getPhoneNumber() === $phoneNumber
                        && $messageData->getEmail() === $email
                        && $messageData->getAdditionalData() === $serializedData;
                })
            );

        $result = $this->smsMessagePublisher->publish($typeId);

        $this->assertTrue($result);
    }

    public function testPublishReturnsFalseWhenTransactionalSmsDisabled(): void
    {
        $typeId = ConfigInterface::SMS_TYPE_NEW_ORDER;
        $storeId = 1;
        $enabledPath = ConfigInterface::XML_PATH_SMS_NEW_ORDER_ENABLED;

        $messageTypeMock = $this->createMock(SmsMessageTypeInterface::class);
        $messageTypeMock->method('getStoreId')->willReturn($storeId);

        $this->messageTypeFactoryMock->expects($this->once())
            ->method('create')
            ->with($typeId, [])
            ->willReturn($messageTypeMock);

        $this->messageTypeFactoryMock->expects($this->once())
            ->method('getEnabledConfigPath')
            ->with($typeId)
            ->willReturn($enabledPath);

        $this->moduleConfigMock->expects($this->once())
            ->method('isTransactionalSmsEnabled')
            ->with($storeId)
            ->willReturn(false);

        $this->publisherMock->expects($this->never())
            ->method('publish');

        $result = $this->smsMessagePublisher->publish($typeId);

        $this->assertFalse($result);
    }

    public function testPublishReturnsFalseWhenSmsTypeDisabled(): void
    {
        $typeId = ConfigInterface::SMS_TYPE_NEW_ORDER;
        $storeId = 1;
        $enabledPath = ConfigInterface::XML_PATH_SMS_NEW_ORDER_ENABLED;

        $messageTypeMock = $this->createMock(SmsMessageTypeInterface::class);
        $messageTypeMock->method('getStoreId')->willReturn($storeId);

        $this->messageTypeFactoryMock->expects($this->once())
            ->method('create')
            ->with($typeId, [])
            ->willReturn($messageTypeMock);

        $this->messageTypeFactoryMock->expects($this->once())
            ->method('getEnabledConfigPath')
            ->with($typeId)
            ->willReturn($enabledPath);

        $this->moduleConfigMock->expects($this->once())
            ->method('isTransactionalSmsEnabled')
            ->with($storeId)
            ->willReturn(true);

        $this->moduleConfigMock->expects($this->once())
            ->method('isSmsTypeEnabled')
            ->with($storeId, $enabledPath)
            ->willReturn(false);

        $this->publisherMock->expects($this->never())
            ->method('publish');

        $result = $this->smsMessagePublisher->publish($typeId);

        $this->assertFalse($result);
    }

    public function testPublishHandlesSerializationException(): void
    {
        $typeId = ConfigInterface::SMS_TYPE_NEW_ORDER;
        $storeId = 1;
        $websiteId = 1;
        $orderId = 100;
        $additionalData = ['key' => 'value'];
        $enabledPath = ConfigInterface::XML_PATH_SMS_NEW_ORDER_ENABLED;

        $messageTypeMock = $this->createMock(SmsMessageTypeInterface::class);
        $messageTypeMock->method('getStoreId')->willReturn($storeId);
        $messageTypeMock->method('getWebsiteId')->willReturn($websiteId);
        $messageTypeMock->method('getOrderId')->willReturn($orderId);
        $messageTypeMock->method('getPhoneNumber')->willReturn('+1234567890');
        $messageTypeMock->method('getEmail')->willReturn('test@example.com');
        $messageTypeMock->method('getAdditionalData')->willReturn($additionalData);

        $this->messageTypeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($messageTypeMock);

        $this->messageTypeFactoryMock->expects($this->once())
            ->method('getEnabledConfigPath')
            ->willReturn($enabledPath);

        $this->moduleConfigMock->expects($this->once())
            ->method('isTransactionalSmsEnabled')
            ->willReturn(true);

        $this->moduleConfigMock->expects($this->once())
            ->method('isSmsTypeEnabled')
            ->willReturn(true);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->willThrowException(new \InvalidArgumentException('Serialization failed'));

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Serialization failed'));

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with(
                SmsMessagePublisher::TOPIC_SMS_MESSAGE,
                $this->callback(function (SmsMessageData $messageData) {
                    return $messageData->getAdditionalData() === '';
                })
            );

        $result = $this->smsMessagePublisher->publish($typeId);

        $this->assertTrue($result);
    }

    public function testPublishLogsErrorAndReturnsFalseOnException(): void
    {
        $typeId = ConfigInterface::SMS_TYPE_NEW_ORDER;
        $exception = new \Exception('Test exception');

        $this->messageTypeFactoryMock->expects($this->once())
            ->method('create')
            ->with($typeId, [])
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                sprintf('Error publishing SMS message type: %d', $typeId),
                [(string) $exception]
            );

        $this->publisherMock->expects($this->never())
            ->method('publish');

        $result = $this->smsMessagePublisher->publish($typeId);

        $this->assertFalse($result);
    }

    public function testPublishAcceptsDataParameter(): void
    {
        $typeId = ConfigInterface::SMS_TYPE_NEW_ORDER;
        $storeId = 1;
        $orderId = 150;
        $data = ['order' => 'test_order'];
        $enabledPath = ConfigInterface::XML_PATH_SMS_NEW_ORDER_ENABLED;

        $messageTypeMock = $this->createMock(SmsMessageTypeInterface::class);
        $messageTypeMock->method('getStoreId')->willReturn($storeId);
        $messageTypeMock->method('getWebsiteId')->willReturn(1);
        $messageTypeMock->method('getOrderId')->willReturn($orderId);
        $messageTypeMock->method('getPhoneNumber')->willReturn('+1234567890');
        $messageTypeMock->method('getEmail')->willReturn('test@example.com');
        $messageTypeMock->method('getAdditionalData')->willReturn([]);

        $this->messageTypeFactoryMock->expects($this->once())
            ->method('create')
            ->with($typeId, $data)
            ->willReturn($messageTypeMock);

        $this->messageTypeFactoryMock->method('getEnabledConfigPath')
            ->willReturn($enabledPath);

        $this->moduleConfigMock->method('isTransactionalSmsEnabled')->willReturn(true);
        $this->moduleConfigMock->method('isSmsTypeEnabled')->willReturn(true);
        $this->serializerMock->method('serialize')->willReturn('');

        $result = $this->smsMessagePublisher->publish($typeId, $data);

        $this->assertTrue($result);
    }
}
