<?php

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;

class SmsContact extends Contact
{
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * SmsContact constructor.
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

    /**
     * Set mobile number.
     *
     * If we have a number to set, remove spaces and the +.
     * If we don't (e.g. number is being removed), use native behaviour.
     *
     * @param string|int $mobileNumber
     *
     * @return void
     */
    public function setMobileNumber($mobileNumber)
    {
        if (!$mobileNumber) {
            parent::setMobileNumber();
            return;
        }

        $mobileNumber = str_replace(' ', '', $mobileNumber);
        parent::setMobileNumber((int) $mobileNumber);
    }

    /**
     * Get mobile number.
     *
     * @return string
     */
    public function getMobileNumber(): string
    {
        if (! parent::getMobileNumber()) {
            return '';
        }
        $mobileNumber = str_replace(' ', '', parent::getMobileNumber());

        return '+' . (int) $mobileNumber;
    }
}
