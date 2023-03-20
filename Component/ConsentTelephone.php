<?php

namespace Dotdigitalgroup\Sms\Component;

class ConsentTelephone
{
    /**
     * @return array
     */
    public function render()
    {
        return  [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => 'shippingAddress.custom_attributes',
                'customEntry' => null,
                'template' => 'ui/form/field',
                'elementTmpl' => 'Dotdigitalgroup_Sms/form/element/telephone',
            ],
            'dataScope' => 'shippingAddress.custom_attributes.dd_sms_consent_telephone',
            'label' => null,
            'provider' => 'checkoutProvider',
            'sortOrder' => 200,
            'validation' => [
                "max_text_length" => 255,
                "min_text_length" => 1,
                'validate-phone-number' => true
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
            'focused' => false,
            'id' => 'dd_sms_consent_telephone',
        ];
    }
}
