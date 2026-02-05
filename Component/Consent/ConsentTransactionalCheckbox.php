<?php

namespace Dotdigitalgroup\Sms\Component\Consent;

use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\ViewModel\TelephoneInputConfig;

class ConsentTransactionalCheckbox
{
    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var TelephoneInputConfig
     */
    private $telephoneInputConfig;

    /**
     * @param Configuration $moduleConfig
     * @param TelephoneInputConfig $telephoneInputConfig
     */
    public function __construct(
        Configuration $moduleConfig,
        TelephoneInputConfig $telephoneInputConfig
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->telephoneInputConfig = $telephoneInputConfig;
    }

    /**
     * Render the component.
     *
     * @param string|int $storeId
     * @return array
     */
    public function render($storeId)
    {
        if (!$this->moduleConfig->isPhoneNumberValidationEnabled($storeId)) {
            return [];
        }

        if (!$this->moduleConfig->isTransactionalConsentEnabled($storeId)) {
            return [];
        }

        return [
            'component' => 'Dotdigitalgroup_Sms/js/view/consentTransactionalCheckbox',
            'config' => [
                'customScope' => 'shippingAddress.custom_attributes',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/checkbox',
                'customConfig' => [
                    'isoCodes' => $this->moduleConfig->transactionalConsentApplicableCodes($storeId),
                    'intlTelInputConfig' => $this->telephoneInputConfig->getConfig()
                ],
            ],
            'dataScope' => 'shippingAddress.dd_consent.dd_sms_transactional_consent_checkbox',
            'description' => __($this->moduleConfig->getTransactionalConsentText($storeId)),
            'label' => '',
            'provider' => 'checkoutProvider',
            'checked' => false,
            'validation' => [],
            'sortOrder' => 180,
        ];
    }
}
