<?php

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriberFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Cron
{
    /**
     * @var SmsStatusManagerFactory
     */
    private $smsStatusManagerFactory;

    /**
     * @var SmsSubscriberFactory
     */
    private $smsSubscriberFactory;

    /**
     * Cron constructor.
     *
     * @param SmsStatusManagerFactory $smsStatusManagerFactory
     * @param SmsSubscriberFactory $smsSubscriberFactory
     */
    public function __construct(
        SmsStatusManagerFactory $smsStatusManagerFactory,
        SmsSubscriberFactory $smsSubscriberFactory
    ) {
        $this->smsStatusManagerFactory = $smsStatusManagerFactory;
        $this->smsSubscriberFactory = $smsSubscriberFactory;
    }

    /**
     * Update SMS message statuses.
     *
     * @return void
     */
    public function updateSmsStatuses()
    {
        $this->smsStatusManagerFactory->create()
            ->run();
    }
}
