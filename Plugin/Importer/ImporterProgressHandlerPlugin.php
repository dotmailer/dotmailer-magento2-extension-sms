<?php

namespace Dotdigitalgroup\Sms\Plugin\Importer;

use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterProgressHandler;
use Dotdigitalgroup\Sms\Model\Importer as SmsImporter;

class ImporterProgressHandlerPlugin
{
    /**
     * Append SmsSubscriber imports to V3 set.
     *
     * @param ImporterProgressHandler $subject
     * @param array $result
     * @return array
     */
    public function afterGetInProgressGroups(ImporterProgressHandler $subject, array $result)
    {
        $result[ImporterProgressHandler::VERSION_3]
        [ImporterProgressHandler::CONTACT]
        [ImporterProgressHandler::PROGRESS_GROUP_TYPES][] = SmsImporter::IMPORT_TYPE_SMS_SUBSCRIBERS;
        return $result;
    }
}
