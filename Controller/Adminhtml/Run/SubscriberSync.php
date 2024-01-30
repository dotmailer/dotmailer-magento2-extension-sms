<?php

namespace Dotdigitalgroup\Sms\Controller\Adminhtml\Run;

use Dotdigitalgroup\Email\Model\Cron\JobChecker;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriberFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class SubscriberSync extends Action implements HttpGetActionInterface
{
    /**
     * @var JobChecker
     */
    private $jobChecker;

    /**
     * @var SmsSubscriberFactory
     */
    private $smsSubscriberFactory;

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Sms::config';

    /**
     * @param JobChecker $jobChecker
     * @param SmsSubscriberFactory $subscriberFactory
     * @param Context $context
     */
    public function __construct(
        JobChecker $jobChecker,
        SmsSubscriberFactory $subscriberFactory,
        Context $context
    ) {
        $this->jobChecker = $jobChecker;
        $this->smsSubscriberFactory = $subscriberFactory;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Run SMS Subscriber sync.
     *
     * @return ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        /** @var Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setRefererUrl();

        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_sms_subscriber')) {
            $this->messageManager->addNoticeMessage(
                sprintf('%s cron is currently running.', 'ddg_automation_sms_subscriber')
            );
            return $redirect;
        }

        $subscriberSyncResult = $this->smsSubscriberFactory->create(
            ['data' => ['web' => true]]
        )->sync();
        $this->messageManager->addSuccessMessage(
            __($subscriberSyncResult['message'])
        );

        return $redirect;
    }
}
