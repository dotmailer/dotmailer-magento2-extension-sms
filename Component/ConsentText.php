<?php

namespace Dotdigitalgroup\Sms\Component;

use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConsentText
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
                'customScope' => 'description',
                'customEntry' => null,
                'template' => 'Dotdigitalgroup_Sms/free-text',
            ],
            'id' => 'dd_sms_consent_text',
            'class' => 'dd-sms-consent-text',
            'text' => $this->getSmsMarketingConsentText($storeId),
            'provider' => 'checkoutProvider',
            'sortOrder' => 220,
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
            'focused' => false,
        ];
    }

    /**
     * Get SMS marketing consent text.
     *
     * @param string|int $storeId
     * @return string
     */
    private function getSmsMarketingConsentText($storeId)
    {
        return $this->scopeConfig->getValue(
            ConfigInterface::XML_PATH_CONSENT_SMS_MARKETING_TEXT,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }
}
