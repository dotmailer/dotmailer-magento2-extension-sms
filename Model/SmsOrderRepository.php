<?php

namespace Dotdigitalgroup\Sms\Model;

use DateTime;
use Dotdigitalgroup\Sms\Api\Data\SmsOrderInterface;
use Dotdigitalgroup\Sms\Api\SmsOrderRepositoryInterface;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsOrderFactory as SmsOrderResourceFactory;
use Dotdigitalgroup\Sms\Model\Query\GetList;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;

class SmsOrderRepository implements SmsOrderRepositoryInterface
{
    /**
     * @var GetList
     */
    private $smsList;

    /**
     * @var \Dotdigitalgroup\Sms\Model\SmsOrderFactory
     */
    private $smsOrderFactory;

    /**
     * @var SmsOrderResourceFactory
     */
    private $smsOrderResourceFactory;

    /**
     * SmsOrderRepository constructor.
     *
     * @param SmsOrderResourceFactory $smsOrderResourceFactory
     * @param SmsOrderFactory $smsOrderFactory
     * @param GetList $smsList
     */
    public function __construct(
        SmsOrderResourceFactory $smsOrderResourceFactory,
        SmsOrderFactory $smsOrderFactory,
        GetList $smsList
    ) {
        $this->smsOrderResourceFactory = $smsOrderResourceFactory;
        $this->smsOrderFactory = $smsOrderFactory;
        $this->smsList = $smsList;
    }

    /**
     * @inheritdoc
     */
    public function getById($id)
    {
        $smsOrderQueueRow = $this->smsOrderFactory->create();
        $this->smsOrderResourceFactory->create()->load($smsOrderQueueRow, $id, 'id');
        if (!$smsOrderQueueRow->getId()) {
            throw new NoSuchEntityException(
                __("The queued message that was requested doesn't exist.")
            );
        }
        return $smsOrderQueueRow;
    }

    /**
     * Save the SMS order.
     *
     * @param SmsOrderInterface $orderSms
     * @throws AlreadyExistsException
     */
    public function save(SmsOrderInterface $orderSms)
    {
        $this->smsOrderResourceFactory
            ->create()
            ->save($orderSms);
    }

    /**
     * Get list of SMS orders.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        return $this->smsList->getList($searchCriteria);
    }
}
