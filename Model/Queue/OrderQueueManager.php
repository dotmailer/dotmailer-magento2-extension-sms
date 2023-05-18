<?php

namespace Dotdigitalgroup\Sms\Model\Queue;

use Dotdigitalgroup\Sms\Api\SmsOrderRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Store\Model\StoreManagerInterface;

class OrderQueueManager
{
    public const SMS_STATUS_PENDING = 0;
    public const SMS_STATUS_IN_PROGRESS = 1;
    public const SMS_STATUS_DELIVERED = 2;
    public const SMS_STATUS_FAILED = 3;
    public const SMS_STATUS_EXPIRED = 4;
    public const SMS_STATUS_UNKNOWN = 5;

    /**
     * @var SmsOrderRepositoryInterface
     */
    private $smsOrderRepository;

    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * OrderQueueManager constructor.
     *
     * @param SmsOrderRepositoryInterface $smsOrderRepository
     * @param Configuration $moduleConfig
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DateTimeFactory $dateTimeFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SmsOrderRepositoryInterface $smsOrderRepository,
        Configuration $moduleConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DateTimeFactory $dateTimeFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->smsOrderRepository = $smsOrderRepository;
        $this->moduleConfig = $moduleConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * The pending queue limits by batch size and filters out rows with no phone number.
     *
     * @param array $storeIds
     * @return \Magento\Framework\Api\SearchResults
     */
    public function getPendingQueue(array $storeIds)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', self::SMS_STATUS_PENDING)
            ->addFilter('store_id', [$storeIds], 'in')
            ->addFilter('phone_number', null, 'neq')
            ->setPageSize($this->moduleConfig->getBatchSize());

        return $this->smsOrderRepository->getList($searchCriteria->create());
    }

    /**
     * Get the in progress queue for the given store ids.
     *
     * @param array $storeIds
     * @return \Magento\Framework\Api\SearchResults
     */
    public function getInProgressQueue(array $storeIds)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', self::SMS_STATUS_IN_PROGRESS)
            ->addFilter('store_id', [$storeIds], 'in');

        return $this->smsOrderRepository->getList($searchCriteria->create());
    }

    /**
     * Get expired sends.
     *
     * @return void
     */
    public function expirePendingSends()
    {
        $now = $this->dateTimeFactory->create('now', new \DateTimeZone('UTC'));
        $oneDayAgo = $now->sub(new \DateInterval('PT24H'));

        $this->smsOrderRepository->expirePendingRowsOlderThan($oneDayAgo);
    }
}
