<?php

namespace Dotdigitalgroup\Sms\Plugin\Checkout;

use Dotdigitalgroup\Email\Logger\Logger;
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
     * @var Logger
     */
    private $logger;

    /**
     * @param ShippingInformationExtensionFactory $shippingInformationExtensionFactory
     * @param CheckoutSession $checkoutSession
     * @param Logger $logger
     */
    public function __construct(
        ShippingInformationExtensionFactory $shippingInformationExtensionFactory,
        CheckoutSession $checkoutSession,
        Logger $logger
    ) {
        $this->shippingInformationExtensionFactory = $shippingInformationExtensionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * Before save address information.
     *
     * Extracts SMS consent data from extension attributes and stores in checkout session.
     * Gracefully handles cases where extension attributes may not be available due to
     * setup:di:compile not having been run.
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

        if (method_exists($shippingExtensionAttributes, 'getDdSmsMarketingConsentCheckbox')) {
            $this->checkoutSession->setData(
                'dd_sms_marketing_consent_checkbox',
                $shippingExtensionAttributes->getDdSmsMarketingConsentCheckbox()
            );
        } else {
            $this->logger->warning('ShippingInformationManagementPlugin: getDdSmsMarketingConsentCheckbox is not available on extension attributes. Please run setup:di:compile.');
        }

        if (method_exists($shippingExtensionAttributes, 'getDdSmsMarketingConsentTelephone')) {
            $this->checkoutSession->setData(
                'dd_sms_marketing_consent_telephone',
                $shippingExtensionAttributes->getDdSmsMarketingConsentTelephone()
            );
        } else {
            $this->logger->warning('ShippingInformationManagementPlugin: getDdSmsMarketingConsentTelephone is not available on extension attributes. Please run setup:di:compile.');
        }

        if (method_exists($shippingExtensionAttributes, 'getDdSmsTransactionalConsentCheckbox')) {
            $this->checkoutSession->setData(
                'dd_sms_transactional_consent_checkbox',
                $shippingExtensionAttributes->getDdSmsTransactionalConsentCheckbox()
            );
        } else {
            $this->logger->warning('ShippingInformationManagementPlugin: getDdSmsTransactionalConsentCheckbox is not available on extension attributes. Please run setup:di:compile.');
        }

        return null;
    }
}
