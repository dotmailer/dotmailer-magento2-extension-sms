<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Observer\Customer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\Config\ConfigInterface;
use Dotdigitalgroup\Sms\Model\Consent\ConsentManager;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsSubscriptionDataFactory;
use Dotdigitalgroup\Sms\Model\Queue\Publisher\SmsMessagePublisher;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class Register implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

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
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var SmsSubscriptionDataFactory
     */
    private $smsSubscriptionDataFactory;

    /**
     * @var SmsMessagePublisher
     */
    private $smsMessagePublisher;

    /**
     * @param Logger $logger
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactResource $contactResource
     * @param ConsentManager $consentManager
     * @param Context $context
     * @param PublisherInterface $publisher
     * @param SmsSubscriptionDataFactory $smsSubscriptionDataFactory
     * @param SmsMessagePublisher $smsMessagePublisher
     */
    public function __construct(
        Logger $logger,
        ContactCollectionFactory $contactCollectionFactory,
        ContactResource $contactResource,
        ConsentManager $consentManager,
        Context $context,
        PublisherInterface $publisher,
        SmsSubscriptionDataFactory $smsSubscriptionDataFactory,
        SmsMessagePublisher $smsMessagePublisher
    ) {
        $this->logger = $logger;
        $this->consentManager = $consentManager;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->publisher = $publisher;
        $this->smsSubscriptionDataFactory = $smsSubscriptionDataFactory;
        $this->context = $context;
        $this->smsMessagePublisher = $smsMessagePublisher;
    }

    /**
     * Execute.
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var Http $request */
        $request = $this->context->getRequest();
        $post = $request->getPost();

        if (!$post->get('is_sms_subscribed')) {
            return $this;
        }

        try {
            $customer = $observer->getEvent()->getCustomer();
            $storeId = $customer->getStoreId();
            $mobileNumber = $request->get('mobile_number');

            $contactModel = $this->contactCollectionFactory->create()
                ->loadByCustomerEmail(
                    $customer->getEmail(),
                    $customer->getWebsiteId()
                );

            if (!$contactModel) {
                $this->logger->error(
                    'Error in SMS register observer - contact was not created',
                    ['email' => $customer->getEmail(), 'website_id' => $customer->getWebsiteId()]
                );
                return $this;
            }

            if ($contactModel && $contactModel->getId()) {
                $contactModel->setMobileNumber($mobileNumber);
                $contactModel->setSmsSubscriberStatus(Subscriber::STATUS_SUBSCRIBED);
                $this->contactResource->save($contactModel);
            }

            $this->consentManager->createConsentRecord($contactModel->getId(), $storeId);

            // Publish new account signup SMS message
            $this->smsMessagePublisher->publish(
                ConfigInterface::SMS_TYPE_NEW_ACCOUNT_SIGN_UP,
                [
                    'customer' => $customer,
                    'mobileNumber' => $mobileNumber
                ]
            );

            // Publish SMS subscription message
            $this->publisher->publish(
                Subscriber::TOPIC_SMS_SUBSCRIPTION,
                $this->smsSubscriptionDataFactory->create()
                    ->setWebsiteId((int) $contactModel->getData('website_id'))
                    ->setContactId((int) $contactModel->getId())
                    ->setType('subscribe')
            );
        } catch (\Exception $e) {
            $this->logger->error('Error in SMS register observer', [(string) $e]);
        }

        return $this;
    }
}
