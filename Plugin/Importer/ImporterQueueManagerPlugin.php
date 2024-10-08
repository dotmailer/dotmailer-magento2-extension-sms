<?php

namespace Dotdigitalgroup\Sms\Plugin\Importer;

use Dotdigitalgroup\Email\Model\Sync\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterQueueManager;
use Dotdigitalgroup\Email\Model\Sync\Importer\BulkImportBuilderFactory;
use Dotdigitalgroup\Sms\Model\Importer as SmsImporter;
use Dotdigitalgroup\Sms\Model\Sync\Importer\Type\Contact\UnsubscribeFactory;
use Dotdigitalgroup\Sms\Model\Sync\Importer\Type\Contact\BulkFactory;

class ImporterQueueManagerPlugin
{
    /**
     * @var UnsubscribeFactory
     */
    private $unsubscribeFactory;

    /**
     * @var BulkImportBuilderFactory
     */
    private $bulkImportBuilderFactory;

    /**
     * @var BulkFactory
     */
    private $bulkFactory;

    /**
     * ImporterQueueManagerPlugin constructor.
     *
     * @param UnsubscribeFactory $unsubscribeFactory
     * @param BulkImportBuilderFactory $bulkImportBuilderFactory
     * @param BulkFactory $bulkFactory
     */
    public function __construct(
        UnsubscribeFactory $unsubscribeFactory,
        BulkImportBuilderFactory $bulkImportBuilderFactory,
        BulkFactory $bulkFactory
    ) {
        $this->unsubscribeFactory = $unsubscribeFactory;
        $this->bulkImportBuilderFactory = $bulkImportBuilderFactory;
        $this->bulkFactory = $bulkFactory;
    }

    /**
     * Add SMS Subscriber unsubscribe to the single queue.
     *
     * @param ImporterQueueManager $subject
     * @param array $result
     *
     * @return array
     *
     * @deprecated We have removed this in favour of message queues.
     * @see Dotdigitalgroup\Sms\Model\Queue\Consumer\SmsSubscriptionConsumer::process()
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

    /**
     * Add SMS Subscriber unsubscribe to the bulk queue.
     *
     * @param ImporterQueueManager $subject
     * @param array $additionalImports
     * @return array
     */
    public function beforeGetBulkQueue(ImporterQueueManager $subject, array $additionalImports = [])
    {
        return [
            'additionalImports' => [
                ...$additionalImports,
                ...[
                        $this->bulkImportBuilderFactory->create()
                            ->setModel($this->bulkFactory)
                            ->setMode(SmsImporter::MODE_BULK)
                            ->setType([SmsImporter::IMPORT_TYPE_SMS_SUBSCRIBERS]),
                    ]
                ]
        ];
    }
}
