<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\SmsSubscriber;

use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Customer\RegistrationStoreResolver;
use Magento\Framework\Exception\LocalizedException;

class StoreResolver
{
    /**
     * @var RegistrationStoreResolver
     */
    private $registrationStoreResolver;

    /**
     * @param RegistrationStoreResolver $registrationStoreResolver
     */
    public function __construct(RegistrationStoreResolver $registrationStoreResolver)
    {
        $this->registrationStoreResolver = $registrationStoreResolver;
    }

    /**
     * Resolve the registration store or non-customer first-contact store before publishing.
     *
     * @param Contact $contact
     * @return int
     * @throws LocalizedException
     */
    public function resolve(Contact $contact): int
    {
        $websiteId = (int) $contact->getData('website_id');
        $customerId = (int) $contact->getData('customer_id');
        if ($customerId > 0) {
            $storeId = $this->registrationStoreResolver->resolve($customerId, $websiteId);
        } else {
            $storeId = (int) $contact->getData('store_id');
            if (!in_array($storeId, $this->registrationStoreResolver->getWebsiteStoreIds($websiteId), true)) {
                $storeId = $this->registrationStoreResolver->getFallbackStoreId($websiteId);
            }
        }

        return $this->validate($storeId, $websiteId);
    }

    /**
     * Reject missing or cross-website message context rather than selecting another account's list.
     *
     * @param int $storeId
     * @param int $websiteId
     * @return int
     * @throws LocalizedException
     */
    public function validate(int $storeId, int $websiteId): int
    {
        if ($storeId <= 0 ||
            !in_array($storeId, $this->registrationStoreResolver->getWebsiteStoreIds($websiteId), true)
        ) {
            throw new LocalizedException(__('SMS subscriber store does not belong to the message website.'));
        }
        return $storeId;
    }
}
