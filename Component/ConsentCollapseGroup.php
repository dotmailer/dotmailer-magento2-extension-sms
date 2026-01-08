<?php

namespace Dotdigitalgroup\Sms\Component;

use Dotdigitalgroup\Sms\Component\Consent\ConsentMarketingCheckbox;
use Dotdigitalgroup\Sms\Component\Consent\ConsentMarketingTelephone;
use Dotdigitalgroup\Sms\Component\Consent\ConsentMarketingText;
use Dotdigitalgroup\Sms\Component\Consent\ConsentTransactionalCheckbox;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\ViewModel\TelephoneInputConfig;

class ConsentCollapseGroup
{
    /**
     * @var ConsentMarketingCheckbox
     */
    private $consentCheckbox;

    /**
     * @var ConsentTransactionalCheckbox
     */
    private $transactionalConsentCheckbox;

    /**
     * @var ConsentMarketingTelephone
     */
    private $consentTelephone;

    /**
     * @var ConsentMarketingText
     */
    private $consentText;

    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var TelephoneInputConfig
     */
    private $telephoneInputConfig;

    /**
     * @param ConsentMarketingCheckbox $consentCheckbox
     * @param ConsentTransactionalCheckbox $transactionalConsentCheckbox
     * @param ConsentMarketingTelephone $consentTelephone
     * @param ConsentMarketingText $consentText
     * @param Configuration $moduleConfig
     * @param TelephoneInputConfig $telephoneInputConfig
     */
    public function __construct(
        ConsentMarketingCheckbox     $consentCheckbox,
        ConsentTransactionalCheckbox $transactionalConsentCheckbox,
        ConsentMarketingTelephone    $consentTelephone,
        ConsentMarketingText         $consentText,
        Configuration                $moduleConfig,
        TelephoneInputConfig         $telephoneInputConfig
    ) {
        $this->consentCheckbox = $consentCheckbox;
        $this->transactionalConsentCheckbox = $transactionalConsentCheckbox;
        $this->consentTelephone = $consentTelephone;
        $this->consentText = $consentText;
        $this->moduleConfig = $moduleConfig;
        $this->telephoneInputConfig = $telephoneInputConfig;
    }

    /**
     * Render.
     *
     * @param string|int $storeId
     *
     * @return array
     */
    public function render($storeId)
    {
        $group = [
            'component' => 'Dotdigitalgroup_Sms/js/view/consentCheckoutForm',
            'config' => [
                'template' => 'Dotdigitalgroup_Sms/consent-checkout-form',
            ],
            'provider' => 'checkoutProvider',
            'children' => [
                'consent-checkout-form-fields-checkbox' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'consent-checkout-form-fields-checkbox',
                    'children' => [
                        'dd_sms_marketing_consent_checkbox' => $this->consentCheckbox->render($storeId),
                        'dd_sms_transactional_consent_checkbox' => $this->transactionalConsentCheckbox->render($storeId)
                    ]
                ],
                'marketing-consent-checkout-form-fields' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'marketing-consent-checkout-form-fields',
                    'children' => [
                        'dd_sms_marketing_consent_telephone' => $this->consentTelephone->render($storeId),
                        'dd_sms_marketing_consent_text' => $this->consentText->render($storeId),
                    ]
                ]
            ]
        ];
        if ($this->moduleConfig->isPhoneNumberValidationEnabled($storeId)) {
            $group['config']['intlTelInputConfig'] = $this->telephoneInputConfig->getConfig();
        }
        return $group;
    }
}
