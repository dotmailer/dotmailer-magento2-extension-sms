<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\Queue\Item\SmsSignup;
use Dotdigitalgroup\Sms\Model\Queue\Item\TransactionalMessageEnqueuer;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsSubscriptionDataFactory;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory;
use Dotdigitalgroup\Sms\Model\SmsContactFactory;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Dotdigitalgroup\Sms\Model\Consent\ConsentManager;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Observer for storing consent at checkout.
 */
class CheckoutConsentObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SmsContactFactory
     */
    private $contactFactory;

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
     * @var CheckoutSession
     */
    private $checkoutSession;

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
     * CheckoutConsentObserver constructor.
     *
     * @param Logger $logger
     * @param SmsContactFactory $contactFactory
     * @param ContactResource $contactResource
     * @param SmsSignup $smsSignupQueueItem
     * @param TransactionalMessageEnqueuer $transactionalMessageEnqueuer
     * @param CollectionFactory $contactCollectionFactory
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param ConsentManager $consentManager
     * @param PublisherInterface $publisher
     * @param SmsSubscriptionDataFactory $smsSubscriptionDataFactory
     */
    public function __construct(
        Logger $logger,
        SmsContactFactory $contactFactory,
        ContactResource $contactResource,
        SmsSignup $smsSignupQueueItem,
        TransactionalMessageEnqueuer $transactionalMessageEnqueuer,
        CollectionFactory $contactCollectionFactory,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        ConsentManager $consentManager,
        PublisherInterface $publisher,
        SmsSubscriptionDataFactory $smsSubscriptionDataFactory,
    ) {
        $this->logger = $logger;
        $this->contactFactory = $contactFactory;
        $this->contactResource = $contactResource;
        $this->smsSignupQueueItem = $smsSignupQueueItem;
        $this->transactionalMessageEnqueuer = $transactionalMessageEnqueuer;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->consentManager = $consentManager;
        $this->smsSubscriptionDataFactory = $smsSubscriptionDataFactory;
        $this->storeManager = $storeManager;
        $this->publisher = $publisher;
    }

    /**
     * Observer for converting quote data to order
     *
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (false === $this->checkoutSession->getData('dd_sms_consent_checkbox') ||
            empty($this->checkoutSession->getData('dd_sms_consent_telephone'))) {
            return $this;
        }

        try {
            $order = $observer->getEvent()->getOrder();
            $websiteId = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();
            $storeId = $order->getStoreId();
            $consentMobileNumber = $this->checkoutSession->getData('dd_sms_consent_telephone');

            $contactModel = $this->contactCollectionFactory->create()
                ->loadByCustomerEmail(
                    $order->getCustomerEmail(),
                    $websiteId
                );

            if ($contactModel && $contactModel->getId()) {
                $contactModel->setMobileNumber($consentMobileNumber);
                $contactModel->setSmsSubscriberStatus(Subscriber::STATUS_SUBSCRIBED);
            } else {
                $contactModel = $this->contactFactory->create()
                    ->setEmail($order->getCustomerEmail())
                    ->setMobileNumber($consentMobileNumber)
                    ->setSmsSubscriberStatus(Subscriber::STATUS_SUBSCRIBED)
                    ->setWebsiteId($websiteId)
                    ->setStoreId($order->getStoreId());
            }

            $contactModel->setSmsSubscriberImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
            $this->contactResource->save($contactModel);
            $this->consentManager->createConsentRecord($contactModel->getId(), $storeId);
            $this->publisher->publish(
                'ddg.sms.subscription',
                $this->smsSubscriptionDataFactory->create()
                    ->setWebsiteId((int) $contactModel->getData('website_id'))
                    ->setContactId((int) $contactModel->getId())
                    ->setType('subscribe')
            );

            if ($this->transactionalMessageEnqueuer->canQueue($this->smsSignupQueueItem, (int) $storeId)) {
                $this->smsSignupQueueItem->prepare(
                    $consentMobileNumber,
                    $order->getCustomerEmail(),
                    (int) $websiteId,
                    (int) $storeId,
                    $this->findFirstName($order),
                    $this->findLastName($order)
                );
                $this->transactionalMessageEnqueuer->queue(
                    $this->smsSignupQueueItem
                );
            }
        } catch (LocalizedException|\Exception $e) {
            $this->logger->debug((string) $e);
        }

        return $this;
    }

    /**
     * Find first name.
     *
     * @param OrderInterface $order
     *
     * @return string|null
     */
    private function findFirstName(OrderInterface $order)
    {
        /** @var \Magento\Sales\Model\Order $order */
        return $order->getCustomerFirstname() !== null ?
            $order->getCustomerFirstname():
            $order->getShippingAddress()->getFirstName();
    }

    /**
     * Find last name.
     *
     * @param OrderInterface $order
     *
     * @return string|null
     */
    private function findLastName(OrderInterface $order)
    {
        /** @var \Magento\Sales\Model\Order $order */
        return $order->getCustomerLastname() !== null ?
            $order->getCustomerLastname():
            $order->getShippingAddress()->getLastName();
    }
}
