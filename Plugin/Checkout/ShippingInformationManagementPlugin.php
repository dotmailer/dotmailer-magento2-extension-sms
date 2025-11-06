<?php

namespace Dotdigitalgroup\Sms\Plugin\Checkout;

use Magento\Checkout\Api\Data\ShippingInformationExtensionFactory;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Checkout\Model\Session as CheckoutSession;

class ShippingInformationManagementPlugin
{
    /**
     * @var ShippingInformationExtensionFactory
     */
    private $shippingInformationExtensionFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param ShippingInformationExtensionFactory $shippingInformationExtensionFactory
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        ShippingInformationExtensionFactory $shippingInformationExtensionFactory,
        CheckoutSession $checkoutSession
    ) {
        $this->shippingInformationExtensionFactory = $shippingInformationExtensionFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Before save address information.
     *
     * @param ShippingInformationManagement $subject
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     *
     * @return null
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        if (!$shippingExtensionAttributes = $addressInformation->getExtensionAttributes()) {
            $shippingExtensionAttributes = $this->shippingInformationExtensionFactory->create();
        }

        $this->checkoutSession->setData(
            'dd_sms_marketing_consent_checkbox',
            $shippingExtensionAttributes->getDdSmsMarketingConsentCheckbox()
        );
        $this->checkoutSession->setData(
            'dd_sms_marketing_consent_telephone',
            $shippingExtensionAttributes->getDdSmsMarketingConsentTelephone()
        );
        $this->checkoutSession->setData(
            'dd_sms_transactional_consent_checkbox',
            $shippingExtensionAttributes->getDdSmsTransactionalConsentCheckbox()
        );

        return null;
    }
}
