<?php

namespace Dotdigitalgroup\Sms\Plugin\Customer\Controller\Adminhtml\Index;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\Queue\Message\SmsSubscriptionDataFactory;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Customer\Controller\Adminhtml\Index\Save as CustomerAdminSaveController;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\MessageQueue\PublisherInterface;

class Save
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
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var SmsSubscriptionDataFactory
     */
    private $smsSubscriptionDataFactory;

    /**
     * @param Logger $logger
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactResource $contactResource
     * @param PublisherInterface $publisher
     * @param SmsSubscriptionDataFactory $smsSubscriptionDataFactory
     */
    public function __construct(
        Logger $logger,
        ContactCollectionFactory $contactCollectionFactory,
        ContactResource $contactResource,
        PublisherInterface $publisher,
        SmsSubscriptionDataFactory $smsSubscriptionDataFactory
    ) {
        $this->logger = $logger;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->publisher = $publisher;
        $this->smsSubscriptionDataFactory = $smsSubscriptionDataFactory;
    }

    /**
     * After execute.
     *
     * @param CustomerAdminSaveController $subject
     * @param Redirect $result
     * @return ResultInterface
     * @throws AlreadyExistsException
     */
    public function afterExecute(
        CustomerAdminSaveController $subject,
        $result
    ) {
        $mobileNumber = $subject->getRequest()->getParam('mobile_number');
        $hasSubscribed = $subject->getRequest()->getParam('is_subscribed');
        $customerId = $subject->getRequest()->getParam('customer_id');

        try {
            $contactModel = $this->contactCollectionFactory->create()
                ->loadByCustomerId($customerId);

            if (!$contactModel) {
                return $result;
            }

            if (!$hasSubscribed && $contactModel->getSmsSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED) {
                $contactMessage = $this->smsSubscriptionDataFactory->create();
                $contactMessage->setWebsiteId((int) $contactModel->getWebsiteId());
                $contactMessage->setEmail((string) $contactModel->getEmail());
                $contactMessage->setType('unsubscribe');

                $this->publisher->publish(
                    Subscriber::TOPIC_SMS_SUBSCRIPTION,
                    $contactMessage
                );
            }

            $contactModel->setMobileNumber($mobileNumber);
            $contactModel->setSmsSubscriberStatus(
                $hasSubscribed ?
                    Subscriber::STATUS_SUBSCRIBED:
                    Subscriber::STATUS_UNSUBSCRIBED
            );

            if ($hasSubscribed) {
                $contactModel->setSmsSubscriberImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
                $this->publisher->publish(
                    Subscriber::TOPIC_SMS_SUBSCRIPTION,
                    $this->smsSubscriptionDataFactory->create()
                        ->setWebsiteId((int) $contactModel->getData('website_id'))
                        ->setContactId((int) $contactModel->getId())
                        ->setType('subscribe')
                );
            }

            $this->contactResource->save($contactModel);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error in SMS admin save', [(string) $e]);
            return $result;
        }
    }
}
