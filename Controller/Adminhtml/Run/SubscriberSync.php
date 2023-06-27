<?php

namespace Dotdigitalgroup\Sms\Controller\Adminhtml\Run;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Dotdigitalgroup\Sms\Model\Sync\SmsSubscriberFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class SubscriberSync extends Action implements HttpGetActionInterface
{
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
     * @param SmsSubscriberFactory $subscriberFactory
     * @param Context $context
     */
    public function __construct(
        SmsSubscriberFactory $subscriberFactory,
        Context $context
    ) {
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
        $subscriberSyncResult = $this->smsSubscriberFactory->create(
            ['data' => ['web' => true]]
        )->sync();
        $this->messageManager->addSuccessMessage(
            __($subscriberSyncResult['message'])
        );
        /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setRefererUrl();
        return $redirect;
    }
}
