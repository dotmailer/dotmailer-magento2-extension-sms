<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Plugin\Customer\Account;

use Dotdigitalgroup\Email\Controller\Customer\Newsletter;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Consent\ConsentManager;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsSubscriptionDataFactory;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
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
     * @var ConsentManager
     */
    private $consentManager;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var SmsSubscriptionDataFactory
     */
    private $smsSubscriptionDataFactory;

    /**
     * @var SmsMessagePublisher
     */
    private $smsMessagePublisher;

    /**
     * @param Data $dataHelper
     * @param Logger $logger
     * @param ContactResource $contactResource
     * @param CollectionFactory $contactCollectionFactory
     * @param Session $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param StoreManagerInterface $storeManager
     * @param ConsentManager $consentManager
     * @param SmsSubscriptionDataFactory $smsSubscriptionDataFactory
     * @param PublisherInterface $publisher
     * @param SmsMessagePublisher $smsMessagePublisher
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
        ConsentManager $consentManager,
        SmsSubscriptionDataFactory $smsSubscriptionDataFactory,
        PublisherInterface $publisher,
        SmsMessagePublisher $smsMessagePublisher,
        Context $context
    ) {
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->contactResource = $contactResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->storeManager = $storeManager;
        $this->consentManager = $consentManager;
        $this->smsSubscriptionDataFactory = $smsSubscriptionDataFactory;
        $this->publisher = $publisher;
        $this->smsMessagePublisher = $smsMessagePublisher;
        $this->request = $context->getRequest();
    }

    /**
     * After execute.
     *
     * @param Newsletter $subject
     * @param Redirect $result
     *
     * @return Redirect
     * @throws NoSuchEntityException
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
        $hasProvidedConsent = (bool) $this->request->getParam('is_sms_subscribed');
        $consentMobileNumber = $this->request->getParam('mobile_number') ?
            str_replace(' ', '', $this->request->getParam('mobile_number')) :
            '';
        if ($hasProvidedConsent && !$consentMobileNumber) {
            return $result;
        }

        $storeId = $this->storeManager->getStore()->getId();
        $customer = $this->customerSession->getCustomer();

        try {
            $contactModel = $this->contactCollectionFactory->create()
                ->loadByCustomerEmail(
                    $customer->getEmail(),
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

                    // Publish SMS subscription message
                    $this->publisher->publish(
                        Subscriber::TOPIC_SMS_SUBSCRIPTION,
                        $this->smsSubscriptionDataFactory->create()
                            ->setWebsiteId((int) $websiteId)
                            ->setContactId((int) $contactModel->getId())
                            ->setType('subscribe')
                    );

                    // Publish SMS signup message
                    $this->smsMessagePublisher->publish(
                        ConfigInterface::SMS_TYPE_SIGN_UP,
                        [
                            'websiteId' => (int) $websiteId,
                            'storeId' => (int) $storeId,
                            'mobileNumber' => $consentMobileNumber,
                            'email' => $customer->getEmail(),
                            'firstName' => $customer->getData('firstname'),
                            'lastName' => $customer->getData('lastname')
                        ]
                    );

                } elseif ($this->contactHasJustOptedOut(
                    $contactModel,
                    $hasProvidedConsent
                )) {

                    $contactMessage = $this->smsSubscriptionDataFactory->create();
                    $contactMessage->setWebsiteId((int) $websiteId);
                    $contactMessage->setEmail($customer->getEmail());
                    $contactMessage->setType('unsubscribe');

                    $this->publisher->publish(
                        Subscriber::TOPIC_SMS_SUBSCRIPTION,
                        $contactMessage
                    );

                    $contactModel->setMobileNumber($consentMobileNumber);
                    $contactModel->setSmsSubscriberStatus(Subscriber::STATUS_UNSUBSCRIBED);
                    $this->contactResource->save($contactModel);

                } elseif ($this->contactSubscribedMobileNumberIsUpdated(
                    $contactModel,
                    $consentMobileNumber
                )) {
                    $this->publisher->publish(
                        Subscriber::TOPIC_SMS_SUBSCRIPTION,
                        $this->smsSubscriptionDataFactory->create()
                            ->setWebsiteId((int) $websiteId)
                            ->setContactId((int) $contactModel->getId())
                            ->setType('subscribe')
                    );

                    $contactModel->setMobileNumber($consentMobileNumber);
                    $contactModel->setSmsSubscriberImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
                    $this->contactResource->save($contactModel);
                } elseif ($this->contactMobileNumberIsUpdated(
                    $contactModel,
                    $consentMobileNumber
                )) {
                    $contactModel->setMobileNumber($consentMobileNumber);
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
     * Status has not changed, but contact is subscribed, and mobile number has been updated.
     *
     * @param Contact $contact
     * @param string $consentMobileNumber
     *
     * @return bool
     */
    private function contactSubscribedMobileNumberIsUpdated(Contact $contact, string $consentMobileNumber): bool
    {
        return $contact->getSmsSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED &&
            $contact->getMobileNumber() != $consentMobileNumber;
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
