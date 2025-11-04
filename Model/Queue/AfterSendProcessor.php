<?php

namespace Dotdigitalgroup\Sms\Model\Queue;

use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Dotdigitalgroup\Sms\Api\SmsMessageRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Message\MessageBuilder;

/**
 * Class AfterSendProcessor
 *
 * @deprecated This class is deprecated and will be removed in a future version.
 * The queue system now uses a publisher and consumer.
 * @see \Dotdigitalgroup\Sms\Model\Queue\Consumer\SmsMessageConsumer
 */

class AfterSendProcessor
{
    /**
     * @var SmsMessageRepositoryInterface
     */
    private $smsMessageRepository;

    /**
     * AfterSendProcessor constructor.
     * @param SmsMessageRepositoryInterface $smsMessageRepository
     */
    public function __construct(
        SmsMessageRepositoryInterface  $smsMessageRepository
    ) {
        $this->smsMessageRepository = $smsMessageRepository;
    }

    /**
     * Loop through the batched rows, assigning a message id from the response,
     * plus the message content from the cached $batchedContent.
     * The results will always be keyed according to the posted batch.
     *
     * @param SmsMessageInterface[] $itemsToProcess
     * @param array $results
     * @param array $messageBatch
     */
    public function process(array $itemsToProcess, array $results, array $messageBatch)
    {
        $batchRowIds = array_keys($itemsToProcess);

        foreach ($batchRowIds as $i => $rowId) {
            $item = $itemsToProcess[$rowId];

            $item->setMessageId($results[$i]->messageId)
                ->setStatus(SmsMessageQueueManager::SMS_STATUS_IN_PROGRESS)
                ->setContent($messageBatch[$i]['body']);

            $this->smsMessageRepository->save($item);
        }
    }
}
