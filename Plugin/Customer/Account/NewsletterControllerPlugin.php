<?php

namespace Dotdigitalgroup\Sms\Plugin\Customer\Account;

use Dotdigitalgroup\Email\Controller\Customer\Newsletter;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory;
use Dotdigitalgroup\Sms\Model\Importer\Enqueuer;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Dotdigitalgroup\Sms\Model\Consent\ConsentManager;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
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
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var CollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Enqueuer
     */
    private $importerEnqueuer;

    /**
     * @var ConsentManager
     */
    private $consentManager;

    /**
     * @param Data $dataHelper
     * @param Logger $logger
     * @param ContactResource $contactResource
     * @param CollectionFactory $contactCollectionFactory
     * @param Session $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param StoreManagerInterface $storeManager
     * @param Enqueuer $importerEnqueuer
     * @param ConsentManager $consentManager
     * @param Context $context
     */
    public function __construct(
        Data $dataHelper,
        Logger $logger,
        ContactResource $contactResource,
        CollectionFactory $contactCollectionFactory,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        StoreManagerInterface $storeManager,
        Enqueuer $importerEnqueuer,
        ConsentManager $consentManager,
        Context $context
    ) {
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->contactResource = $contactResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->storeManager = $storeManager;
        $this->importerEnqueuer = $importerEnqueuer;
        $this->consentManager = $consentManager;
        $this->request = $context->getRequest();
    }

    /**
     * After execute.
     *
     * @param Newsletter $subject
     * @param Redirect $result
     *
     * @return Redirect
     * @throws LocalizedException
     */
    public function afterExecute(Newsletter $subject, Redirect $result): Redirect
    {
        if (! $this->formKeyValidator->validate($this->request)) {
            return $result;
        }

        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        if (!$this->dataHelper->isEnabled($websiteId)) {
            return $result;
        }
        $hasProvidedConsent = $this->request->getParam('is_sms_subscribed') ?: false;
        $consentMobileNumber = $this->request->getParam('mobile_number') ?
            str_replace(' ', '', $this->request->getParam('mobile_number')) :
            '';
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
                    $contactModel,
                    $hasProvidedConsent,
                    $consentMobileNumber
                )) {
                    $contactModel->setMobileNumber($consentMobileNumber);
                    $contactModel->setSmsSubscriberStatus(Subscriber::STATUS_SUBSCRIBED);
                    $contactModel->setSmsSubscriberImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
                    $this->contactResource->save($contactModel);

                    $this->consentManager->createConsentRecord($contactModel->getId(), $storeId);

                } elseif ($this->contactHasJustOptedOut(
                    $contactModel,
                    $hasProvidedConsent
                )) {
                    $this->importerEnqueuer->enqueueUnsubscribe(
                        $contactModel->getContactId(),
                        $customerEmail,
                        $websiteId
                    );
                    $contactModel->setMobileNumber($consentMobileNumber);
                    $contactModel->setSmsSubscriberStatus(Subscriber::STATUS_UNSUBSCRIBED);
                    $this->contactResource->save($contactModel);

                } elseif ($this->contactMobileNumberIsUpdated(
                    $contactModel,
                    $consentMobileNumber
                )) {
                    $contactModel->setMobileNumber($consentMobileNumber);
                    $contactModel->setSmsSubscriberImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
                    $this->contactResource->save($contactModel);
                }
            }

        } catch (LocalizedException|\Exception $e) {
            $this->logger->debug('Error when handling SMS subscription', [(string) $e]);
        }

        return $result;
    }

    /**
     * Determine if a contact has just opted IN.
     *
     * @param Contact $contact
     * @param bool|null $hasProvidedConsent
     * @param string $consentMobileNumber
     *
     * @return bool
     */
    private function contactHasJustOptedIn(
        Contact $contact,
        ?bool $hasProvidedConsent,
        string $consentMobileNumber
    ): bool {
        return $contact->getSmsSubscriberStatus() != Subscriber::STATUS_SUBSCRIBED &&
            $hasProvidedConsent &&
            $consentMobileNumber;
    }

    /**
     * Determine if a previously opted-in contact has just opted OUT.
     *
     * @param Contact $contact
     * @param bool|null $hasProvidedConsent
     *
     * @return bool
     */
    private function contactHasJustOptedOut(Contact $contact, ?bool $hasProvidedConsent): bool
    {
        return $contact->getSmsSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED && !$hasProvidedConsent;
    }

    /**
     * Status has not changed, but mobile number has been updated.
     *
     * @param Contact $contact
     * @param string $consentMobileNumber
     *
     * @return bool
     */
    private function contactMobileNumberIsUpdated(Contact $contact, string $consentMobileNumber): bool
    {
        return $contact->getMobileNumber() != $consentMobileNumber;
    }
}
