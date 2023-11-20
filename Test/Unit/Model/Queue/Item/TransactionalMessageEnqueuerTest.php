<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Test\Unit\Model\Queue\Item;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterfaceFactory;
use Dotdigitalgroup\Sms\Api\SmsOrderRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Queue\Item\QueueItemInterface;
use Dotdigitalgroup\Sms\Model\Queue\Item\TransactionalMessageEnqueuer;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransactionalMessageEnqueuerTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $logger;

    /**
     * @var SmsOrderInterfaceFactory|MockObject
     */
    private $smsOrderInterfaceFactory;

    /**
     * @var SmsOrderRepositoryInterface|MockObject
     */
    private $smsOrderRepositoryInterface;

    /**
     * @var Configuration|MockObject
     */
    private $moduleConfig;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var QueueItemInterface|MockObject
     */
    private $queueItem;

    /**
     * @var TransactionalMessageEnqueuer
     */
    private $enqueuer;

    protected function setUp()
    : void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->smsOrderInterfaceFactory = $this->createMock(SmsOrderInterfaceFactory::class);
        $this->smsOrderRepositoryInterface = $this->createMock(SmsOrderRepositoryInterface::class);
        $this->moduleConfig = $this->createMock(Configuration::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->queueItem = $this->createMock(QueueItemInterface::class);

        $this->enqueuer = new TransactionalMessageEnqueuer(
            $this->logger,
            $this->smsOrderInterfaceFactory,
            $this->smsOrderRepositoryInterface,
            $this->moduleConfig,
            $this->serializer
        );
    }

    public function testQueue()
    {
        $storeId = 1;
        $websiteId = 1;
        $typeId = 1;
        $status = 0;
        $phoneNumber = '1234567890';
        $email = 'test@example.com';
        $additionalData = ['key' => 'value'];
        $serializedData = '{"key":"value"}';

        $smsOrder = $this->createMock(SmsOrderInterface::class);
        $smsOrder->method('setStoreId')->willReturnSelf();
        $smsOrder->method('setWebsiteId')->willReturnSelf();
        $smsOrder->method('setTypeId')->willReturnSelf();
        $smsOrder->method('setStatus')->willReturnSelf();
        $smsOrder->method('setPhoneNumber')->willReturnSelf();
        $smsOrder->method('setEmail')->willReturnSelf();
        $smsOrder->method('setAdditionalData')->willReturnSelf();

        $this->queueItem->method('getStoreId')->willReturn($storeId);
        $this->queueItem->method('getWebsiteId')->willReturn($websiteId);
        $this->queueItem->method('getTypeId')->willReturn($typeId);
        $this->queueItem->method('getPhoneNumber')->willReturn($phoneNumber);
        $this->queueItem->method('getEmail')->willReturn($email);
        $this->queueItem->method('getAdditionalData')->willReturn($additionalData);

        $this->smsOrderInterfaceFactory->expects($this->once())
            ->method('create')
            ->willReturn($smsOrder);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($additionalData)
            ->willReturn($serializedData);

        $this->smsOrderRepositoryInterface->expects($this->once())
            ->method('save')
            ->with($smsOrder);

        $this->enqueuer->queue($this->queueItem);
    }

    public function testCanQueue()
    {
        $storeId = 1;
        $smsConfigPath = 'sms_config_path';

        $this->queueItem->method('getSmsConfigPath')->willReturn($smsConfigPath);

        $this->moduleConfig->expects($this->once())
            ->method('isTransactionalSmsEnabled')
            ->with($storeId)
            ->willReturn(true);

        $this->moduleConfig->expects($this->once())
            ->method('isSmsTypeEnabled')
            ->with($storeId)
            ->willReturn(true);

        $canQueue = $this->enqueuer->canQueue($this->queueItem, $storeId);
        $this->assertTrue($canQueue);
    }
}
