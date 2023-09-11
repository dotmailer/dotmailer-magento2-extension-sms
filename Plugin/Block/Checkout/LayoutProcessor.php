<?php

namespace Dotdigitalgroup\Sms\Plugin\Block\Checkout;

use Dotdigitalgroup\Sms\Component\ConsentCollapseGroup;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Checkout\Block\Checkout\LayoutProcessor as MageLayoutProcessor;
use Magento\Customer\Helper\Session\CurrentCustomerAddress;
use Magento\Customer\Model\Session;
use Magento\Store\Model\StoreManagerInterface;

class LayoutProcessor
{
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
     * @var ConsentCollapseGroup
     */
    private $consentCollapseGroup;

    /**
     * LayoutProcessor constructor.
     *
     * @param Configuration $moduleConfig
     * @param Session $customerSession
     * @param CurrentCustomerAddress $currentCustomerAddress
     * @param StoreManagerInterface $storeManager
     * @param ConsentCollapseGroup $consentCollapseGroup
     */
    public function __construct(
        Configuration $moduleConfig,
        Session $customerSession,
        CurrentCustomerAddress $currentCustomerAddress,
        StoreManagerInterface $storeManager,
        ConsentCollapseGroup $consentCollapseGroup
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->customerSession = $customerSession;
        $this->currentCustomerAddress = $currentCustomerAddress;
        $this->storeManager = $storeManager;
        $this->consentCollapseGroup = $consentCollapseGroup;
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

        // @codingStandardsIgnoreStart
        $shippingConfiguration = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];
        $billingConfiguration = &$jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'];
        $shippingSelection = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['before-form']['children'];

        if (isset($shippingConfiguration)) {
            if ($this->moduleConfig->isPhoneNumberValidationEnabled($storeId)) {
                $shippingConfiguration['telephone'] = $this->moduleConfig->telephoneFieldConfig("shippingAddress");
            }

            if (!$this->currentCustomerHasStoredShippingAddress()) {
                $this->appendConsentLayout($shippingConfiguration, $storeId);
            }
        }

        if (isset($shippingSelection)) {
            if ($this->moduleConfig->isPhoneNumberValidationEnabled($storeId)) {
                $shippingSelection['dd-telephone-resubmission-form'] = $this->moduleConfig->getResubmissionForm();
            }

            if ($this->currentCustomerHasStoredShippingAddress()) {
                $this->appendConsentLayout($shippingSelection, $storeId);
            }
        }

        if (!$this->moduleConfig->isPhoneNumberValidationEnabled($storeId)) {
            return $jsLayout;
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

        $layoutNode['dd_sms_consent_collapse_group'] = $this->consentCollapseGroup->render();
    }
}
