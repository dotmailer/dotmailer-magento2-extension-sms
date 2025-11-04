<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue;

use Dotdigitalgroup\Sms\Model\Apiconnector\Client;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Dotdigitalgroup\Sms\Api\SmsMessageRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime;

class SenderProgressHandler extends DataObject
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var SmsMessageRepositoryInterface
     */
    private $smsMessageRepository;

    /**
     * @var SmsMessageQueueManager
     */
    private $smsMessageQueueManager;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * SenderProgressHandler constructor.
     *
     * @param SmsMessageRepositoryInterface $smsMessageRepository
     * @param SmsMessageQueueManager $smsMessageQueueManager
     * @param DateTime $dateTime
     * @param array $data
     */
    public function __construct(
        SmsMessageRepositoryInterface $smsMessageRepository,
        SmsMessageQueueManager $smsMessageQueueManager,
        DateTime $dateTime,
        array $data = []
    ) {
        $this->smsMessageRepository = $smsMessageRepository;
        $this->smsMessageQueueManager = $smsMessageQueueManager;
        $this->dateTime = $dateTime;
        parent::__construct($data);
    }

    /**
     * Update sends in progress.
     *
     * @param array $storeIds
     * @throws \Exception
     */
    public function updateSendsInProgress(array $storeIds)
    {
        $inProgressQueue = $this->smsMessageQueueManager->getInProgressQueue(
            $storeIds
        );
        if ($inProgressQueue->getTotalCount() === 0) {
            return;
        }

        $this->client = $this->getClient();

        /** @var SmsMessageInterface $item */
        foreach ($inProgressQueue->getItems() as $item) {
            $messageState = $this->client->getMessageByMessageId($item->getMessageId());

            if (!isset($messageState->messageId)) {
                $item->setStatus(SmsMessageQueueManager::SMS_STATUS_UNKNOWN);
            } elseif (isset($messageState->status)) {
                if ($messageState->status === 'delivered') {
                    $item->setStatus(SmsMessageQueueManager::SMS_STATUS_DELIVERED);
                    $item->setMessage($messageState->statusDetails->channelStatus->statusdescription);
                    $item->setSentAt($this->dateTime->formatDate($messageState->sentOn));
                } elseif ($messageState->status === 'failed') {
                    $item->setStatus(SmsMessageQueueManager::SMS_STATUS_FAILED);
                    $item->setMessage($messageState->statusDetails->reason);
                }
            }

            $this->smsMessageRepository->save($item);
        }
    }

    /**
     * Get client.
     *
     * @return Client
     */
    private function getClient()
    {
        return $this->_getData('client');
    }
}
