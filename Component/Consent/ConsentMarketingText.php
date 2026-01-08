<?php

namespace Dotdigitalgroup\Sms\Component\Consent;

use Dotdigitalgroup\Sms\Model\Config\Configuration;

class ConsentMarketingText
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
                'customScope' => 'description',
                'customEntry' => null,
                'template' => 'Dotdigitalgroup_Sms/free-text',
            ],
            'id' => 'dd_sms_marketing_consent_text',
            'class' => 'dd-sms-consent-text',
            'text' => $this->moduleConfig->getSmsMarketingConsentText($storeId),
            'provider' => 'checkoutProvider',
            'sortOrder' => 220,
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
            'focused' => false,
        ];
    }
}
