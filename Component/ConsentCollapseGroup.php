<?php

namespace Dotdigitalgroup\Sms\Component;

use Dotdigitalgroup\Sms\Component\Consent\ConsentCheckbox;
use Dotdigitalgroup\Sms\Component\Consent\ConsentTelephone;
use Dotdigitalgroup\Sms\Component\Consent\ConsentText;
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
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * @var TelephoneInputConfig
     */
    private $telephoneInputConfig;

    /**
     * @param ConsentCheckbox $consentCheckbox
     * @param ConsentTelephone $consentTelephone
     * @param ConsentText $consentText
     * @param StoreManagerInterface $storeManager
     * @param TelephoneInputConfig $telephoneInputConfig
     */
    public function __construct(
        ConsentCheckbox $consentCheckbox,
        ConsentTelephone $consentTelephone,
        ConsentText $consentText,
        StoreManagerInterface $storeManager,
        TelephoneInputConfig $telephoneInputConfig
    ) {
        $this->consentCheckbox = $consentCheckbox;
        $this->consentTelephone = $consentTelephone;
        $this->consentText = $consentText;
        $this->storeManager = $storeManager;
        $this->telephoneInputConfig = $telephoneInputConfig;
    }

    /**
     * Render.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function render()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $config = $this->telephoneInputConfig->getConfig();
        return [
            'component' => 'Dotdigitalgroup_Sms/js/view/consentCheckoutForm',
            'config' => [
                'template' => 'Dotdigitalgroup_Sms/consent-checkout-form',
                'intlTelInputConfig' => $config,
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
    }
}
