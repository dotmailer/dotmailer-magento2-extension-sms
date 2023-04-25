<?php

namespace Dotdigitalgroup\Sms\ViewModel\Customer\Account;

use Dotdigitalgroup\Email\Model\Consent;
use Dotdigitalgroup\Email\ViewModel\Customer\AccountSubscriptions;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class MarketingConsent implements ArgumentInterface
{
    /**
     * @var Consent
     */
    private $consent;

    /**
     * @var AccountSubscriptions
     */
    private $containerViewModel;

    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Consent $consent
     * @param AccountSubscriptions $containerViewModel
     * @param Configuration $moduleConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Consent $consent,
        AccountSubscriptions $containerViewModel,
        Configuration $moduleConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->consent = $consent;
        $this->containerViewModel = $containerViewModel;
        $this->moduleConfig = $moduleConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Get customer consent text.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getCustomerConsentText(): string
    {
        $websiteId = $this->storeManager->getWebsite()->getId();
        return $this->consent->getConsentCustomerText($websiteId) ?: '';
    }

    /**
     * Get SMS signup text.
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSmsSignUpText()
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->moduleConfig->getSmsSignUpText($storeId);
    }

    /**
     * Get SMS marketing consent text.
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSmsMarketingConsentText()
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->moduleConfig->getSmsMarketingConsentText($storeId);
    }

    /**
     * Is subscribed.
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isSubscribed()
    {
        $contact = $this->containerViewModel->getContactFromTable();
        return $contact && $contact->getSmsSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED;
    }

    /**
     * Get stored mobile number from contact table.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getStoredMobileNumber()
    {
        $contact = $this->containerViewModel->getContactFromTable();
        return $contact ? $contact->getMobileNumber() : '';
    }

    /**
     * Is validation enabled.
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isPhoneNumberValidationEnabled()
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->moduleConfig->isPhoneNumberValidationEnabled($storeId) ? 'true' : 'false';
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function shouldValidatePhoneNumberWithCheckbox()
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->moduleConfig->isSmsConsentEnabled($storeId) &&
            $this->moduleConfig->isPhoneNumberValidationEnabled($storeId);
    }
}
