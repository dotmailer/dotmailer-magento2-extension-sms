<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Sync\Importer\InProgress\Config;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Sms\Model\Importer as SmsImporter;

interface V3SmsSubscribersConfigInterface
{
    public const HANDLER = 'v3_sms_subscribers';
    public const IMPORT_MODE = ImporterModel::MODE_BULK;
    public const RESOURCE = 'contacts';
    public const METHOD = 'getImportById';
    public const IMPORT_TYPES = [
        SmsImporter::IMPORT_TYPE_SMS_SUBSCRIBERS,
    ];
}
