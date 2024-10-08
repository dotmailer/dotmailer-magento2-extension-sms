<?php

namespace Dotdigitalgroup\Sms\ViewModel\Customer\Account;

use Dotdigitalgroup\Email\Model\Consent;
use Dotdigitalgroup\Email\ViewModel\Customer\AccountSubscriptions;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory as ContactCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * MarketingConsent constructor.
     *
     * @param Consent $consent
     * @param AccountSubscriptions $containerViewModel
     * @param Configuration $moduleConfig
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Consent $consent,
        AccountSubscriptions $containerViewModel,
        Configuration $moduleConfig,
        ContactCollectionFactory $contactCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->consent = $consent;
        $this->containerViewModel = $containerViewModel;
        $this->moduleConfig = $moduleConfig;
        $this->contactCollectionFactory = $contactCollectionFactory;
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
     * @throws NoSuchEntityException
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
     * @throws NoSuchEntityException
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
     * The SmsContact does the transformation of mobile number from the table. Therefore
     * we can't rely on the Email-based contact from table, we need a new one from our
     * overloaded collection.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getStoredMobileNumber()
    {
        $emailContactModel = $this->containerViewModel->getContactFromTable();
        if (!$emailContactModel) {
            return '';
        }
        $smsContactModel = $this->contactCollectionFactory->create()
            ->loadByCustomerEmail(
                $emailContactModel->getEmail(),
                $emailContactModel->getWebsiteId()
            );
        return $smsContactModel ? $smsContactModel->getMobileNumber() : '';
    }

    /**
     * Is validation enabled.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isPhoneNumberValidationEnabled()
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->moduleConfig->isPhoneNumberValidationEnabled($storeId) ? 'true' : 'false';
    }

    /**
     * Should validate phone number with checkbox.
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function shouldValidatePhoneNumberWithCheckbox()
    {
        $storeId = $this->storeManager->getStore()->getId();
        return (
                $this->moduleConfig->isSmsConsentRegistrationEnabled($storeId)
                ||
                $this->moduleConfig->isSmsConsentAccountEnabled($storeId)
            ) &&
            $this->moduleConfig->isPhoneNumberValidationEnabled($storeId);
    }
}
