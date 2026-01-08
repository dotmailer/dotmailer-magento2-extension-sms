<?php

namespace Dotdigitalgroup\Sms\Controller\Adminhtml\Report;

use Dotdigitalgroup\Email\Controller\Adminhtml\MassDeleteCsrf;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessage;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessage\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends MassDeleteCsrf
{
    /**
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Sms::report';

    /**
     * MassDelete constructor.
     * @param SmsMessage $collectionResource
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        SmsMessage $collectionResource,
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->collectionResource = $collectionResource;
        parent::__construct($context);
    }
}
