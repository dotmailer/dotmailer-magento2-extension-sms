<?php

namespace Dotdigitalgroup\Sms\Plugin\Customer\Account;

use Dotdigitalgroup\Email\Controller\Customer\Newsletter;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class NewsletterControllerPlugin
{
    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var CollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Data $dataHelper
     * @param Logger $logger
     * @param ContactFactory $contactFactory
     * @param ContactResource $contactResource
     * @param CollectionFactory $contactCollectionFactory
     * @param Configuration $config
     * @param Session $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $dataHelper,
        Logger $logger,
        ContactFactory $contactFactory,
        ContactResource $contactResource,
        CollectionFactory $contactCollectionFactory,
        Configuration $config,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        StoreManagerInterface $storeManager,
    ) {
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->contactFactory = $contactFactory;
        $this->contactResource = $contactResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->storeManager = $storeManager;
    }

    /**
     * After execute.
     *
     * @param Newsletter $subject
     * @param ResponseInterface $result
     *
     * @return \Magento\Framework\App\ResponseInterface
     * @throws LocalizedException
     */
    public function afterExecute(Newsletter $subject, $result)
    {
        if (! $this->formKeyValidator->validate($subject->getRequest())) {
            return $result;
        }

        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        if (!$this->dataHelper->isEnabled($websiteId)) {
            return $result;
        }
        $hasProvidedConsent = $subject->getRequest()->getParam('is_sms_subscribed');
        $consentMobileNumber = $subject->getRequest()->getParam('mobile_number');

        if ($hasProvidedConsent && !$consentMobileNumber) {
            return $result;
        }

        $storeId = $this->storeManager->getStore()->getId();
        $customerEmail = $this->customerSession->getCustomer()->getEmail();

        try {
            $contactModel = $this->contactCollectionFactory->create()
                ->loadByCustomerEmail(
                    $customerEmail,
                    $websiteId
                );

            if ($contactModel) {
                if ($this->contactHasJustOptedIn(
                    $hasProvidedConsent,
                    $consentMobileNumber
                )) {
                    $contactModel->setMobileNumber($consentMobileNumber);
                    $contactModel->setSmsSubscriberStatus(Subscriber::STATUS_SUBSCRIBED);
                }

                if ($this->contactHasJustOptedOut(
                    $contactModel,
                    $hasProvidedConsent
                )) {
                    $contactModel->setSmsSubscriberStatus(Subscriber::STATUS_UNSUBSCRIBED);
                }
            } else {
                $contactModel = $this->contactFactory->create()
                    ->setEmail($customerEmail)
                    ->setMobileNumber($consentMobileNumber)
                    ->setSmsSubscriberStatus(Subscriber::STATUS_SUBSCRIBED)
                    ->setWebsiteId($websiteId)
                    ->setStoreId($storeId);
            }

            $contactModel->setSmsSubscriberImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
            $this->contactResource->save($contactModel);
        } catch (LocalizedException|\Exception $e) {
            $this->logger->debug('Error when updating email_contact table', [(string) $e]);
        }

        return $result;
    }

    /**
     * Determine if a contact has just opted IN.
     *
     * @param bool|null $hasProvidedConsent
     * @param string $consentMobileNumber
     *
     * @return bool
     */
    private function contactHasJustOptedIn(?bool $hasProvidedConsent, $consentMobileNumber)
    {
        return $hasProvidedConsent && $consentMobileNumber;
    }

    /**
     * Determine if a previously opted-in contact has just opted OUT.
     *
     * @param Contact $contact
     * @param bool|null $hasProvidedConsent
     *
     * @return bool
     */
    private function contactHasJustOptedOut(Contact $contact, ?bool $hasProvidedConsent)
    {
        return $contact->getSmsSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED && !$hasProvidedConsent;
    }
}
