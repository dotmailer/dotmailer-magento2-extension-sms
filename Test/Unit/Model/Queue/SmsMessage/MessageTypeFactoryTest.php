<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\SmsMessage;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\MessageTypeFactory;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageTypeInterface;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewOrder;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MessageTypeFactoryTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $objectManagerMock;

    /**
     * @var MessageTypeFactory
     */
    private $messageTypeFactory;

    protected function setUp(): void
    {
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->messageTypeFactory = new MessageTypeFactory($this->objectManagerMock);
    }

    public function testCreateReturnsMessageTypeInstance(): void
    {
        $typeId = ConfigInterface::SMS_TYPE_NEW_ORDER;
        $args = ['order' => 'test_order'];
        $expectedClass = NewOrder::class;

        $messageTypeMock = $this->createMock(SmsMessageTypeInterface::class);

        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with($expectedClass, $args)
            ->willReturn($messageTypeMock);

        $result = $this->messageTypeFactory->create($typeId, $args);

        $this->assertInstanceOf(SmsMessageTypeInterface::class, $result);
    }

    public function testCreateThrowsExceptionForInvalidTypeId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown message type ID: 999');

        $this->messageTypeFactory->create(999);
    }

    public function testGetEnabledConfigPathReturnsCorrectPath(): void
    {
        $typeId = ConfigInterface::SMS_TYPE_NEW_ORDER;
        $expectedPath = ConfigInterface::XML_PATH_SMS_NEW_ORDER_ENABLED;

        $result = $this->messageTypeFactory->getEnabledConfigPath($typeId);

        $this->assertEquals($expectedPath, $result);
    }

    public function testGetEnabledConfigPathThrowsExceptionForInvalidTypeId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown message type ID: 999');

        $this->messageTypeFactory->getEnabledConfigPath(999);
    }

    public function testGetMessageConfigPathReturnsCorrectPath(): void
    {
        $typeId = ConfigInterface::SMS_TYPE_NEW_ORDER;
        $expectedPath = ConfigInterface::XML_PATH_SMS_NEW_ORDER_MESSAGE;

        $result = $this->messageTypeFactory->getMessageConfigPath($typeId);

        $this->assertEquals($expectedPath, $result);
    }

    public function testGetMessageConfigPathThrowsExceptionForInvalidTypeId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown message type ID: 999');

        $this->messageTypeFactory->getMessageConfigPath(999);
    }

    /**
     * @dataProvider messageTypeDataProvider
     */
    public function testCreateHandlesAllMessageTypes(int $typeId, string $expectedClass): void
    {
        $messageTypeMock = $this->createMock(SmsMessageTypeInterface::class);

        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with($expectedClass, [])
            ->willReturn($messageTypeMock);

        $result = $this->messageTypeFactory->create($typeId);

        $this->assertInstanceOf(SmsMessageTypeInterface::class, $result);
    }

    public function messageTypeDataProvider(): array
    {
        return [
            'new_order' => [
                ConfigInterface::SMS_TYPE_NEW_ORDER,
                \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewOrder::class
            ],
            'update_order' => [
                ConfigInterface::SMS_TYPE_UPDATE_ORDER,
                \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\UpdateOrder::class
            ],
            'new_shipment' => [
                ConfigInterface::SMS_TYPE_NEW_SHIPMENT,
                \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewShipment::class
            ],
            'update_shipment' => [
                ConfigInterface::SMS_TYPE_UPDATE_SHIPMENT,
                \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\UpdateShipment::class
            ],
            'new_credit_memo' => [
                ConfigInterface::SMS_TYPE_NEW_CREDIT_MEMO,
                \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewCreditMemo::class
            ],
            'sms_signup' => [
                ConfigInterface::SMS_TYPE_SIGN_UP,
                \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\SmsSignup::class
            ],
            'new_account_signup' => [
                ConfigInterface::SMS_TYPE_NEW_ACCOUNT_SIGN_UP,
                \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewAccountSignup::class
            ],
        ];
    }
}
