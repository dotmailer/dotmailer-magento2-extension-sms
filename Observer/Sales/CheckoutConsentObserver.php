<?php

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Dotdigitalgroup\Sms\Model\Subscriber;
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
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,
        ContactFactory $contactFactory,
        ContactResource $contactResource,
        CollectionFactory $contactCollectionFactory,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->contactFactory = $contactFactory;
        $this->contactResource = $contactResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
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
            $consentMobileNumber = $this->checkoutSession->getData('dd_sms_consent_telephone');

            $contactModel = $this->contactCollectionFactory->create()
                ->loadByCustomerEmail(
                    $order->getCustomerEmail(),
                    $websiteId
                );

            if ($contactModel) {
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

            $contactModel->setSmsImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
            $this->contactResource->save($contactModel);
        } catch (LocalizedException|\Exception $e) {
            $this->logger->debug((string) $e);
        }

        return $this;
    }
}
