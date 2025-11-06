<?php

namespace Dotdigitalgroup\Sms\Model\Config;

interface ConfigInterface
{
    public const XML_PATH_TRANSACTIONAL_SMS_ALPHANUMERIC_FROM_NAME = 'transactional_sms/sms_settings/alphanumeric_from_name';// phpcs:ignore
    public const XML_PATH_TRANSACTIONAL_SMS_ENABLED = 'transactional_sms/sms_settings/enabled';
    public const XML_PATH_TRANSACTIONAL_SMS_DEFAULT_FROM_NAME = 'transactional_sms/sms_settings/default_sms_from_name';
    public const XML_PATH_SMS_PHONE_NUMBER_VALIDATION = 'transactional_sms/sms_settings/phone_number_validation';
    public const XML_PATH_TRANSACTIONAL_SMS_BATCH_SIZE = 'transactional_sms/sms_settings/batch_size';

    public const XML_PATH_TRANSACTIONAL_SMS_CONSENT_ENABLED = 'transactional_sms/consent/enabled';
    public const XML_PATH_TRANSACTIONAL_SMS_CONSENT_TEXT = 'transactional_sms/consent/text';
    public const XML_PATH_TRANSACTIONAL_SMS_OPTOUT_TEXT = 'transactional_sms/consent/opt_out_text';
    public const XML_PATH_CPAAS_OPTOUT_GENERATED = 'transactional_sms/consent/cpaas_optout_generated';

    public const XML_PATH_SMS_NEW_ORDER_ENABLED = 'transactional_sms/sms_templates/new_order_confirmation_enabled';
    public const XML_PATH_SMS_NEW_ORDER_MESSAGE = 'transactional_sms/sms_templates/new_order_confirmation_message';

    public const XML_PATH_SMS_ORDER_UPDATE_ENABLED = 'transactional_sms/sms_templates/order_update_enabled';
    public const XML_PATH_SMS_ORDER_UPDATE_MESSAGE = 'transactional_sms/sms_templates/order_update_message';

    public const XML_PATH_SMS_NEW_SHIPMENT_ENABLED = 'transactional_sms/sms_templates/new_shipment_enabled';
    public const XML_PATH_SMS_NEW_SHIPMENT_MESSAGE = 'transactional_sms/sms_templates/new_shipment_message';

    public const XML_PATH_SMS_SHIPMENT_UPDATE_ENABLED = 'transactional_sms/sms_templates/shipment_update_enabled';
    public const XML_PATH_SMS_SHIPMENT_UPDATE_MESSAGE = 'transactional_sms/sms_templates/shipment_update_message';

    public const XML_PATH_SMS_NEW_CREDIT_MEMO_ENABLED = 'transactional_sms/sms_templates/new_credit_memo_enabled';
    public const XML_PATH_SMS_NEW_CREDIT_MEMO_MESSAGE = 'transactional_sms/sms_templates/new_credit_memo_message';

    public const XML_PATH_SMS_SIGNUP_ENABLED = 'connector_consent/sms_templates/signup_enabled';
    public const XML_PATH_SMS_SIGNUP_MESSAGE = 'connector_consent/sms_templates/signup_message';

    public const XML_PATH_SMS_NEW_ACCOUNT_SIGNUP_ENABLED = 'connector_consent/sms_templates/new_account_signup_enabled';
    public const XML_PATH_SMS_NEW_ACCOUNT_SIGNUP_MESSAGE = 'connector_consent/sms_templates/new_account_signup_message';
    /**
     * @deprecated Since version 1.7.3, use new paths instead.
     * @see
     * - \Dotdigitalgroup\Sms\Model\Config\ConfigInterface::XML_PATH_CONSENT_SMS_REGISTRATION_ENABLED
     * - \Dotdigitalgroup\Sms\Model\Config\ConfigInterface::XML_PATH_CONSENT_SMS_CHECKOUT_ENABLED
     * - \Dotdigitalgroup\Sms\Model\Config\ConfigInterface::XML_PATH_CONSENT_SMS_ACCOUNT_ENABLED
     */
    public const XML_PATH_CONSENT_SMS_ENABLED = 'connector_consent/sms/enabled';
    public const XML_PATH_CONSENT_SMS_REGISTRATION_ENABLED = 'connector_consent/sms/registration_enabled';
    public const XML_PATH_CONSENT_SMS_CHECKOUT_ENABLED = 'connector_consent/sms/checkout_enabled';
    public const XML_PATH_CONSENT_SMS_ACCOUNT_ENABLED = 'connector_consent/sms/account_enabled';
    public const XML_PATH_CONSENT_SMS_SIGNUP_TEXT = 'connector_consent/sms/signup_text';
    public const XML_PATH_CONSENT_SMS_MARKETING_TEXT = 'connector_consent/sms/marketing_consent_text';

