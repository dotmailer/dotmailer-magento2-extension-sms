<?php

namespace Dotdigitalgroup\Sms\Component;

use Dotdigitalgroup\Sms\Component\Consent\ConsentCheckbox;
use Dotdigitalgroup\Sms\Component\Consent\ConsentTelephone;
use Dotdigitalgroup\Sms\Component\Consent\ConsentText;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\ViewModel\TelephoneInputConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class ConsentCollapseGroup
{
    /**
     * @var ConsentCheckbox
     */
    private $consentCheckbox;

    /**
     * @var ConsentTelephone
     */
    private $consentTelephone;

    /**
     * @var ConsentText
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
     * @param ConsentCheckbox $consentCheckbox
     * @param ConsentTelephone $consentTelephone
     * @param ConsentText $consentText
     * @param Configuration $moduleConfig
     * @param TelephoneInputConfig $telephoneInputConfig
     */
    public function __construct(
        ConsentCheckbox $consentCheckbox,
        ConsentTelephone $consentTelephone,
        ConsentText $consentText,
        Configuration $moduleConfig,
        TelephoneInputConfig $telephoneInputConfig
    ) {
        $this->consentCheckbox = $consentCheckbox;
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
                'template' => 'Dotdigitalgroup_Sms/consent-checkout-form'
            ],
            'provider' => 'checkoutProvider',
            'children' => [
                'consent-checkout-form-fields-checkbox' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'consent-checkout-form-fields-checkbox',
                    'children' => [
                        'dd_sms_consent_checkbox' => $this->consentCheckbox->render($storeId),
                    ]
                ],
                'consent-checkout-form-fields' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'consent-checkout-form-fields',
                    'children' => [
                        'dd_sms_consent_telephone' => $this->consentTelephone->render($storeId),
                        'dd_sms_consent_text' => $this->consentText->render($storeId),
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
