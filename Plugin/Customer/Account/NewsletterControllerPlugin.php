<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Plugin\Customer\Account;

use Dotdigitalgroup\Email\Controller\Customer\Newsletter;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\Queue\Item\SmsSignup;
use Dotdigitalgroup\Sms\Model\Queue\Item\TransactionalMessageEnqueuer;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Dotdigitalgroup\Sms\Model\Consent\ConsentManager;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Dotdigitalgroup\Sms\Model\Queue\Message\MarketingSmsSubscribeDataFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Dotdigitalgroup\Sms\Model\Queue\Message\MarketingSmsUnsubscribeDataFactory;

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
     * @var SmsSignup
     */
    private $smsSignupQueueItem;

    /**
     * @var TransactionalMessageEnqueuer
     */
    private $transactionalMessageEnqueuer;

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
     * @var MarketingSmsUnsubscribeDataFactory
     */
    private $marketingSmsUnsubscribeDataFactory;

    /**
     * @var MarketingSmsSubscribeDataFactory
     */
    private $marketingSmsSubscribeDataFactory;

    /**
     * NewsletterControllerPlugin constructor.
     *
     * @param Data $dataHelper
     * @param Logger $logger
     * @param ContactResource $contactResource
     * @param SmsSignup $smsSignupQueueItem
     * @param TransactionalMessageEnqueuer $transactionalMessageEnqueuer
     * @param CollectionFactory $contactCollectionFactory
     * @param Session $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param StoreManagerInterface $storeManager
     * @param ConsentManager $consentManager
     * @param MarketingSmsSubscribeDataFactory $marketingSmsSubscribeDataFactory
     * @param MarketingSmsUnsubscribeDataFactory $marketingSmsUnsubscribeDataFactory
     * @param PublisherInterface $publisher
     * @param Context $context
     */
    public function __construct(
        Data $dataHelper,
        Logger $logger,
        ContactResource $contactResource,
        SmsSignup $smsSignupQueueItem,
        TransactionalMessageEnqueuer $transactionalMessageEnqueuer,
        CollectionFactory $contactCollectionFactory,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        StoreManagerInterface $storeManager,
        ConsentManager $consentManager,
        MarketingSmsSubscribeDataFactory $marketingSmsSubscribeDataFactory,
        MarketingSmsUnsubscribeDataFactory $marketingSmsUnsubscribeDataFactory,
        PublisherInterface $publisher,
        Context $context
    ) {
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->contactResource = $contactResource;
        $this->smsSignupQueueItem = $smsSignupQueueItem;
        $this->transactionalMessageEnqueuer = $transactionalMessageEnqueuer;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->storeManager = $storeManager;
        $this->consentManager = $consentManager;
        $this->marketingSmsSubscribeDataFactory = $marketingSmsSubscribeDataFactory;
        $this->marketingSmsUnsubscribeDataFactory = $marketingSmsUnsubscribeDataFactory;
        $this->publisher = $publisher;
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
                    $this->publisher->publish(
                        'ddg.sms.subscribe',
                        $this->marketingSmsSubscribeDataFactory->create()
                            ->setWebsiteId((int) $websiteId)
                            ->setContactId((int) $contactModel->getId())
                    );

                    if ($this->transactionalMessageEnqueuer->canQueue($this->smsSignupQueueItem, (int) $storeId)) {
                        $this->smsSignupQueueItem->prepare(
                            $consentMobileNumber,
                            $customer->getEmail(),
                            (int) $websiteId,
                            (int) $storeId,
                            $customer->getData('firstname'),
                            $customer->getData('lastname')
                        );
                        $this->transactionalMessageEnqueuer->queue(
                            $this->smsSignupQueueItem
                        );
                    }

                } elseif ($this->contactHasJustOptedOut(
                    $contactModel,
                    $hasProvidedConsent
                )) {

                    $contactMessage = $this->marketingSmsUnsubscribeDataFactory->create();
                    $contactMessage->setWebsiteId($websiteId);
                    $contactMessage->setEmail($customer->getEmail());

                    $this->publisher->publish(
                        'ddg.sms.unsubscribe',
                        $contactMessage
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
