<?php

namespace Dotdigitalgroup\Sms\Plugin\Importer;

use Dotdigitalgroup\Email\Model\Sync\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterQueueManager;
use Dotdigitalgroup\Sms\Model\Importer as SmsImporter;
use Dotdigitalgroup\Sms\Model\Sync\Importer\Type\Contact\UnsubscribeFactory;

class ImporterQueueManagerPlugin
{
    /**
     * @var UnsubscribeFactory
     */
    private $unsubscribeFactory;

    /**
     * @param UnsubscribeFactory $unsubscribeFactory
     */
    public function __construct(UnsubscribeFactory $unsubscribeFactory)
    {
        $this->unsubscribeFactory = $unsubscribeFactory;
    }

    /**
     * Add SMS Subscriber unsubscribe to the single queue.
     *
     * @param ImporterQueueManager $subject
     * @param array $result
     *
     * @return array
     */
    public function afterGetSingleQueue(ImporterQueueManager $subject, array $result)
    {
        $result[] = [
            'model' => $this->unsubscribeFactory,
            'mode' => SmsImporter::MODE_SUBSCRIBER_UNSUBSCRIBE,
            'type' => SmsImporter::IMPORT_TYPE_SMS_SUBSCRIBER,
            'limit' => Importer::TOTAL_IMPORT_SYNC_LIMIT
        ];
        return $result;
    }
}
