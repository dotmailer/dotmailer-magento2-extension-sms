<?php

namespace Dotdigitalgroup\Sms\Model\Queue;

use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Dotdigitalgroup\Sms\Api\SmsOrderRepositoryInterface;

class AfterSendProcessor
{
    /**
     * @var SmsOrderRepositoryInterface
     */
    private $smsOrderRepository;

    /**
     * AfterSendProcessor constructor.
     * @param SmsOrderRepositoryInterface $smsOrderRepository
     */
    public function __construct(
        SmsOrderRepositoryInterface  $smsOrderRepository
    ) {
        $this->smsOrderRepository = $smsOrderRepository;
    }

    /**
     * Loop through the batched rows, assigning a message id from the response.
     * The results will always be keyed according to the posted batch.
     *
     * @param SmsOrderInterface[] $itemsToProcess
     * @param array $results
     */
    public function process(array $itemsToProcess, array $results)
    {
        $batchRowIds = array_keys($itemsToProcess);

        foreach ($batchRowIds as $i => $rowId) {
            $item = $itemsToProcess[$rowId];
            $matchingResult = $results[$i];

            $item->setMessageId($matchingResult->messageId)
                ->setStatus(OrderQueueManager::SMS_STATUS_IN_PROGRESS);

            $this->smsOrderRepository->save($item);
        }
    }
}
