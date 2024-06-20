<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Observer\Customer;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\Consent\ConsentManager;
use Dotdigitalgroup\Sms\Model\Queue\Item\NewAccountSignup;
use Dotdigitalgroup\Sms\Model\Queue\Item\TransactionalMessageEnqueuer;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Dotdigitalgroup\Sms\Model\Queue\Message\MarketingSmsSubscribeDataFactory;

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
     * @var NewAccountSignup
     */
    private $newAccountSignupQueueItem;

    /**
     * @var TransactionalMessageEnqueuer
     */
    private $transactionalMessageEnqueuer;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var MarketingSmsSubscribeDataFactory
     */
    private $marketingSmsSubscribeDataFactory;

    /**
     * Register constructor.
     *
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactResource $contactResource
     * @param ConsentManager $consentManager
     * @param TransactionalMessageEnqueuer $transactionalMessageEnqueuer
     * @param NewAccountSignup $newAccountSignupQueueItem
     * @param Context $context
     * @param PublisherInterface $publisher
     * @param MarketingSmsSubscribeDataFactory $marketingSmsSubscribeDataFactory
     */
    public function __construct(
        ContactCollectionFactory $contactCollectionFactory,
        ContactResource $contactResource,
        ConsentManager $consentManager,
        TransactionalMessageEnqueuer $transactionalMessageEnqueuer,
        NewAccountSignup $newAccountSignupQueueItem,
        Context $context,
        PublisherInterface $publisher,
        MarketingSmsSubscribeDataFactory $marketingSmsSubscribeDataFactory
    ) {
        $this->consentManager = $consentManager;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->transactionalMessageEnqueuer = $transactionalMessageEnqueuer;
        $this->newAccountSignupQueueItem = $newAccountSignupQueueItem;
        $this->publisher = $publisher;
        $this->marketingSmsSubscribeDataFactory = $marketingSmsSubscribeDataFactory;
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

            if ($contactModel && $contactModel->getId()) {
                $contactModel->setMobileNumber($request->get('mobile_number'));
                $contactModel->setSmsSubscriberStatus(Subscriber::STATUS_SUBSCRIBED);
                $this->contactResource->save($contactModel);
            }
            $this->consentManager->createConsentRecord($contactModel->getId(), $storeId);

            if ($this->transactionalMessageEnqueuer->canQueue($this->newAccountSignupQueueItem, (int) $storeId)) {
                $this->newAccountSignupQueueItem->prepare(
                    $customer,
                    $request->get('mobile_number')
                );
                $this->transactionalMessageEnqueuer->queue(
                    $this->newAccountSignupQueueItem
                );
            }

            $this->publisher->publish(
                'ddg.sms.subscribe',
                $this->marketingSmsSubscribeDataFactory->create()
                    ->setWebsiteId((int) $contactModel->getData('website_id'))
                    ->setContactId((int) $contactModel->getId())
            );
        }
    }
}
