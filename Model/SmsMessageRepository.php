<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model;

use DateTime;
use Dotdigitalgroup\Sms\Api\Data\SmsMessageInterface;
use Dotdigitalgroup\Sms\Api\SmsMessageRepositoryInterface;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessageFactory as SmsMessageResourceFactory;
use Dotdigitalgroup\Sms\Model\Query\GetList;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;

class SmsMessageRepository implements SmsMessageRepositoryInterface
{
    /**
     * @var GetList
     */
    private $smsList;

    /**
     * @var \Dotdigitalgroup\Sms\Model\SmsMessageFactory
     */
    private $smsMessageFactory;

    /**
     * @var SmsMessageResourceFactory
     */
    private $smsMessageResourceFactory;

    /**
     * SmsMessageRepository constructor.
     *
     * @param SmsMessageResourceFactory $smsMessageResourceFactory
     * @param SmsMessageFactory $smsMessageFactory
     * @param GetList $smsList
     */
    public function __construct(
        SmsMessageResourceFactory $smsMessageResourceFactory,
        SmsMessageFactory $smsMessageFactory,
        GetList $smsList
    ) {
        $this->smsMessageResourceFactory = $smsMessageResourceFactory;
        $this->smsMessageFactory = $smsMessageFactory;
        $this->smsList = $smsList;
    }

    /**
     * @inheritdoc
     */
    public function getById($id)
    {
        $smsMessageQueueRow = $this->smsMessageFactory->create();
        $this->smsMessageResourceFactory->create()->load($smsMessageQueueRow, $id, 'id');
        if (!$smsMessageQueueRow->getId()) {
            throw new NoSuchEntityException(
                __("The queued message that was requested doesn't exist.")
            );
        }
        return $smsMessageQueueRow;
    }

    /**
     * Save the SMS order.
     *
     * @param SmsMessageInterface $orderSms
     * @throws AlreadyExistsException
     */
    public function save(SmsMessageInterface $orderSms)
    {
        $this->smsMessageResourceFactory
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
