<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Plugin\Importer;

use Dotdigitalgroup\Email\Model\Sync\Importer\ImporterProgressHandler;
use Dotdigitalgroup\Sms\Model\Sync\Importer\InProgress\Config\V3SmsSubscribersConfigInterface;

class ImporterProgressHandlerPlugin
{
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
            ImporterProgressHandler::PROGRESS_GROUP_MODE => V3SmsSubscribersConfigInterface::IMPORT_MODE,
            ImporterProgressHandler::PROGRESS_GROUP_TYPES => V3SmsSubscribersConfigInterface::IMPORT_TYPES,
            ImporterProgressHandler::PROGRESS_GROUP_HANDLER => V3SmsSubscribersConfigInterface::HANDLER,
            ImporterProgressHandler::PROGRESS_GROUP_RESOURCE => V3SmsSubscribersConfigInterface::RESOURCE,
            ImporterProgressHandler::PROGRESS_GROUP_METHOD => V3SmsSubscribersConfigInterface::METHOD,
        ];

        return $result;
    }
}
