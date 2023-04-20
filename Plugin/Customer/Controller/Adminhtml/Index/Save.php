<?php

namespace Dotdigitalgroup\Sms\Plugin\Customer\Controller\Adminhtml\Index;

use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Sms\Model\Subscriber;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\AlreadyExistsException;

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
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param ContactResource $contactResource
     */
    public function __construct(
        ContactCollectionFactory $contactCollectionFactory,
        ContactResource $contactResource
    ) {
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
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
        $isSubscribed = $subject->getRequest()->getParam('is_subscribed');
        $customerId = $subject->getRequest()->getParam('customer_id');

        $contactModel = $this->contactCollectionFactory->create()
            ->loadByCustomerId($customerId);

        if (!$contactModel) {
            return $result;
        }

        $contactModel->setMobileNumber($mobileNumber);
        $contactModel->setSmsSubscriberStatus(
            $isSubscribed ?
                Subscriber::STATUS_SUBSCRIBED :
                Subscriber::STATUS_UNSUBSCRIBED
        );
        $contactModel->setSmsSubscriberImported(Contact::EMAIL_CONTACT_NOT_IMPORTED);

        $this->contactResource->save($contactModel);
        return $result;
    }
}
