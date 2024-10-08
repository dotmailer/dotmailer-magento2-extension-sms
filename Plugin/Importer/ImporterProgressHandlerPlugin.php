<?php

namespace Dotdigitalgroup\Sms\Plugin\Importer;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterProgressHandler;
use Dotdigitalgroup\Email\Model\Sync\Importer\V2InProgressImportResponseHandlerFactory as V2HandlerFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\V3InProgressImportResponseHandlerFactory as V3HandlerFactory;
use Dotdigitalgroup\Sms\Model\Importer as SmsImporter;

class ImporterProgressHandlerPlugin
{
    /**
     * @var V3HandlerFactory
     */
    private $v3HandlerFactory;

    /**
     * @param V3HandlerFactory $v3HandlerFactory
     */
    public function __construct(
        V3HandlerFactory $v3HandlerFactory
    ) {
        $this->v3HandlerFactory = $v3HandlerFactory;
    }

    /**
     * Create new group for SMS - V3 but 'Bulk'.
     *
     * @param ImporterProgressHandler $subject
     * @param array $result
     * @return array
     */
    public function afterGetInProgressGroups(ImporterProgressHandler $subject, array $result)
    {
        $result[ImporterProgressHandler::VERSION_3]['Sms'] = [
            ImporterProgressHandler::PROGRESS_GROUP_MODE => ImporterModel::MODE_BULK,
            ImporterProgressHandler::PROGRESS_GROUP_TYPES => [
                SmsImporter::IMPORT_TYPE_SMS_SUBSCRIBERS
            ],
            ImporterProgressHandler::PROGRESS_GROUP_MODEL => $this->v3HandlerFactory,
            ImporterProgressHandler::PROGRESS_GROUP_RESOURCE => 'contacts',
            ImporterProgressHandler::PROGRESS_GROUP_METHOD => 'getImportById'
        ];

        return $result;
    }
}
