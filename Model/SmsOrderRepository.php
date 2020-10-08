<?php

namespace Dotdigitalgroup\Sms\Model;

use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterfaceFactory;
use Dotdigitalgroup\Sms\Api\SmsOrderRepositoryInterface;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsOrderFactory as SmsOrderResourceFactory;
use Dotdigitalgroup\Sms\Model\Query\GetList;
use Magento\Framework\Api\SearchCriteriaInterface;

class SmsOrderRepository implements SmsOrderRepositoryInterface
{
    /**
     * @var SmsOrderInterfaceFactory
     */
    private $smsOrderInterfaceFactory;

    /**
     * @var SmsOrderResourceFactory
     */
    private $smsOrderResourceFactory;

    /**
     * @var GetList
     */
    private $smsList;

    /**
     * SMSOrderRepository constructor.
     * @param SmsOrderInterfaceFactory $smsOrderInterfaceFactory
     * @param SmsOrderResourceFactory $smsOrderResourceFactory
     * @param GetList $smsList
     */
    public function __construct(
        SmsOrderInterfaceFactory $smsOrderInterfaceFactory,
        SmsOrderResourceFactory $smsOrderResourceFactory,
        GetList $smsList
    ) {
        $this->smsOrderInterfaceFactory = $smsOrderInterfaceFactory;
        $this->smsOrderResourceFactory = $smsOrderResourceFactory;
        $this->smsList = $smsList;
    }

    /**
     * @param $id
     * @return \Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface
     */
    public function getById($id)
    {
        return $this->smsOrderInterfaceFactory->create()->load($id, 'id');
    }

    /**
     * @param SmsOrderInterface $orderSms
     */
    public function save(SmsOrderInterface $orderSms)
    {
        $this->smsOrderResourceFactory
            ->create()
            ->save($orderSms);
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResults|void
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        return $this->smsList->getList($searchCriteria);
    }
}
