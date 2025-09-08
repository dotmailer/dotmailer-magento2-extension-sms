<?php

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;

interface DotdigitalConfigInterface
{
    public const CONFIGURATION_PATHS = [
        ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_ENABLED,
        ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_DEFAULT_FROM_NAME,
        ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_ALPHANUMERIC_FROM_NAME,
        ConfigInterface::XML_PATH_SMS_PHONE_NUMBER_VALIDATION,
        ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_BATCH_SIZE,
        ConfigInterface::XML_PATH_SMS_NEW_ORDER_ENABLED,
        ConfigInterface::XML_PATH_SMS_ORDER_UPDATE_ENABLED,
        ConfigInterface::XML_PATH_SMS_NEW_SHIPMENT_ENABLED,
        ConfigInterface::XML_PATH_SMS_SHIPMENT_UPDATE_ENABLED,
        ConfigInterface::XML_PATH_SMS_NEW_CREDIT_MEMO_ENABLED,
        ConfigInterface::XML_PATH_CONSENT_SMS_REGISTRATION_ENABLED,
        ConfigInterface::XML_PATH_CONSENT_SMS_CHECKOUT_ENABLED,
        ConfigInterface::XML_PATH_CONSENT_SMS_ACCOUNT_ENABLED,
        ConfigInterface::XML_PATH_CONSENT_SMS_SIGNUP_TEXT,
        ConfigInterface::XML_PATH_CONSENT_SMS_MARKETING_TEXT,
        ConfigInterface::XML_PATH_SMS_SIGNUP_ENABLED,
        ConfigInterface::XML_PATH_SMS_NEW_ACCOUNT_SIGNUP_ENABLED,
        ConfigInterface::XML_PATH_CONNECTOR_SMS_SUBSCRIBER_SYNC_ENABLED,
        ConfigInterface::XML_PATH_CONNECTOR_SMS_SUBSCRIBER_ADDRESS_BOOK_ID,
    ];
}
