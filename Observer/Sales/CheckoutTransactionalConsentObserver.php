<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Observer\Sales;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\OrderFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Order as EmailOrderResource;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

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
     * CheckoutTransactionalConsentObserver constructor.
     *
     * @param Logger $logger
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $emailOrderFactory
     * @param EmailOrderResource $emailOrderResource
     */
    public function __construct(
        Logger $logger,
        CheckoutSession $checkoutSession,
        OrderFactory $emailOrderFactory,
        EmailOrderResource $emailOrderResource
    ) {
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->emailOrderFactory = $emailOrderFactory;
        $this->emailOrderResource = $emailOrderResource;
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

            $emailOrder = $this->emailOrderFactory->create()
                ->loadOrCreateOrder($order->getId(), $order->getQuoteId());

            $emailOrder->setData('sms_transactional_requires_opt_in', $hasTransactionalConsent ? 1 : 0);
            $emailOrder->setData('sms_transactional_opt_in', 1);

            $this->emailOrderResource->save($emailOrder);

        } catch (LocalizedException $e) {
            $this->logger->error('Error saving SMS transactional consent: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error saving SMS transactional consent: ' . $e->getMessage());
        } finally {
            return $this;
        }
    }
}
