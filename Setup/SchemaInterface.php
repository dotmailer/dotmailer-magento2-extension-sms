<?php

namespace Dotdigitalgroup\Sms\Setup;

interface SchemaInterface
{
    public const EMAIL_SMS_MESSAGE_QUEUE_TABLE = 'email_sms_message_queue';
    public const SMS_SUBSCRIBERS_TEMPORARY_TABLE = 'dd_sms_subscribers_temp';
}
