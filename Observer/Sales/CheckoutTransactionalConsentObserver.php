<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\OrderFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order as EmailOrderResource;
use Dotdigitalgroup\Sms\Model\Consent\ConsentManager;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Observer for storing consent at checkout.
 */
class CheckoutTransactionalConsentObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var OrderFactory
     */
    private $emailOrderFactory;

    /**
     * @var EmailOrderResource
     */
    private $emailOrderResource;

    /**
     * @var CollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConsentManager
     */
    private $consentManager;

    /**
     * CheckoutTransactionalConsentObserver constructor.
     *
     * @param Logger $logger
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $emailOrderFactory
     * @param EmailOrderResource $emailOrderResource
     * @param CollectionFactory $contactCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ConsentManager $consentManager
     */
    public function __construct(
        Logger $logger,
        CheckoutSession $checkoutSession,
        OrderFactory $emailOrderFactory,
        EmailOrderResource $emailOrderResource,
        CollectionFactory $contactCollectionFactory,
        StoreManagerInterface $storeManager,
        ConsentManager $consentManager
    ) {
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->emailOrderFactory = $emailOrderFactory;
        $this->emailOrderResource = $emailOrderResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->consentManager = $consentManager;
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
        try {
            $hasTransactionalConsent = $this->checkoutSession->getData('dd_sms_transactional_consent_checkbox');

            if ($hasTransactionalConsent === null) {
                return $this;
            }

            $order = $observer->getEvent()->getOrder();

            if (!$order || !$order->getId()) {
                $this->logger->warning('No valid order found in observer event.');
                return $this;
            }
            // Prevent processing for order updates
            if ($order->getOrigData('entity_id') !== null) {
                return $this;
            }

            $emailOrder = $this->emailOrderFactory->create()
                ->loadOrCreateOrder($order->getId(), $order->getQuoteId());

            $emailOrder->setData('sms_transactional_requires_opt_in', 1);
            $emailOrder->setData('sms_transactional_opt_in', ($hasTransactionalConsent) ? 1 : 0);

            $this->emailOrderResource->save($emailOrder);

            if ($hasTransactionalConsent) {
                $websiteId = $this->storeManager->getStore($order->getStoreId())->getWebsiteId();
                $storeId = $order->getStoreId();
                $contactModel = $this->contactCollectionFactory->create()
                ->loadByCustomerEmail(
                    $order->getCustomerEmail(),
                    $websiteId
                );

                if ($contactModel && $contactModel->getId()) {
                    $this->consentManager
                    ->setTransactionalConsent(true)
                    ->createConsentRecord($contactModel->getId(), $storeId);
                } else {
                    $this->logger->debug(
                        'Failed to create consent record, contact not found.',
                        ['email' => $order->getCustomerEmail() ,'websiteId' => $websiteId]
                    );
                }
            }

        } catch (LocalizedException $e) {
            $this->logger->error('Error saving SMS transactional consent: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error saving SMS transactional consent: ' . $e->getMessage());
        } finally {
            return $this;
        }
    }
}
