<?php

namespace Dotdigitalgroup\Sms\Test\Unit\Queue;

use Dotdigitalgroup\Sms\Model\Apiconnector\Client;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Dotdigitalgroup\Sms\Api\SmsMessageRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Queue\SmsMessageQueueManager;
use Dotdigitalgroup\Sms\Model\Queue\SenderProgressHandler;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime;
use PHPUnit\Framework\TestCase;

class SenderProgressHandlerTest extends TestCase
{
    /**
     * @var DataObject
     */
    private $dataObjectMock;

    /**
     * @var Client
     */
    private $clientMock;

    /**
     * @var SmsMessageRepositoryInterface
     */
    private $smsMessageRepositoryMock;

    /**
     * @var SmsMessageQueueManager
     */
    private $smsMessageQueueManagerMock;

    /**
     * @var DateTime
     */
    private $dateTimeMock;

    /**
     * @var SenderProgressHandler
     */
    private $senderProgressHandler;

    /**
     * @var SearchResultsInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchResultsInterfaceMock;

    protected function setUp() :void
    {
        $this->smsMessageRepositoryMock = $this->createMock(SmsMessageRepositoryInterface::class);
        $this->smsMessageQueueManagerMock = $this->createMock(SmsMessageQueueManager::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->dataObjectMock = $this->createMock(DataObject::class);
        $this->searchResultsInterfaceMock = $this->createMock(SearchResultsInterface::class);

        $this->senderProgressHandler = new SenderProgressHandler(
            $this->smsMessageRepositoryMock,
            $this->smsMessageQueueManagerMock,
            $this->dateTimeMock,
            ['client' => $this->createMock(Client::class)]
        );
    }

    public function testReturnIfNoPendingItems()
    {
        $storeIds = $this->getStoreIds();

        $this->smsMessageQueueManagerMock->expects($this->once())
            ->method('getInProgressQueue')
            ->with($storeIds)
            ->willReturn($this->searchResultsInterfaceMock);

        $this->searchResultsInterfaceMock->expects($this->once())
            ->method('getTotalCount')
            ->willReturn(0);

        $this->smsMessageRepositoryMock->expects($this->never())
            ->method('save');

        $this->senderProgressHandler->updateSendsInProgress($storeIds);
    }

    public function testHandleDeliveredMessages()
    {
        $storeIds = $this->getStoreIds();
        $messageStateMock = $this->getDeliveredMessageState();

        $this->smsMessageQueueManagerMock->expects($this->once())
            ->method('getInProgressQueue')
            ->with($storeIds)
            ->willReturn($this->searchResultsInterfaceMock);

        $this->searchResultsInterfaceMock->expects($this->once())
            ->method('getItems')
            ->willReturn(
                $this->getDeliveredSmsMockArray(3)
            );

        $this->clientMock = $this->senderProgressHandler->getData('client');

        $this->clientMock->expects($this->atLeastOnce())
            ->method('getMessageByMessageId')
            ->willReturn($messageStateMock);

        $this->dateTimeMock->expects($this->atLeastOnce())
            ->method('formatDate')
            ->willReturn($messageStateMock->sentOn);

        $this->smsMessageRepositoryMock->expects($this->atLeastOnce())
            ->method('save');

        $this->senderProgressHandler->updateSendsInProgress($storeIds);
    }

    public function testHandleFailedMessage()
    {
        $storeIds = $this->getStoreIds();
        $messageStateMock = $this->getFailedMessageState();

        $this->smsMessageQueueManagerMock->expects($this->once())
            ->method('getInProgressQueue')
            ->with($storeIds)
            ->willReturn($this->searchResultsInterfaceMock);

        $this->searchResultsInterfaceMock->expects($this->once())
            ->method('getItems')
            ->willReturn(
                $this->getFailedSmsMockArray(3)
            );

        $this->clientMock = $this->senderProgressHandler->getData('client');

        $this->clientMock->expects($this->atLeastOnce())
            ->method('getMessageByMessageId')
            ->willReturn($messageStateMock);

        $this->dateTimeMock->expects($this->never())
            ->method('formatDate');

        $this->smsMessageRepositoryMock->expects($this->atLeastOnce())
            ->method('save');

        $this->senderProgressHandler->updateSendsInProgress($storeIds);
    }

    public function testHandleMissingMessageId()
    {
        $storeIds = $this->getStoreIds();
        $messageStateMock = $this->getNoMessageState();

        $this->smsMessageQueueManagerMock->expects($this->once())
            ->method('getInProgressQueue')
            ->with($storeIds)
            ->willReturn($this->searchResultsInterfaceMock);

        $this->searchResultsInterfaceMock->expects($this->once())
            ->method('getItems')
            ->willReturn(
                [$this->getErrorUnknownSmsMock()]
            );

        $this->clientMock = $this->senderProgressHandler->getData('client');

        $this->clientMock->expects($this->once())
            ->method('getMessageByMessageId')
            ->willReturn($messageStateMock);

        $this->smsMessageRepositoryMock->expects($this->atLeastOnce())
            ->method('save');

        $this->senderProgressHandler->updateSendsInProgress($storeIds);
    }

    private function getStoreIds()
    {
        return [1, 2, 3];
    }

    private function getDeliveredMessageState()
    {
        $state = [
            'messageId' => '70266de2-ad1f-4acd-8588-456ad58acc1',
            'status' => 'delivered',
            'statusDetails' => [
                'channelStatus' => [
                    'statusdescription' => 'Messages delivered to handset'
                ]
            ],
            'sentOn' => '2020-10-06 16:00:00',
        ];

        return json_decode(json_encode($state));
    }

    private function getFailedMessageState()
    {
        $state = [
            'messageId' => '70266de2-ad1f-4acd-8588-456ad58acc2',
            'status' => 'failed',
            'statusDetails' => [
                'reason' => 'Channel reported the message was undeliverable'
            ],
        ];

        return json_decode(json_encode($state));
    }

    private function getNoMessageState()
    {
        return (object) [
            'messageId' => null,
        ];
    }

    private function getDeliveredSmsMockArray($multiple)
    {
        $smsMessageMocks = [];
        for ($i = 0; $i < $multiple; $i++) {
            $mock = $this->createMock(SmsMessageInterface::class);
            $mock->expects($this->any())
                ->method('getMessageId')
                ->willReturn($mock);
            $mock->expects($this->once())
                ->method('setStatus')
                ->willReturn($mock);
            $mock->expects($this->any())
                ->method('setMessage')
                ->willReturn($mock);
            $mock->expects($this->once())
                ->method('setSentAt')
                ->willReturn($mock);

            $smsMessageMocks[] = $mock;
        }

        return $smsMessageMocks;
    }

    private function getFailedSmsMockArray($multiple)
    {
        $smsMessageMocks = [];
        for ($i = 0; $i < $multiple; $i++) {
            $mock = $this->createMock(SmsMessageInterface::class);
            $mock->expects($this->any())
                ->method('getMessageId')
                ->willReturn($mock);
            $mock->expects($this->once())
                ->method('setStatus')
                ->willReturn($mock);
            $mock->expects($this->any())
                ->method('setMessage')
                ->willReturn($mock);

            $smsMessageMocks[] = $mock;
        }

        return $smsMessageMocks;
    }

    private function getErrorUnknownSmsMock()
    {
        $mock = $this->createMock(SmsMessageInterface::class);
        $mock->expects($this->once())
            ->method('setStatus')
            ->willReturn($mock);
        return $mock;
    }
}
