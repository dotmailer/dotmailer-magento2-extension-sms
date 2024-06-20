<?php

namespace Dotdigitalgroup\Sms\Plugin\Customer\Controller\Adminhtml\Index;

use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\Queue\Message\MarketingSmsSubscribeDataFactory;
use Dotdigitalgroup\Sms\Model\Queue\Message\MarketingSmsUnsubscribeDataFactory;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsContact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\MessageQueue\PublisherInterface;

class Save
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
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var MarketingSmsUnsubscribeDataFactory
     */
    private $marketingSmsUnsubscribeDataFactory;

    /**
     * @var MarketingSmsSubscribeDataFactory
     */
    private $marketingSmsSubscribeDataFactory;

    /**
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactResource $contactResource
     * @param PublisherInterface $publisher
     * @param MarketingSmsSubscribeDataFactory $marketingSmsSubscribeDataFactory
     * @param MarketingSmsUnsubscribeDataFactory $marketingSmsUnsubscribeDataFactory
     */
    public function __construct(
        ContactCollectionFactory $contactCollectionFactory,
        ContactResource $contactResource,
        PublisherInterface $publisher,
        MarketingSmsSubscribeDataFactory $marketingSmsSubscribeDataFactory,
        MarketingSmsUnsubscribeDataFactory $marketingSmsUnsubscribeDataFactory
    ) {
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->publisher = $publisher;
        $this->marketingSmsSubscribeDataFactory = $marketingSmsSubscribeDataFactory;
        $this->marketingSmsUnsubscribeDataFactory = $marketingSmsUnsubscribeDataFactory;
    }

    /**
     * After execute.
     *
     * @param \Magento\Customer\Controller\Adminhtml\Index\Save $subject
     * @param Redirect $result
     * @return mixed
     * @throws AlreadyExistsException
     */
    public function afterExecute(
        \Magento\Customer\Controller\Adminhtml\Index\Save $subject,
        $result
    ) {
        $mobileNumber = $subject->getRequest()->getParam('mobile_number');
        $hasSubscribed = $subject->getRequest()->getParam('is_subscribed');
        $customerId = $subject->getRequest()->getParam('customer_id');

        $contactModel = $this->contactCollectionFactory->create()
            ->loadByCustomerId($customerId);

        if (!$contactModel) {
            return $result;
        }

        if (!$hasSubscribed && $contactModel->getSmsSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED) {
            $contactMessage = $this->marketingSmsUnsubscribeDataFactory->create();
            $contactMessage->setWebsiteId((string)$contactModel->getWebsiteId());
            $contactMessage->setEmail((string)$contactModel->getEmail());

            $this->publisher->publish(
                'ddg.sms.unsubscribe',
                $contactMessage
            );
        }

        $contactModel->setMobileNumber($mobileNumber);
        $contactModel->setSmsSubscriberStatus(
            $hasSubscribed ?
                Subscriber::STATUS_SUBSCRIBED :
                Subscriber::STATUS_UNSUBSCRIBED
        );

        if ($hasSubscribed) {
            $contactModel->setSmsSubscriberImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);
            $this->publisher->publish(
                'ddg.sms.subscribe',
                $this->marketingSmsSubscribeDataFactory->create()
                    ->setWebsiteId((int) $contactModel->getData('website_id'))
                    ->setContactId((int) $contactModel->getId())
            );
        }

        $this->contactResource->save($contactModel);
        return $result;
    }
}