    public const XML_PATH_CONNECTOR_SMS_SUBSCRIBER_SYNC_ENABLED = 'sync_settings/sync/sms_subscriber_enabled';
    public const XML_PATH_CONNECTOR_SMS_SUBSCRIBER_ADDRESS_BOOK_ID = 'sync_settings/addressbook/sms_subscribers';

    public const SMS_TYPE_NEW_ORDER = 1;
    public const SMS_TYPE_UPDATE_ORDER = 2;
    public const SMS_TYPE_NEW_SHIPMENT = 3;
    public const SMS_TYPE_UPDATE_SHIPMENT = 4;
    public const SMS_TYPE_NEW_CREDIT_MEMO = 5;
    public const SMS_TYPE_SIGN_UP = 6;
    public const SMS_TYPE_NEW_ACCOUNT_SIGN_UP = 7;

    public const TRANSACTIONAL_SMS_MESSAGE_TYPES_MAP = [
        self::SMS_TYPE_NEW_ORDER => [
            'enabled_path' => self::XML_PATH_SMS_NEW_ORDER_ENABLED,
            'message_path' => self::XML_PATH_SMS_NEW_ORDER_MESSAGE,
            'class' => \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewOrder::class,
        ],
        self::SMS_TYPE_UPDATE_ORDER => [
            'enabled_path' => self::XML_PATH_SMS_ORDER_UPDATE_ENABLED,
            'message_path' => self::XML_PATH_SMS_ORDER_UPDATE_MESSAGE,
            'class' => \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\UpdateOrder::class,
        ],
        self::SMS_TYPE_NEW_SHIPMENT => [
            'enabled_path' => self::XML_PATH_SMS_NEW_SHIPMENT_ENABLED,
            'message_path' => self::XML_PATH_SMS_NEW_SHIPMENT_MESSAGE,
            'class' => \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewShipment::class,
        ],
        self::SMS_TYPE_UPDATE_SHIPMENT => [
            'enabled_path' => self::XML_PATH_SMS_SHIPMENT_UPDATE_ENABLED,
            'message_path' => self::XML_PATH_SMS_SHIPMENT_UPDATE_MESSAGE,
            'class' => \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\UpdateShipment::class,
        ],
        self::SMS_TYPE_NEW_CREDIT_MEMO => [
            'enabled_path' => self::XML_PATH_SMS_NEW_CREDIT_MEMO_ENABLED,
            'message_path' => self::XML_PATH_SMS_NEW_CREDIT_MEMO_MESSAGE,
            'class' => \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewCreditMemo::class,
        ],
        self::SMS_TYPE_SIGN_UP => [
            'enabled_path' => self::XML_PATH_SMS_SIGNUP_ENABLED,
            'message_path' => self::XML_PATH_SMS_SIGNUP_MESSAGE,
            'class' => \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\SmsSignup::class,
        ],
        self::SMS_TYPE_NEW_ACCOUNT_SIGN_UP => [
            'enabled_path' => self::XML_PATH_SMS_NEW_ACCOUNT_SIGNUP_ENABLED,
            'message_path' => self::XML_PATH_SMS_NEW_ACCOUNT_SIGNUP_MESSAGE,
            'class' => \Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\NewAccountSignup::class,
        ],
    ];

    public const SMS_ISO_COUNTRY_CODES_WITH_TRANSACTIONAL_CONSENT = ['us'];
}
