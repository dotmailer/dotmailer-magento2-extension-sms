<?php

namespace Dotdigitalgroup\Sms\Plugin\Block\Checkout;

use Dotdigitalgroup\Sms\Component\ConsentCheckbox;
use Dotdigitalgroup\Sms\Component\ConsentTelephone;
use Dotdigitalgroup\Sms\Component\ConsentText;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Checkout\Block\Checkout\LayoutProcessor as MageLayoutProcessor;
use Magento\Customer\Helper\Session\CurrentCustomerAddress;
use Magento\Customer\Model\Session;
use Magento\Store\Model\StoreManagerInterface;

class LayoutProcessor
{
    /**
     * @var ConsentCheckbox
     */
    private $consentCheckbox;

    /**
     * ConsentTelephone
     */
    private $consentTelephone;

    /**
     * @var ConsentText
     */
    private $consentText;

    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var CurrentCustomerAddress
     */
    private $currentCustomerAddress;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * LayoutProcessor constructor.
     *
     * @param ConsentCheckbox $consentCheckbox
     * @param ConsentTelephone $consentTelephone
     * @param ConsentText $consentText
     * @param Configuration $moduleConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConsentCheckbox $consentCheckbox,
        ConsentTelephone $consentTelephone,
        ConsentText $consentText,
        Configuration $moduleConfig,
        Session $customerSession,
        CurrentCustomerAddress $currentCustomerAddress,
        StoreManagerInterface $storeManager
    ) {
        $this->consentCheckbox = $consentCheckbox;
        $this->consentTelephone = $consentTelephone;
        $this->consentText = $consentText;
        $this->moduleConfig = $moduleConfig;
        $this->customerSession = $customerSession;
        $this->currentCustomerAddress = $currentCustomerAddress;
        $this->storeManager = $storeManager;
    }

    /**
     * After process.
     *
     * @param MageLayoutProcessor $subject
     * @param array $jsLayout
     * @return mixed
     */
    public function afterProcess(MageLayoutProcessor $subject, $jsLayout)
    {
        $storeId = $this->storeManager->getStore()->getId();

        if (!$this->moduleConfig->isPhoneNumberValidationEnabled($storeId)) {
            return $jsLayout;
        }

        // @codingStandardsIgnoreStart
        $shippingConfiguration = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];
        $billingConfiguration = &$jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'];
        $shippingSelection = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['before-form']['children'];

        if (isset($shippingConfiguration)) {
            $shippingConfiguration['telephone'] = $this->moduleConfig->telephoneFieldConfig("shippingAddress");

            if (!$this->currentCustomerHasStoredShippingAddress()) {
                $this->appendConsentLayout($shippingConfiguration, $storeId);
            }
        }

        if (isset($shippingSelection)) {
            $shippingSelection['dd-telephone-resubmission-form'] = $this->moduleConfig->getResubmissionForm();

            if ($this->currentCustomerHasStoredShippingAddress()) {
                $this->appendConsentLayout($shippingSelection, $storeId);
            }
        }

        /* config: checkout/options/display_billing_address_on = payment_method */
        if (isset($billingConfiguration)) {
            foreach ($billingConfiguration as $key => &$element) {
                $method = substr($key, 0, -5);

                $element['dataScopePrefix'] = $this->moduleConfig->getDataScopePrefix("billingAddress", $method);
                $element['children']['form-fields']['children']['telephone'] = $this->moduleConfig->telephoneFieldConfig("billingAddress", $method);
            }
        }

        /* config: checkout/options/display_billing_address_on = payment_page */
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form'])) {
            $method = 'shared';

            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['telephone'] = $this->moduleConfig->telephoneFieldConfig("billingAddress", $method);
        }
        // @codingStandardsIgnoreEnd

        return $jsLayout;
    }

    /**
     * Checks if current customer is logged in and has a stored shipping address.
     *
     * @return bool
     */
    private function currentCustomerHasStoredShippingAddress()
    {
        return $this->customerSession->isLoggedIn() && $this->currentCustomerAddress->getDefaultShippingAddress();
    }

    /**
     * Add consent components to the XML tree.
     *
     * @param array $layoutNode
     * @param string|int $storeId
     * @return void
     */
    private function appendConsentLayout(&$layoutNode, $storeId)
    {
        if (!$this->moduleConfig->isSmsConsentEnabled($storeId)) {
            return;
        }

        $layoutNode['dd_sms_consent_checkbox'] = $this->consentCheckbox->render($storeId);
        $layoutNode['dd_sms_consent_telephone'] = $this->consentTelephone->render();
        $layoutNode['dd_sms_consent_text'] = $this->consentText->render($storeId);
    }
}
