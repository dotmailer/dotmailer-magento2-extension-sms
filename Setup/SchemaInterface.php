<?php

namespace Dotdigitalgroup\Sms\Setup;

interface SchemaInterface
{
    public const EMAIL_TRANSACTIONAL_SMS_QUEUE_TABLE = 'email_transactional_sms_queue';
    public const SMS_SUBSCRIBERS_TEMPORARY_TABLE = 'dd_sms_subscribers_temp';
}
