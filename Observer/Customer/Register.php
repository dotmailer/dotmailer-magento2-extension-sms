<?php

namespace Dotdigitalgroup\Sms\Observer\Customer;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\Consent\ConsentManager;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Register implements ObserverInterface
{
    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var ConsentManager
     */
    private $consentManager;

    /**
     * @var Context
     */
    private $context;

    /**
     * Register constructor.
     *
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactResource $contactResource
     * @param ConsentManager $consentManager
     * @param Context $context
     */
    public function __construct(
        ContactCollectionFactory $contactCollectionFactory,
        ContactResource $contactResource,
        ConsentManager $consentManager,
        Context $context
    ) {
        $this->consentManager = $consentManager;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->context = $context;
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
        /** @var Http $request */
        $request = $this->context->getRequest();
        $post = $request->getPost();

        if ($post->get('is_sms_subscribed')) {
            $customer = $observer->getEvent()->getCustomer();
            $storeId = $customer->getStoreId();
            $contactModel = $this->contactCollectionFactory->create()
                ->loadByCustomerEmail(
                    $customer->getEmail(),
                    $customer->getWebsiteId()
                );

            if ($contactModel->getId()) {
                $contactModel->setMobileNumber($request->get('mobile_number'));
                $contactModel->setSmsSubscriberStatus(Subscriber::STATUS_SUBSCRIBED);
                $this->contactResource->save($contactModel);
            }
            $this->consentManager->createConsentRecord($contactModel->getId(), $storeId);
        }
    }
}
