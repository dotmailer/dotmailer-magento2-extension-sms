<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Sms\Model\Queue;

use Dotdigitalgroup\Sms\Api\SmsMessageRepositoryInterface;
use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Dotdigitalgroup\Sms\Model\ResourceModel\SmsMessageFactory as SmsMessageResourceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Intl\DateTimeFactory;

class SmsMessageQueueManager
{
    public const SMS_STATUS_PENDING = 0;
    public const SMS_STATUS_IN_PROGRESS = 1;
    public const SMS_STATUS_DELIVERED = 2;
    public const SMS_STATUS_FAILED = 3;
    public const SMS_STATUS_EXPIRED = 4;
    public const SMS_STATUS_UNKNOWN = 5;

    /**
     * @var SmsMessageRepositoryInterface
     */
    private $smsMessageRepository;

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
     * @var SmsMessageResourceFactory
     */
    private $smsMessageResourceFactory;

    /**
     * SmsMessageQueueManager constructor.
     *
     * @param SmsMessageRepositoryInterface $smsMessageRepository
     * @param Configuration $moduleConfig
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DateTimeFactory $dateTimeFactory
     * @param SmsMessageResourceFactory $smsMessageResourceFactory
     */
    public function __construct(
        SmsMessageRepositoryInterface $smsMessageRepository,
        Configuration $moduleConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DateTimeFactory $dateTimeFactory,
        SmsMessageResourceFactory $smsMessageResourceFactory
    ) {
        $this->smsMessageRepository = $smsMessageRepository;
        $this->moduleConfig = $moduleConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->smsMessageResourceFactory = $smsMessageResourceFactory;
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

        return $this->smsMessageRepository->getList($searchCriteria->create());
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

        return $this->smsMessageRepository->getList($searchCriteria->create());
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

        $this->smsMessageResourceFactory
            ->create()
            ->expirePendingRowsOlderThan($oneDayAgo);
    }
}
