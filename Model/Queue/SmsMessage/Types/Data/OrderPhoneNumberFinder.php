<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue\SmsMessage\Types\Data;

use Magento\Sales\Api\Data\OrderInterface;

class OrderPhoneNumberFinder
{
    /**
     * Get order phone number.
     *
     * @param OrderInterface $order
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getPhoneNumber(OrderInterface $order): string
    {
        /** @var \Magento\Sales\Model\Order $order */
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $phoneNumber = null;
        if ($shippingAddress && $shippingAddress->getTelephone()) {
            $phoneNumber = $shippingAddress->getTelephone();
        } elseif ($billingAddress && $billingAddress->getTelephone()) {
            $phoneNumber = $billingAddress->getTelephone();
        }

        if (!$phoneNumber) {
            throw new \InvalidArgumentException(
                sprintf(
                    'No telephone number supplied for order %s, not queueing transactional SMS.',
                    $order->getIncrementId()
                )
            );
        }

        return $phoneNumber;
    }
}
