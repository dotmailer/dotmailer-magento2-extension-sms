<?php

namespace Dotdigitalgroup\Sms\Component\Consent;

use Dotdigitalgroup\Sms\Model\Config\Configuration;

class ConsentTransactionalCheckbox
{
    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @param Configuration $moduleConfig
     */
    public function __construct(
        Configuration $moduleConfig
    ) {
        $this->moduleConfig = $moduleConfig;
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

        return [
            'component' => 'Dotdigitalgroup_Sms/js/view/consentTransactionalCheckbox',
            'config' => [
                'customScope' => 'shippingAddress.custom_attributes',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/checkbox',
                'customConfig' => [
                    'isEnabled' => $this->moduleConfig->isTransactionalConsentEnabled($storeId),
                    'isoCodes' => $this->moduleConfig->transactionalConsentApplicableCodes($storeId),
                ],
            ],
            'dataScope' => 'shippingAddress.dd_consent.dd_sms_transactional_consent_checkbox',
            'description' => __($this->moduleConfig->getTransactionalConsentText($storeId)),
            'label' => '',
            'provider' => 'checkoutProvider',
            'visible' => false,
            'checked' => false,
            'validation' => [],
            'sortOrder' => 180,
        ];
    }
}
