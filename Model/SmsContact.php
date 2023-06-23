<?php

namespace Dotdigitalgroup\Sms\Model;

use DateTimeInterface;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;

class SmsContact extends Contact
{
    /**
     * @var ContactResource
     */
    private $contactResource;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * Contact constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ContactResource $contactResource
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        ContactResource $contactResource,
        ContactCollectionFactory $contactCollectionFactory,
        DateTime $dateTime
    ) {
        $this->contactResource = $contactResource;
        $this->dateTime = $dateTime;

        parent::__construct(
            $context,
            $registry,
            $contactResource,
            $contactCollectionFactory,
            $dateTime
        );
    }

    /**
     * Set date of last changed status
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->dataHasChangedFor('sms_subscriber_status')) {
            $this->setSmsChangeStatusAt($this->dateTime->formatDate(true));
        }
        return $this;
    }
}
