<?php

namespace Dotdigitalgroup\Sms\Component\Consent;

use Dotdigitalgroup\Sms\Model\Config\Configuration;

class ConsentMarketingCheckbox
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
        return [
            'component' => 'Magento_Ui/js/form/element/boolean',
            'config' => [
                'customScope' => 'shippingAddress.custom_attributes',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/checkbox',
            ],
            'dataScope' => 'shippingAddress.dd_consent.dd_sms_marketing_consent_checkbox',
            'description' => $this->moduleConfig->getSmsSignUpText($storeId),
            'provider' => 'checkoutProvider',
            'visible' => $this->moduleConfig->isSmsConsentCheckoutEnabled($storeId),
            'disabled' => !$this->moduleConfig->isSmsConsentCheckoutEnabled($storeId),
            'checked' => false,
            'validation' => [],
            'sortOrder' => 180,
        ];
    }
}
