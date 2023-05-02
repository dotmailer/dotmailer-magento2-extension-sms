<?php

namespace Dotdigitalgroup\Sms\Observer\Customer;

use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\Consent\ConsentManager;
use Magento\Framework\Event\Observer;

class Register implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var ContactFactory
     */
    private $contactFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var ConsentManager
     */
    private $consentManager;

    /**
     * @param ContactFactory $contactFactory
     * @param ContactResource $contactResource
     * @param ConsentManager $consentManager
     */
    public function __construct(
        ContactFactory $contactFactory,
        ContactResource $contactResource,
        ConsentManager $consentManager
    ) {
        $this->consentManager = $consentManager;
        $this->contactFactory = $contactFactory;
        $this->contactResource = $contactResource;
    }

    /**
     * Execute.
     *
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute(Observer $observer): void
    {
        $request = $observer->getAccountController()->getRequest()->getPost();
        $customer = $observer->getEvent()->getCustomer();
        $storeId = $customer->getStoreId();

        if ($request->get('is_sms_subscribed')) {
            $contactModel = $this->contactFactory->create()
                ->loadByCustomerEmail(
                    $customer->getEmail(),
                    $customer->getWebsiteId()
                );

            if ($contactModel) {
                $contactModel->setMobileNumber($request->get('mobile_number'));
                $contactModel->setSmsSubscriberStatus($request->get('is_sms_subscribed'));
                $this->contactResource->save($contactModel);
            }
            $this->consentManager->createConsentRecord($contactModel->getId(), $storeId);
        }
    }
}
