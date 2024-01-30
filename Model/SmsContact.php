<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Sms\Model\Number\Utility;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;

class SmsContact extends Contact
{
    /**
     * @var Utility
     */
    private $numberUtility;

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
     * @param Utility $numberUtility
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        ContactResource $contactResource,
        ContactCollectionFactory $contactCollectionFactory,
        Utility $numberUtility,
        DateTime $dateTime
    ) {
        $this->numberUtility = $numberUtility;
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
     * @return $this
     */
    public function setMobileNumber($mobileNumber)
    {
        if (!$mobileNumber) {
            return parent::setMobileNumber();
        }

        return parent::setMobileNumber($this->numberUtility->prepareMobileNumberForStorage($mobileNumber));
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

        return $this->numberUtility->returnMobileNumberFromStorage($mobileNumber);
    }
}
