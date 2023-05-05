<?php

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Cron\JobChecker;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriberFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Cron
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SmsSenderManagerFactory
     */
    private $senderManagerFactory;

    /**
     * @var JobChecker
     */
    private $jobChecker;

    /**
     * @var SmsSubscriberFactory
     */
    private $smsSubscriberFactory;

    /**
     * Cron constructor.
     * @param Logger $logger
     * @param SmsSenderManagerFactory $senderManagerFactory
     * @param JobChecker $jobChecker
     * @param SmsSubscriberFactory $smsSubscriberFactory
     */
    public function __construct(
        Logger $logger,
        SmsSenderManagerFactory $senderManagerFactory,
        JobChecker $jobChecker,
        SmsSubscriberFactory $smsSubscriberFactory
    ) {
        $this->logger = $logger;
        $this->senderManagerFactory = $senderManagerFactory;
        $this->jobChecker = $jobChecker;
        $this->smsSubscriberFactory = $smsSubscriberFactory;
    }

    /**
     * Send sms order messages.
     *
     * @return void
     */
    public function sendSmsOrderMessages()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_sms_order_messages')) {
            $message = 'Skipping ddg_automation_sms_order_messages job run';
            $this->logger->info($message);
        }

        $this->senderManagerFactory->create()
            ->run();
    }

    /**
     * Sms subscriber sync.
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function smsSubscriberSync():void
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_sms_subscriber')) {
            $message = 'Skipping ddg_automation_sms_subscriber job run';
            $this->logger->info($message);
        }

        $this->smsSubscriberFactory->create()
            ->sync();
    }
}
