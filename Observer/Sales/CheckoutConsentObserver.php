<?php

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory;
use Dotdigitalgroup\Sms\Model\SmsContactFactory;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Dotdigitalgroup\Sms\Model\Consent\ConsentManager;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
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
     * @param Logger $logger
     * @param SmsContactFactory $contactFactory
     * @param ContactResource $contactResource
     * @param CollectionFactory $contactCollectionFactory
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param ConsentManager $consentManager
     */
    public function __construct(
        Logger $logger,
        SmsContactFactory $contactFactory,
        ContactResource $contactResource,
        CollectionFactory $contactCollectionFactory,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        ConsentManager $consentManager
    ) {
        $this->logger = $logger;
        $this->contactFactory = $contactFactory;
        $this->contactResource = $contactResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->consentManager = $consentManager;
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

            if ($contactModel->getId()) {
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
        } catch (LocalizedException|\Exception $e) {
            $this->logger->debug((string) $e);
        }

        return $this;
    }
}
