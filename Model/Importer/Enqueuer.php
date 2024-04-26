<?php

namespace Dotdigitalgroup\Sms\Model\Importer;

use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Sms\Model\Importer as SmsImporter;

class Enqueuer
{
    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @param ImporterFactory $importerFactory
     */
    public function __construct(
        ImporterFactory $importerFactory
    ) {
        $this->importerFactory = $importerFactory;
    }

    /**
     * Add an unsubscribe job to the import queue.
     *
     * @deprecated we have moved this functionality to message queues.
     * @see \Dotdigitalgroup\Sms\Model\Queue\Consumer\MarketingSmsUnsubscribe::process()
     *
     * @param string|null $contactId
     * @param string $email
     * @param int $websiteId
     *
     * @return void
     */
    public function enqueueUnsubscribe(?string $contactId, string $email, $websiteId): void
    {
        $this->importerFactory->create()
            ->registerQueue(
                SmsImporter::IMPORT_TYPE_SMS_SUBSCRIBER,
                ['id' => $contactId, 'email' => $email],
                Importer::MODE_SUBSCRIBER_UNSUBSCRIBE,
                $websiteId
            );
    }
}
