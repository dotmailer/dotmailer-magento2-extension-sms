<?php

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriberFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Cron
{
    /**
     * @var SmsSenderManagerFactory
     */
    private $senderManagerFactory;

    /**
     * @var SmsSubscriberFactory
     */
    private $smsSubscriberFactory;

    /**
     * Cron constructor.
     *
     * @param SmsSenderManagerFactory $senderManagerFactory
     * @param SmsSubscriberFactory $smsSubscriberFactory
     */
    public function __construct(
        SmsSenderManagerFactory $senderManagerFactory,
        SmsSubscriberFactory $smsSubscriberFactory
    ) {
        $this->senderManagerFactory = $senderManagerFactory;
        $this->smsSubscriberFactory = $smsSubscriberFactory;
    }

    /**
     * Send sms order messages.
     *
     * @return void
     */
    public function sendSmsOrderMessages()
    {
        $this->senderManagerFactory->create()
            ->run();
    }
}
