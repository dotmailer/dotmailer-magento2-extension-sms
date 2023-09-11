<?php

namespace Dotdigitalgroup\Sms\Component\Consent;

use Dotdigitalgroup\Sms\Model\Config\Configuration;

class ConsentTelephone
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
     * Render.
     *
     * @param string|int $storeId
     *
     * @return array
     */
    public function render($storeId)
    {
        $layout =  [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'id' => 'dd_sms_consent_telephone',
                'customScope' => 'shippingAddress.custom_attributes',
                'customEntry' => null,
                'template' => 'ui/form/field',
            ],
            'dataScope' => 'shippingAddress.dd_consent.dd_sms_consent_telephone',
            'label' => null,
            'provider' => 'checkoutProvider',
            'sortOrder' => 200,
            'validation' => [
                "max_text_length" => 255,
                "min_text_length" => 1,
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
            'focused' => false,
            'id' => 'dd_sms_consent_telephone',
        ];

        if ($this->moduleConfig->isPhoneNumberValidationEnabled($storeId)) {
            $layout['config']['elementTmpl'] = 'Dotdigitalgroup_Sms/form/element/telephone';
            $layout['validation']['validate-phone-number'] = true;
        }

        return $layout;
    }
}
