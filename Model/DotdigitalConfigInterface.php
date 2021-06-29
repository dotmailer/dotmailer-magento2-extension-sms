<?php

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;

interface DotdigitalConfigInterface
{
    const CONFIGURATION_PATHS = [
        ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_ENABLED,
        ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_DEFAULT_FROM_NAME,
        ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_ALPHANUMERIC_FROM_NAME,
        ConfigInterface::XML_PATH_SMS_PHONE_NUMBER_VALIDATION,
        ConfigInterface::XML_PATH_TRANSACTIONAL_SMS_BATCH_SIZE,
        ConfigInterface::XML_PATH_SMS_NEW_ORDER_ENABLED,
        ConfigInterface::XML_PATH_SMS_ORDER_UPDATE_ENABLED,
        ConfigInterface::XML_PATH_SMS_NEW_SHIPMENT_ENABLED,
        ConfigInterface::XML_PATH_SMS_SHIPMENT_UPDATE_ENABLED,
        ConfigInterface::XML_PATH_SMS_NEW_CREDIT_MEMO_ENABLED
    ];
}
