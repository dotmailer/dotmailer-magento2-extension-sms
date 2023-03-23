<?php

namespace Dotdigitalgroup\Sms\Component;

use Dotdigitalgroup\Sms\Model\Config\Configuration;

class ConsentCheckbox
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
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'id' => 'dd_sms_consent_checkbox',
                'customScope' => 'shippingAddress.custom_attributes',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/checkbox',
                'options' => [],
            ],
            'dataScope' => 'shippingAddress.dd_consent.dd_sms_consent_checkbox',
            'description' => $this->moduleConfig->getSmsSignUpText($storeId),
            'provider' => 'checkoutProvider',
            'visible' => true,
            'checked' => false,
            'validation' => [],
            'sortOrder' => 180,
            'id' => 'dd_sms_consent_checkbox',
        ];
    }
}
