<?php

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Email\Model\Importer as EmailImporter;

class Importer extends EmailImporter
{
    public const IMPORT_TYPE_SMS_SUBSCRIBERS = 'SMS_Subscribers';
    public const IMPORT_TYPE_SMS_SUBSCRIBER = 'SMS_Subscriber';

    public const MODE_SUBSCRIBER_UNSUBSCRIBE = 'Subscriber_Unsubscribe';
}
