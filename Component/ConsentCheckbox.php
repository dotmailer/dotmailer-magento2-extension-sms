<?php

namespace Dotdigitalgroup\Sms\Component;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConsentCheckbox
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
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
            'description' => $this->getSmsSignUpText($storeId),
            'provider' => 'checkoutProvider',
            'visible' => true,
            'checked' => false,
            'validation' => [],
            'sortOrder' => 180,
            'id' => 'dd_sms_consent_checkbox',
        ];
    }

    /**
     * Get SMS marketing consent text.
     *
     * @param string|int $storeId
     * @return string
     */
    private function getSmsSignUpText($storeId)
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_CONSENT_SMS_SIGNUP_TEXT,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }
}
